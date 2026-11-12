<?php

namespace App\Services\Quotes;

use App\Models\Calculation;
use App\Models\Entity;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Services\Numbering;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Commercial arithmetic and life cycle of quotes. Money is rounded half away from zero to 2 decimals,
 * per line first, then per document – the figures a client can re-add by hand.
 */
final class QuoteService
{
    public const FORMULA_VERSION = 'oferta-1';

    public static function round(float $v, int $decimals = 2): float
    {
        return round($v, $decimals, PHP_ROUND_HALF_UP);
    }

    public function lineTotal(float $quantity, float $unitPrice, float $discountPercent): float
    {
        return self::round($quantity * $unitPrice * (1 - $discountPercent / 100));
    }

    /** Recomputes every stored total from the lines. */
    public function recalculate(Quote $quote): Quote
    {
        $subtotal = 0.0;
        $cost = 0.0;
        $costKnown = true;
        $counted = 0;
        foreach ($quote->items()->get() as $item) {
            $total = $this->lineTotal($item->quantity, $item->unit_price, $item->discount_percent);
            if ($item->line_total !== $total) {
                $item->update(['line_total' => $total]);
            }
            if ($item->is_optional) {
                continue;
            }
            $counted++;
            $subtotal += $total;
            if ($item->unit_cost === null) {
                $costKnown = false;
            } else {
                $cost += self::round($item->quantity * $item->unit_cost);
            }
        }
        $net = self::round($subtotal * (1 - $quote->discount_percent / 100));
        $vat = self::round($net * $quote->vat_rate / 100);
        $quote->update([
            'subtotal' => self::round($subtotal), 'total_net' => $net, 'vat_amount' => $vat, 'total_gross' => self::round($net + $vat),
            'cost_total' => $costKnown && $counted > 0 ? self::round($cost) : null,
        ]);

        return $quote;
    }

    /** Reasons why this quote needs a person with the approval permission (empty = within the approved limits). */
    public function approvalReasons(Quote $quote): array
    {
        $reasons = [];
        $minMargin = setting('quotes.min_margin');
        $maxDiscount = setting('quotes.max_discount');
        $margin = $quote->marginPercent();
        if ($minMargin !== null && $margin !== null && $margin < (float) $minMargin) {
            $reasons[] = 'Marja estimată ('.money($margin).' %) este sub pragul aprobat de '.money($minMargin).' %.';
        }
        if ($minMargin !== null && $margin === null && $quote->total_net > 0) {
            $reasons[] = 'Marja nu poate fi verificată: cel puțin o poziție nu are cost intern.';
        }
        if ($maxDiscount !== null) {
            $highest = max($quote->discount_percent, (float) $quote->items()->max('discount_percent'));
            if ($highest > (float) $maxDiscount) {
                $reasons[] = 'Discountul de '.money($highest).' % depășește limita de '.money($maxDiscount).' %.';
            }
        }

        return $reasons;
    }

    public function submit(Quote $quote, User $user): void
    {
        if ($quote->items()->where('is_optional', false)->count() === 0) {
            throw new RuntimeException('Oferta nu are nicio poziție.');
        }
        $this->recalculate($quote);
        $quote->update(['status' => 'aprobare']);
        $quote->events()->create(['user_id' => $user->id, 'type' => 'trimisa_la_aprobare', 'data' => ['motive' => $this->approvalReasons($quote)]]);
    }

    /** Approval issues the quote: it gets its date, its frozen snapshot, and its calculations are locked. */
    public function approve(Quote $quote, User $user): void
    {
        $entity = $quote->entity;
        if (! $entity->canIssue()) {
            throw new RuntimeException('Emitentul «'.$entity->name.'» nu are datele juridice confirmate de un administrator. Oferta nu poate fi emisă.');
        }
        DB::transaction(function () use ($quote, $user) {
            $this->recalculate($quote);
            $issued = $quote->issued_at ?? now();
            $quote->update([
                'status' => 'aprobata', 'approved_by' => $user->id, 'approved_at' => now(), 'issued_at' => $issued,
                'valid_until' => $quote->valid_until ?? $issued->copy()->addDays((int) setting('quotes.validity_days', 30)),
            ]);
            $quote->update(['snapshot' => $this->snapshot($quote->fresh())]);
            Calculation::whereIn('id', $quote->items()->whereNotNull('calculation_id')->pluck('calculation_id'))->whereNull('locked_at')->update(['locked_at' => now()]);
            $quote->events()->create(['user_id' => $user->id, 'type' => 'aprobata']);
        });
    }

