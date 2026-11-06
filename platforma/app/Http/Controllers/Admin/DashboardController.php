<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Models\Capability;
use App\Models\Document;
use App\Models\Industry;
use App\Models\MailOutbox;
use App\Models\Media;
use App\Models\Page;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Services\Mail\Outbox;
use App\Support\TagConfig;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        $stats = [
            'Cereri noi' => QuoteRequest::where('status', 'nou')->count(),
            'Cereri în ultimele 30 de zile' => QuoteRequest::where('created_at', '>=', now()->subDays(30))->count(),
            'Emailuri în așteptare' => MailOutbox::where('status', 'pending')->count(),
            'Emailuri eșuate' => MailOutbox::where('status', 'failed')->count(),
        ];

        // Missing information checklist – nothing here is published until completed.
        $todo = [];
        foreach (['company.legal_name' => 'Denumirea juridică a firmei', 'company.cui' => 'CUI', 'company.reg_com' => 'Nr. Registrul Comerțului', 'company.postal_code' => 'Cod poștal', 'company.hours' => 'Program de lucru'] as $k => $l) {
            if (! setting($k)) {
                $todo[] = [$l, route('admin.settings.edit', 'company'), 'content'];
            }
        }
        foreach (Capability::where('is_confirmed', false)->orderBy('sort')->get() as $c) {
            $todo[] = ['Capacitate: '.$c->label, route('admin.res.edit', ['capabilities', $c->id]), 'content'];
        }
        if (! Industry::where('is_published', true)->exists()) {
            $todo[] = ['Industriile deservite (exemplele sunt nepublicate)', route('admin.res.index', 'industries'), 'content'];
        }
        if (! Document::where('is_public', true)->exists()) {
            $todo[] = ['Certificări și documente de calitate (nu există niciunul public)', route('admin.res.index', 'documents'), 'content'];
        }
        if (! Project::where('is_published', true)->exists()) {
            $todo[] = ['Proiecte de referință (cu acordul clienților)', route('admin.res.index', 'projects'), 'content'];
        }
        $todo[] = ['Descrierea Argento Group (pagina Despre, secțiunea Argento Group)', route('admin.pages.edit', Page::where('slug', 'despre')->first() ?? 1), 'content'];
        if (! Outbox::configured()) {
            $todo[] = ['Configurare SMTP – fără ea emailurile rămân în coadă', route('admin.settings.edit', 'smtp'), 'smtp'];
        }
        if (! \App\Services\Ai\Assistant::configured()) {
            $todo[] = ['Cheia API pentru asistentul AI (asistentul este ascuns până atunci)', route('admin.ai.index'), 'ai'];
        }
        if (! TagConfig::needsConsent()) {
            $todo[] = ['Google Analytics / Tag Manager (opțional)', route('admin.google.index'), 'google'];
        }
        if (Media::where(fn ($w) => $w->whereNull('alt')->orWhere('alt', ''))->exists()) {
            $todo[] = ['Text alternativ pentru imagini', route('admin.media.index'), 'media'];
        }
        $todo = array_values(array_filter($todo, fn ($t) => $u->can_($t[2])));

        $lastBackup = Backup::where('status', 'ok')->latest()->first();
        $cron = cache('mail-outbox:last-run');

        return view('admin.dashboard', [
            'stats' => $stats,
            'todo' => $todo,
            'latest' => $u->can_('quotes.view') ? QuoteRequest::latest()->limit(8)->get() : collect(),
            'lastBackup' => $lastBackup,
            'cronOk' => $cron && now()->timestamp - $cron < 600,
            'unpublished' => Page::where('has_unpublished_changes', true)->get(),
        ]);
    }
}
