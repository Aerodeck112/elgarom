<?php

namespace App\Http\Controllers;

use App\Models\AiConversation;
use App\Services\Ai\Assistant;
use App\Services\Ai\AssistantLimit;
use App\Services\Ai\AssistantUnavailable;
use App\Support\FormGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    private function fallback(string $reason, int $status = 503)
    {
        return response()->json([
            'error' => $reason,
            'fallback' => 'Puteți trimite oricând o cerere prin formularul de ofertă sau ne puteți suna.',
            'links' => [['label' => 'Formular de ofertă', 'url' => url('/solicita-oferta')], ['label' => 'Contact', 'url' => url('/contact')]],
        ], $status);
    }

    private function conversation(Request $request, bool $create): ?AiConversation
    {
        $sid = hash('sha256', $request->session()->getId());
        $pid = $request->session()->get('ai.conversation');
        $conv = $pid ? AiConversation::where('public_id', $pid)->first() : null;
        if ($conv && ! hash_equals($conv->session_hash, $sid) && ! hash_equals($conv->session_hash, (string) $request->session()->get('ai.session_hash'))) {
            $conv = null;
        }
        if (! $conv && $create) {
            $conv = AiConversation::create(['public_id' => (string) Str::uuid(), 'session_hash' => $sid, 'ip_hash' => FormGuard::ipHash($request), 'last_activity_at' => now()]);
            $request->session()->put('ai.conversation', $conv->public_id);
            $request->session()->put('ai.session_hash', $sid);
        }

        return $conv;
    }

    public function message(Request $request, Assistant $assistant)
    {
        if (! Assistant::publicStatus()['enabled']) {
            return $this->fallback('Asistentul AI nu este disponibil acum.');
        }
        $data = $request->validate(['message' => 'required|string|max:'.config('elgarom.ai.max_input_chars')], [
            'message.required' => 'Scrieți o întrebare.', 'message.max' => 'Mesajul este prea lung (max. '.config('elgarom.ai.max_input_chars').' caractere).',
        ]);
        $key = 'ai:'.FormGuard::ipHash($request);
        if (RateLimiter::tooManyAttempts($key, (int) setting('ai.max_requests_per_ip_hour', 30))) {
            return $this->fallback('Ați trimis multe mesaje într-un timp scurt. Încercați din nou mai târziu.', 429);
        }
        RateLimiter::hit($key, 3600);
        $conv = $this->conversation($request, true);
        try {
            $r = $assistant->reply($conv, trim($data['message']));
        } catch (AssistantLimit $e) {
            return $this->fallback($e->getMessage(), 429);
        } catch (AssistantUnavailable $e) {
            logger()->warning('Asistent AI indisponibil', ['motiv' => $e->getMessage(), 'cauza' => $e->getPrevious()?->getMessage()]);

            return $this->fallback('Asistentul AI nu poate răspunde acum.');
        }

        return response()->json($r);
    }

    public function history(Request $request)
    {
        $conv = $this->conversation($request, false);
        if (! $conv) {
            return response()->json(['messages' => []]);
        }

        return response()->json(['messages' => $conv->messages()->where('status', 'ok')->get()->map(fn ($m) => ['role' => $m->role, 'text' => $m->content])->values()]);
    }

    public function destroy(Request $request)
    {
        if ($conv = $this->conversation($request, false)) {
            $conv->delete();
        }
        $request->session()->forget(['ai.conversation', 'ai.session_hash']);

        return response()->json(['ok' => true]);
    }
}