    public function snapshot(Quote $quote): array
    {
        $quote->load(['entity', 'company', 'contact', 'owner', 'items.calculation']);
        $e = $quote->entity;
        $c = $quote->company;

        return [
            'formula_version' => self::FORMULA_VERSION,
            'frozen_at' => now()->toIso8601String(),
            'issuer' => $e->only(['code', 'name', 'legal_name', 'tax_id', 'reg_no', 'address', 'city', 'county', 'postal_code', 'country', 'phone', 'email', 'website', 'bank', 'iban', 'accent_color', 'logo_path', 'logo_on_dark']),
            'client' => $c->only(['name', 'legal_name', 'tax_id', 'reg_no', 'address', 'city', 'county', 'postal_code', 'country']),
            'contact' => $quote->contact ? ['name' => $quote->contact->fullName(), 'position' => $quote->contact->position, 'email' => $quote->contact->email, 'phone' => $quote->contact->phone] : null,
            'owner' => $quote->owner ? ['name' => $quote->owner->name, 'email' => $quote->owner->email, 'phone' => $quote->owner->phone, 'job_title' => $quote->owner->job_title] : null,
            'quote' => $quote->only(['number', 'revision', 'type', 'title', 'language', 'currency', 'exchange_rate', 'vat_rate', 'lead_time', 'payment_terms', 'delivery_terms', 'incoterm',
                'min_order', 'intro', 'notes', 'discount_percent', 'subtotal', 'total_net', 'vat_amount', 'total_gross'])
                + ['issued_at' => $quote->issued_at?->toDateString(), 'valid_until' => $quote->valid_until?->toDateString(), 'exchange_rate_date' => $quote->exchange_rate_date?->toDateString()],
            'items' => $quote->items->map(fn (QuoteItem $i) => $i->only(['position', 'kind', 'code', 'name', 'description', 'quantity', 'unit', 'unit_price', 'discount_percent', 'line_total', 'is_optional', 'lead_time', 'price_breaks']))->all(),
            // Internal part of the snapshot – never rendered in client documents.
            'internal' => [
                'cost_total' => $quote->cost_total,
                'items' => $quote->items->map(fn (QuoteItem $i) => ['position' => $i->position, 'unit_cost' => $i->unit_cost, 'calculation' => $i->calculation ? [
                    'reference' => $i->calculation->reference, 'model' => $i->calculation->results['model'] ?? null, 'parameters' => $i->calculation->results['parameters'] ?? null,
                    'machine' => $i->calculation->results['machine'] ?? null, 'engine_version' => $i->calculation->results['engine_version'] ?? null,
                ] : null])->all(),
            ],
        ];
    }

    public function create(array $data, User $user): Quote
    {
        $entity = Entity::findOrFail($data['entity_id']);

        return Quote::create($data + [
            'number' => Numbering::next('quote-'.$entity->code, $entity->quote_prefix ?: 'OF'), 'revision' => 0, 'status' => 'ciorna', 'owner_id' => $user->id,
        ]);
    }

    /** New editable revision; the previous one stays untouched in the history. */
    public function revise(Quote $quote, User $user): Quote
    {
        return DB::transaction(function () use ($quote, $user) {
            $latest = (int) Quote::withTrashed()->where('number', $quote->number)->max('revision');
            Quote::where('number', $quote->number)->update(['is_current' => false]);
            $copy = $this->copy($quote, ['number' => $quote->number, 'revision' => $latest + 1, 'root_id' => $quote->root_id ?? $quote->id]);
            $copy->events()->create(['user_id' => $user->id, 'type' => 'revizie', 'body' => 'Revizie creată din '.$quote->label()]);

            return $copy;
        });
    }

    public function duplicate(Quote $quote, User $user, ?int $companyId = null): Quote
    {
        return DB::transaction(function () use ($quote, $user, $companyId) {
            $entity = $quote->entity;
            $copy = $this->copy($quote, [
                'number' => Numbering::next('quote-'.$entity->code, $entity->quote_prefix ?: 'OF'), 'revision' => 0, 'root_id' => null, 'owner_id' => $user->id,
                'company_id' => $companyId ?? $quote->company_id, 'contact_id' => $companyId && $companyId !== $quote->company_id ? null : $quote->contact_id, 'rfq_id' => null,
            ]);
            $copy->events()->create(['user_id' => $user->id, 'type' => 'duplicata', 'body' => 'Copie a ofertei '.$quote->label()]);

            return $copy;
        });
    }

    private function copy(Quote $quote, array $overrides): Quote
    {
        $copy = $quote->replicate(['snapshot', 'approved_by', 'approved_at', 'sent_at', 'decided_at', 'issued_at', 'valid_until', 'public_token_hash', 'public_expires_at', 'confirmed_revenue']);
        $copy->fill($overrides + ['status' => 'ciorna', 'is_current' => true]);
        $copy->save();
        foreach ($quote->items as $item) {
            $copy->items()->create($item->replicate(['quote_id'])->toArray());
        }

        return $this->recalculate($copy);
    }

    /** Differences between two revisions, for the comparison screen. */
    public function diff(Quote $old, Quote $new): array
    {
        $labels = ['currency' => 'Monedă', 'vat_rate' => 'TVA %', 'discount_percent' => 'Discount %', 'lead_time' => 'Termen de fabricație', 'payment_terms' => 'Condiții de plată',
            'delivery_terms' => 'Condiții de livrare', 'incoterm' => 'Incoterm', 'min_order' => 'Cantitate minimă', 'total_net' => 'Total net', 'notes' => 'Note'];
        $header = [];
        foreach ($labels as $field => $label) {
            if ((string) $old->{$field} !== (string) $new->{$field}) {
                $header[] = ['label' => $label, 'old' => $old->{$field}, 'new' => $new->{$field}];
            }
        }
        $key = fn (QuoteItem $i) => ($i->code ?: $i->name).'|'.$i->kind;
        $a = $old->items->keyBy($key);
        $b = $new->items->keyBy($key);
        $lines = [];
        foreach ($b as $k => $item) {
            $prev = $a[$k] ?? null;
            if ($prev === null) {
                $lines[] = ['change' => 'adaugata', 'new' => $item, 'old' => null];
            } elseif ($prev->quantity != $item->quantity || $prev->unit_price != $item->unit_price || $prev->discount_percent != $item->discount_percent) {
                $lines[] = ['change' => 'modificata', 'new' => $item, 'old' => $prev];
            }
        }
        foreach ($a as $k => $item) {
            if (! isset($b[$k])) {
                $lines[] = ['change' => 'eliminata', 'new' => null, 'old' => $item];
            }
        }

        return ['header' => $header, 'lines' => $lines];
    }
}
