<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Entity;
use App\Models\Quote;
use App\Models\Rfq;
use App\Support\Vocab;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $days = in_array((int) $request->query('zile'), [30, 90, 365], true) ? (int) $request->query('zile') : 90;
        $from = now()->subDays($days)->startOfDay();
        $currency = setting('quotes.currency', 'EUR');
        $quotes = fn () => Quote::where('is_current', true)->where('currency', $currency)->where('created_at', '>=', $from);

        $sent = (clone $quotes())->whereIn('status', ['trimisa', 'negociere', 'acceptata', 'respinsa', 'expirata'])->count();
        $accepted = (clone $quotes())->where('status', 'acceptata')->count();
        $decided = (clone $quotes())->whereIn('status', ['acceptata', 'respinsa'])->count();

        // Three different indicators, never merged: offered value, accepted value, revenue confirmed by a person.
        $kpi = [
            'rfq_total' => Rfq::where('created_at', '>=', $from)->count(),
            'rfq_open' => Rfq::whereIn('status', Rfq::OPEN)->count(),
            'quotes_draft' => Quote::where('is_current', true)->whereIn('status', ['ciorna', 'aprobare'])->count(),
            'quotes_sent' => $sent,
            'offered' => (float) (clone $quotes())->whereNotIn('status', ['ciorna', 'aprobare', 'anulata'])->sum('total_net'),
            'accepted_value' => (float) (clone $quotes())->where('status', 'acceptata')->sum('total_net'),
            'confirmed' => (float) (clone $quotes())->whereNotNull('confirmed_revenue')->sum('confirmed_revenue'),
            'potential' => (float) Quote::where('is_current', true)->where('currency', $currency)->whereIn('status', ['aprobata', 'trimisa', 'negociere'])->sum('total_net'),
            'accepted' => $accepted,
            'lost' => (clone $quotes())->where('status', 'respinsa')->count(),
            'conversion' => $decided > 0 ? round($accepted / $decided * 100, 1) : null,
        ];
        if ($user->can_('margins.view')) {
            $m = (clone $quotes())->whereNotNull('cost_total')->where('total_net', '>', 0)->whereNotIn('status', ['ciorna', 'anulata'])->selectRaw('sum(total_net) as net, sum(cost_total) as cost')->first();
            $kpi['margin'] = $m && $m->net > 0 ? round(($m->net - $m->cost) / $m->net * 100, 1) : null;
        }

        $byEntity = Entity::orderBy('sort')->get()->mapWithKeys(fn ($e) => [$e->name => (float) (clone $quotes())->where('entity_id', $e->id)->whereNotIn('status', ['ciorna', 'aprobare', 'anulata'])->sum('total_net')])->filter();
        $byType = (clone $quotes())->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');
        $byStatus = Quote::where('is_current', true)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $topClients = (clone $quotes())->whereNotIn('status', ['ciorna', 'anulata'])->selectRaw('company_id, sum(total_net) as v, count(*) as n')->groupBy('company_id')->orderByDesc('v')->limit(6)->with('company')->get();
        $topCountries = Company::whereNotNull('country')->selectRaw('country, count(*) as n')->groupBy('country')->orderByDesc('n')->limit(6)->pluck('n', 'country');
        $byOwner = (clone $quotes())->selectRaw('owner_id, count(*) as n, sum(total_net) as v')->groupBy('owner_id')->orderByDesc('n')->limit(8)->with('owner')->get();

        return view('admin.dashboard', [
            'days' => $days, 'currency' => $currency, 'kpi' => $kpi, 'topClients' => $topClients, 'byOwner' => $byOwner,
            'tasks' => Activity::with('company')->where('type', 'sarcina')->whereNull('done_at')->where(fn ($w) => $w->where('assigned_to', $user->id)->orWhereNull('assigned_to'))->orderBy('due_at')->limit(8)->get(),
            'followUps' => Company::whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<=', now()->addDays(7))->orderBy('next_follow_up_at')->limit(8)->get(),
            'overdue' => Company::whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', now())->count(),
            'rfqs' => $user->can_('rfq.view') ? Rfq::with('company')->whereIn('status', Rfq::OPEN)->orderByRaw('due_at is null, due_at')->limit(8)->get() : collect(),
            'charts' => [
                'entity' => ['type' => 'bar', 'horizontal' => true, 'labels' => $byEntity->keys()->all(), 'datasets' => [['label' => 'Valoare ofertată ('.$currency.')', 'data' => $byEntity->values()->all()]]],
                'type' => ['type' => 'doughnut', 'labels' => $byType->keys()->map(fn ($k) => Vocab::QUOTE_TYPES[$k] ?? $k)->all(), 'datasets' => [['data' => $byType->values()->all()]]],
                'status' => ['type' => 'bar', 'labels' => $byStatus->keys()->map(fn ($k) => Vocab::QUOTE_STATUSES[$k] ?? $k)->all(), 'datasets' => [['label' => 'Oferte', 'data' => $byStatus->values()->all()]]],
                'country' => ['type' => 'bar', 'horizontal' => true, 'labels' => $topCountries->keys()->map(fn ($k) => Vocab::countries()[$k] ?? $k)->all(), 'datasets' => [['label' => 'Companii', 'data' => $topCountries->values()->all()]]],
            ],
        ]);
    }

    /** Modules scheduled for a later phase: an honest status page instead of buttons that do nothing. */
    public function planned(string $module)
    {
        $modules = [
            'prospectare' => ['AI Prospectare Europa', 'prospecting', 'Faza 4', [
                'Căutare de companii europene după țară, industrie, procese și cuvinte-cheie, printr-o sursă de date B2B aprobată (cheia Claude nu oferă căutare pe internet).',
                'Dosar de prospectare cu surse verificabile, data verificării și scor de relevanță explicabil.',
                'Nicio companie, contact sau adresă nu este inventată; contactarea trece prin verificarea bazei legale, a listei de excludere și prin aprobarea unui operator.',
            ], 'Pregătit acum: cheia API și modelul se configurează în Configurări → API Anthropic Claude; lista globală de excludere și câmpurile GDPR există în CRM.'],
            'marketing' => ['Email Marketing', 'marketing', 'Faza 4', [
                'Liste și segmente, șabloane HTML responsive, campanii programate și de follow-up în RO/EN/DE.',
                'Evidența abonărilor și dezabonărilor, tratarea bounce-urilor, statistici în limitele legale.',
                'Trimiterile în masă vor fi limitate și nu se activează automat către prospecte.',
            ], 'Pregătit acum: SMTP cu coadă și reîncercare, lista globală de excludere, câmpurile «nu contacta» și baza legală pe companii și contacte.'],
            'sabloane' => ['Șabloane și documente', 'templates', 'Faza 3', [
                'Texte comerciale pe emitent și limbă (introducere, condiții, subsol, email de însoțire).',
                'Bibliotecă de documente tehnice private (PDF, XLSX, DXF, STEP) atașate companiilor, cererilor și ofertelor.',
            ], 'Pregătit acum: PDF-ul ofertei în română, engleză și germană, cu identitatea emitentului; tabelele pentru șabloane și documente există în baza de date.'],
            'rapoarte' => ['Rapoarte și analiză comercială', 'reports', 'Faza 4', [
                'Rapoarte pe perioadă cu export: conversie, marje, activitatea ofertanților, performanța campaniilor.',
            ], 'Disponibil acum: indicatorii și graficele din Panoul principal, cu filtru pe 30 / 90 / 365 de zile.'],
        ];
        abort_unless(isset($modules[$module]), 404);
        [$title, $perm, $phase, $scope, $ready] = $modules[$module];
        abort_unless(request()->user()->can_($perm), 403);

        return view('admin.planned', compact('title', 'phase', 'scope', 'ready'));
    }
}
