<?php

namespace App\Http\Controllers\Admin;

use App\Admin\Form;
use App\Admin\SettingGroups;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MailOutbox;
use App\Models\Setting;
use App\Services\Mail\Outbox;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private function group(Request $r, string $key): array
    {
        $g = SettingGroups::get($key);
        abort_unless($r->user()->can_($g['perm']), 403);

        return $g;
    }

    private static function input(string $name): string
    {
        return str_replace('.', '__', $name);
    }

    public function edit(Request $request, string $key)
    {
        $g = $this->group($request, $key);
        $extra = [];
        if ($key === 'smtp') {
            $extra['outbox'] = MailOutbox::whereIn('status', ['pending', 'failed', 'sending'])->latest('id')->limit(50)->get();
            $extra['sent'] = MailOutbox::where('status', 'sent')->latest('id')->limit(10)->get();
            $extra['lastRun'] = cache('mail-outbox:last-run');
        }

        return view('admin.settings.edit', ['g' => $g, 'input' => fn ($n) => self::input($n)] + $extra);
    }

    public function update(Request $request, string $key)
    {
        $g = $this->group($request, $key);
        $fields = array_map(fn ($f) => ['name' => self::input($f['name'])] + $f, $g['fields']);
        $validated = validator(Form::normalise($fields, $request->all()), Form::rules($fields))->validate();
        $changed = [];
        foreach ($g['fields'] as $f) {
            $in = self::input($f['name']);
            if ($f['type'] === 'secret') {
                if ($request->boolean($in.'__clear')) {
                    Setting::forget($f['name']);
                    $changed[] = $f['name'];
                } elseif (($validated[$in] ?? '') !== '') {
                    Setting::put($f['name'], $validated[$in], true);
                    $changed[] = $f['name'];
                }

                continue;
            }
            $value = $validated[$in] ?? null;
            if (in_array($f['type'], ['number', 'decimal'], true) && $value !== null && $value !== '') {
                $value = $f['type'] === 'number' ? (int) $value : (float) $value;
            }
            if ($value === null || $value === '') {
                if (Setting::has($f['name'])) {
                    Setting::forget($f['name']);
                    $changed[] = $f['name'];
                }

                continue;
            }
            if (setting($f['name']) !== $value) {
                Setting::put($f['name'], $value);
                $changed[] = $f['name'];
            }
        }
        AuditLog::record('setari.'.$key, null, ['chei' => $changed]);

        return back()->with('status', $changed ? 'Setările au fost salvate.' : 'Nicio modificare.');
    }

    public function smtpTest(Request $request)
    {
        abort_unless($request->user()->can_('smtp'), 403);
        $to = $request->validate(['to' => 'required|email'])['to'];
        if (! Outbox::configured()) {
            return back()->with('error', 'Completați și salvați serverul SMTP și adresa expeditorului.');
        }
        $m = MailOutbox::create(['template' => 'test', 'to_email' => $to, 'subject' => 'Test SMTP – '.setting('company.name', 'GRUP SIMEX'), 'status' => 'pending', 'next_attempt_at' => now()]);
        $ok = app(Outbox::class)->deliver($m);
        $m->refresh();
        AuditLog::record('smtp.test', null, ['rezultat' => $ok ? 'trimis' : 'esuat']);

        return back()->with($ok ? 'status' : 'error', $ok ? 'Emailul de test a fost trimis la '.$to.'.' : 'Trimiterea a eșuat: '.$m->last_error);
    }

    public function outboxRetry(Request $request, ?int $id = null)
    {
        abort_unless($request->user()->can_('smtp'), 403);
        $q = MailOutbox::whereIn('status', ['failed', 'pending']);
        if ($id) {
            $q->whereKey($id);
        }
        $n = $q->update(['status' => 'pending', 'next_attempt_at' => now(), 'attempts' => 0]);
        [$sent, $failed] = app(Outbox::class)->process(50);
        AuditLog::record('smtp.reincercare', null, ['mesaje' => $n, 'trimise' => $sent]);

        return back()->with($failed ? 'warning' : 'status', "Reîncercare: {$sent} trimise, {$failed} eșuate.");
    }
}
