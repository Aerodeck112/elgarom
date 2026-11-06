<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\Ai\Assistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AiController extends Controller
{
    public function index()
    {
        $days = DB::table('ai_usage_daily')->where('day', '>=', now()->subDays(30)->toDateString())->orderByDesc('day')->get();

        return view('admin.ai.index', [
            'days' => $days,
            'today' => Assistant::spent('day'),
            'month' => Assistant::spent('month'),
            'conversations' => AiConversation::latest('last_activity_at')->paginate(25),
            'led' => AiConversation::where('led_to_quote', true)->where('created_at', '>=', now()->startOfMonth())->count(),
            'status' => Assistant::publicStatus()['enabled'],
            'models' => config('elgarom.ai.models'),
        ]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'enabled' => 'nullable|boolean',
            'api_key' => ['nullable', 'string', 'max:300', 'regex:/^sk-ant-[A-Za-z0-9_\-]+$/'],
            'clear_key' => 'nullable|boolean',
            'model' => ['required', Rule::in(array_keys(config('elgarom.ai.models')))],
            'effort' => 'required|in:low,medium,high',
            'max_output_tokens' => 'required|integer|min:1024|max:16000',
            'max_messages_per_session' => 'required|integer|min:1|max:100',
            'max_requests_per_ip_hour' => 'required|integer|min:1|max:500',
            'daily_budget_usd' => 'required|numeric|min:0|max:1000',
            'monthly_budget_usd' => 'required|numeric|min:0|max:10000',
            'retention_days' => 'required|integer|min:1|max:365',
        ], ['api_key.regex' => 'Cheia API Anthropic începe cu „sk-ant-”.']);
        Setting::put('ai.enabled', $request->boolean('enabled'));
        if ($request->boolean('clear_key')) {
            Setting::forget('ai.api_key');
        } elseif (! empty($d['api_key'])) {
            Setting::put('ai.api_key', $d['api_key'], true);
        }
        foreach (['model', 'effort', 'max_output_tokens', 'max_messages_per_session', 'max_requests_per_ip_hour', 'daily_budget_usd', 'monthly_budget_usd', 'retention_days'] as $k) {
            Setting::put('ai.'.$k, is_numeric($d[$k]) ? $d[$k] + 0 : $d[$k]);
        }
        AuditLog::record('ai.setari', null, ['activ' => $request->boolean('enabled'), 'model' => $d['model'], 'cheie_noua' => ! empty($d['api_key'])]);

        return back()->with('status', 'Setările asistentului au fost salvate.');
    }

    public function test(Assistant $assistant)
    {
        if (! Assistant::configured()) {
            return back()->with('error', 'Salvați întâi cheia API.');
        }
        try {
            $text = $assistant->test();
        } catch (\Throwable $e) {
            return back()->with('error', 'Testul a eșuat: '.class_basename($e).': '.mb_substr($e->getMessage(), 0, 300));
        }
        AuditLog::record('ai.test');

        return back()->with('status', 'Conexiune reușită. Răspuns: '.$text);
    }

    public function show(AiConversation $conversation)
    {
        AuditLog::record('ai.conversatie_vizualizata', $conversation);

        return view('admin.ai.show', ['c' => $conversation->load('messages')]);
    }

    public function destroy(AiConversation $conversation)
    {
        $conversation->delete();
        AuditLog::record('ai.conversatie_stearsa', $conversation);

        return redirect()->route('admin.ai.index')->with('status', 'Conversația a fost ștearsă.');
    }

    public function purge()
    {
        $n = AiConversation::query()->delete();
        AuditLog::record('ai.toate_sterse', null, ['numar' => $n]);

        return back()->with('status', $n.' conversații au fost șterse.');
    }
}
