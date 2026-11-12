<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);
        $q = AuditLog::with('user')->latest('id');
        if ($a = $request->query('action')) {
            $q->where('action', 'like', addcslashes($a, '%_\\').'%');
        }
        if ($u = $request->query('user')) {
            $q->where('user_id', (int) $u);
        }
        if ($f = $request->query('from')) {
            $q->whereDate('created_at', '>=', $f);
        }
        if ($t = $request->query('to')) {
            $q->whereDate('created_at', '<=', $t);
        }

        return view('admin.system.audit', [
            'items' => $q->paginate(60)->withQueryString(),
            'users' => User::orderBy('name')->get(),
            'prefixes' => ['autentificare', '2fa', 'confirmare', 'cerere', 'pagina', 'sectiune', 'setari', 'media', 'utilizator', 'backup', 'actualizare', 'google', 'ai', 'smtp', 'securitate'],
        ]);
    }
}
