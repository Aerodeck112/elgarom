<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Backup;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    public function __construct(private BackupService $svc) {}

    public function index()
    {
        $free = @disk_free_space(storage_path());

        return view('admin.system.backups', ['items' => Backup::with('creator')->latest()->get(), 'free' => $free]);
    }

    public function store(Request $request)
    {
        try {
            $b = $this->svc->create('complet', $request->user()->id, (string) $request->input('notes'));
            $this->svc->prune((int) setting('backups.keep', 10));
            AuditLog::record('backup.creat', $b, ['marime' => $b->size]);
        } catch (\Throwable $e) {
            report($e);
            AuditLog::record('backup.esuat', null, ['eroare' => $e->getMessage()]);

            return back()->with('error', 'Backup-ul a eșuat: '.$e->getMessage());
        }

        return back()->with('status', 'Backup creat și verificat ('.number_format($b->size / 1048576, 1, ',', '').' MB).');
    }

    public function verify(Backup $backup)
    {
        try {
            $this->svc->verify($backup);
        } catch (\Throwable $e) {
            $backup->update(['verified_at' => null]);

            return back()->with('error', 'Verificarea a eșuat: '.$e->getMessage());
        }
        AuditLog::record('backup.verificat', $backup);

        return back()->with('status', 'Backup-ul este integru.');
    }

    public function download(Backup $backup)
    {
        abort_unless(is_file($backup->absolutePath()), 404);
        AuditLog::record('backup.descarcat', $backup);

        return response()->download($backup->absolutePath(), $backup->filename, ['Content-Type' => 'application/zip']);
    }

    public function upload(Request $request)
    {
        $request->validate(['archive' => 'required|file|mimes:zip|max:'.(1024 * 1024)]);
        $name = 'elgarom-incarcat-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.zip';
        $request->file('archive')->move(BackupService::dir(), $name);
        $b = Backup::create(['filename' => $name, 'type' => 'incarcat', 'status' => 'ok', 'size' => filesize(BackupService::dir().'/'.$name), 'sha256' => hash_file('sha256', BackupService::dir().'/'.$name), 'created_by' => $request->user()->id]);
        try {
            $this->svc->verify($b);
        } catch (\Throwable $e) {
            @unlink($b->absolutePath());
            $b->delete();

            return back()->with('error', 'Arhiva încărcată nu este un backup valid: '.$e->getMessage());
        }
        AuditLog::record('backup.incarcat', $b);

        return back()->with('status', 'Backup încărcat și verificat. Îl puteți restaura din listă.');
    }

    public function restore(Request $request, Backup $backup)
    {
        $request->validate(['confirm' => 'required|in:RESTAUREAZA'], ['confirm.in' => 'Scrieți RESTAUREAZA pentru confirmare.']);
        try {
            $safety = $this->svc->restore($backup, $request->user()->id);
        } catch (\Throwable $e) {
            report($e);
            AuditLog::record('backup.restaurare_esuata', $backup, ['eroare' => $e->getMessage()]);

            return back()->with('error', 'Restaurarea a eșuat: '.$e->getMessage().' Starea anterioară poate fi recuperată din backup-ul „pre_restaurare”.');
        }
        AuditLog::record('backup.restaurat', $backup, ['siguranta' => $safety->filename]);

        return redirect()->route('admin.backups.index')->with('status', 'Restaurare finalizată. Backup de siguranță: '.$safety->filename);
    }

    public function destroy(Backup $backup)
    {
        @unlink($backup->absolutePath());
        AuditLog::record('backup.sters', $backup, ['fisier' => $backup->filename]);
        $backup->delete();

        return back()->with('status', 'Backup șters.');
    }
}
