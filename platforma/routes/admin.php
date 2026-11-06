<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    // Authentication (throttled in the controller per account and per IP)
    Route::get('autentificare', [Admin\AuthController::class, 'showLogin'])->name('login');
    Route::post('autentificare', [Admin\AuthController::class, 'login'])->middleware('throttle:30,1')->name('login.post');

    Route::middleware('auth')->group(function () {
        Route::get('2fa', [Admin\AuthController::class, 'showChallenge'])->name('2fa.challenge');
        Route::post('2fa', [Admin\AuthController::class, 'challenge'])->middleware('throttle:20,1')->name('2fa.challenge.post');
        Route::get('2fa/configurare', [Admin\AuthController::class, 'showSetup'])->name('2fa.setup');
        Route::post('2fa/configurare', [Admin\AuthController::class, 'setup'])->middleware('throttle:20,1')->name('2fa.setup.post');
        Route::post('iesire', [Admin\AuthController::class, 'logout'])->name('logout');
    });

    Route::middleware(['auth', 'admin.auth'])->group(function () {
        Route::get('2fa/coduri', [Admin\AuthController::class, 'codes'])->name('2fa.codes');
        Route::get('confirmare', [Admin\AuthController::class, 'showConfirm'])->name('confirm');
        Route::post('confirmare', [Admin\AuthController::class, 'confirm'])->middleware('throttle:20,1')->name('confirm.post');

        Route::get('/', [Admin\DashboardController::class, 'index'])->middleware('admin.can:dashboard')->name('dashboard');

        // Own account
        Route::get('cont', [Admin\UsersController::class, 'account'])->name('account');
        Route::post('cont/parola', [Admin\UsersController::class, 'password'])->name('account.password');
        Route::post('cont/sesiuni', [Admin\UsersController::class, 'accountRevoke'])->name('account.revoke');
        Route::post('cont/coduri', [Admin\UsersController::class, 'recoveryCodes'])->middleware('admin.recent')->name('account.codes');
        Route::post('cont/2fa', [Admin\UsersController::class, 'reconfigure2fa'])->middleware('admin.recent')->name('account.2fa');

        // Pages & sections
        Route::middleware('admin.can:content')->group(function () {
            Route::get('pagini', [Admin\PagesController::class, 'index'])->name('pages.index');
            Route::get('pagini/noua', [Admin\PagesController::class, 'create'])->name('pages.create');
            Route::post('pagini', [Admin\PagesController::class, 'store'])->name('pages.store');
            Route::get('pagini/{page}', [Admin\PagesController::class, 'edit'])->name('pages.edit');
            Route::put('pagini/{page}', [Admin\PagesController::class, 'update'])->name('pages.update');
            Route::delete('pagini/{page}', [Admin\PagesController::class, 'destroy'])->name('pages.destroy');
            Route::post('pagini/{page}/publica', [Admin\PagesController::class, 'publish'])->name('pages.publish');
            Route::post('pagini/{page}/retrage', [Admin\PagesController::class, 'unpublish'])->name('pages.unpublish');
            Route::get('pagini/{page}/previzualizare', [Admin\PagesController::class, 'previewLink'])->name('pages.preview-link');
            Route::get('pagini/{page}/ciorna', [Admin\PagesController::class, 'preview'])->middleware('signed')->name('pages.preview');
            Route::post('pagini/{page}/sectiuni', [Admin\PagesController::class, 'addSection'])->name('pages.sections.add');
            Route::put('pagini/{page}/sectiuni/{section}', [Admin\PagesController::class, 'updateSection'])->name('pages.sections.update');
            Route::post('pagini/{page}/sectiuni/{section}/muta', [Admin\PagesController::class, 'moveSection'])->name('pages.sections.move');
            Route::delete('pagini/{page}/sectiuni/{section}', [Admin\PagesController::class, 'deleteSection'])->name('pages.sections.delete');
            Route::get('documente/{id}/fisier', [Admin\ResourceController::class, 'document'])->name('documents.file');
        });

        // Generic resources (permission checked per resource inside the controller)
        Route::get('continut/{key}', [Admin\ResourceController::class, 'index'])->name('res.index');
        Route::get('continut/{key}/nou', [Admin\ResourceController::class, 'create'])->name('res.create');
        Route::post('continut/{key}', [Admin\ResourceController::class, 'store'])->name('res.store');
        Route::get('continut/{key}/{id}', [Admin\ResourceController::class, 'edit'])->whereNumber('id')->name('res.edit');
        Route::put('continut/{key}/{id}', [Admin\ResourceController::class, 'update'])->whereNumber('id')->name('res.update');
        Route::delete('continut/{key}/{id}', [Admin\ResourceController::class, 'destroy'])->whereNumber('id')->name('res.destroy');
        Route::post('continut/{key}/{id}/muta', [Admin\ResourceController::class, 'move'])->whereNumber('id')->name('res.move');

        // Media
        Route::middleware('admin.can:media')->group(function () {
            Route::get('media', [Admin\MediaController::class, 'index'])->name('media.index');
            Route::post('media', [Admin\MediaController::class, 'store'])->name('media.store');
            Route::put('media/{media}', [Admin\MediaController::class, 'update'])->name('media.update');
            Route::post('media/{media}/regenereaza', [Admin\MediaController::class, 'regenerate'])->name('media.regenerate');
            Route::delete('media/{media}', [Admin\MediaController::class, 'destroy'])->name('media.destroy');
        });
        Route::get('media/json', [Admin\MediaController::class, 'json'])->name('media.json');

        // Settings groups (permission per group inside the controller)
        Route::get('setari/{key}', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('setari/{key}', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('smtp/test', [Admin\SettingsController::class, 'smtpTest'])->name('smtp.test');
        Route::post('smtp/reincearca/{id?}', [Admin\SettingsController::class, 'outboxRetry'])->name('outbox.retry');

        // Quotes
        Route::middleware('admin.can:quotes.view')->group(function () {
            Route::get('cereri', [Admin\QuotesController::class, 'index'])->name('quotes.index');
            Route::get('cereri/export', [Admin\QuotesController::class, 'export'])->middleware(['admin.can:quotes.export', 'admin.recent'])->name('quotes.export');
            Route::get('cereri/{quote}', [Admin\QuotesController::class, 'show'])->name('quotes.show');
            Route::put('cereri/{quote}', [Admin\QuotesController::class, 'update'])->middleware('admin.can:quotes.manage')->name('quotes.update');
            Route::post('cereri/{quote}/note', [Admin\QuotesController::class, 'note'])->middleware('admin.can:quotes.manage')->name('quotes.note');
            Route::get('cereri/{quote}/fisiere/{file}', [Admin\QuotesController::class, 'file'])->middleware('admin.can:quotes.files')->name('quotes.file');
            Route::delete('cereri/{quote}', [Admin\QuotesController::class, 'destroy'])->middleware(['admin.can:users', 'admin.recent'])->name('quotes.destroy');
        });

        // Users (admin only, sensitive)
        Route::middleware(['admin.can:users', 'admin.recent'])->group(function () {
            Route::get('utilizatori', [Admin\UsersController::class, 'index'])->name('users.index');
            Route::get('utilizatori/nou', [Admin\UsersController::class, 'create'])->name('users.create');
            Route::post('utilizatori', [Admin\UsersController::class, 'store'])->name('users.store');
            Route::get('utilizatori/{user}', [Admin\UsersController::class, 'edit'])->name('users.edit');
            Route::put('utilizatori/{user}', [Admin\UsersController::class, 'update'])->name('users.update');
            Route::delete('utilizatori/{user}', [Admin\UsersController::class, 'destroy'])->name('users.destroy');
            Route::post('utilizatori/{user}/parola', [Admin\UsersController::class, 'resetPassword'])->name('users.reset-password');
            Route::post('utilizatori/{user}/2fa', [Admin\UsersController::class, 'reset2fa'])->name('users.reset-2fa');
            Route::post('utilizatori/{user}/sesiuni', [Admin\UsersController::class, 'revokeSessions'])->name('users.revoke');
        });

        Route::middleware('admin.can:security')->group(function () {
            Route::get('securitate', [Admin\SecurityController::class, 'index'])->name('security.index');
            Route::post('securitate', [Admin\SecurityController::class, 'update'])->middleware('admin.recent')->name('security.update');
            Route::post('securitate/sesiuni/{id}', [Admin\SecurityController::class, 'revoke'])->name('security.revoke');
        });
        Route::get('jurnal', [Admin\AuditController::class, 'index'])->middleware('admin.can:audit')->name('audit.index');

        // Backups (admin, sensitive)
        Route::middleware(['admin.can:backups'])->group(function () {
            Route::get('backup', [Admin\BackupController::class, 'index'])->name('backups.index');
            Route::post('backup', [Admin\BackupController::class, 'store'])->name('backups.store');
            Route::post('backup/{backup}/verifica', [Admin\BackupController::class, 'verify'])->name('backups.verify');
            Route::get('backup/{backup}/descarca', [Admin\BackupController::class, 'download'])->middleware('admin.recent')->name('backups.download');
            Route::post('backup/{backup}/restaureaza', [Admin\BackupController::class, 'restore'])->middleware('admin.recent')->name('backups.restore');
            Route::post('backup/incarca', [Admin\BackupController::class, 'upload'])->middleware('admin.recent')->name('backups.upload');
            Route::delete('backup/{backup}', [Admin\BackupController::class, 'destroy'])->middleware('admin.recent')->name('backups.destroy');
        });

        // Updates (admin, sensitive; installation requires a fresh TOTP code)
        Route::middleware(['admin.can:updates'])->group(function () {
            Route::get('actualizari', [Admin\UpdateController::class, 'index'])->name('updates.index');
            Route::post('actualizari/incarca', [Admin\UpdateController::class, 'upload'])->name('updates.upload');
            Route::get('actualizari/verificare', [Admin\UpdateController::class, 'review'])->name('updates.review');
            Route::post('actualizari/instaleaza', [Admin\UpdateController::class, 'install'])->name('updates.install');
            Route::post('actualizari/anuleaza', [Admin\UpdateController::class, 'cancel'])->name('updates.cancel');
        });

        // Google & marketing
        Route::middleware('admin.can:google')->group(function () {
            Route::get('google', [Admin\GoogleController::class, 'index'])->name('google.index');
            Route::put('google', [Admin\GoogleController::class, 'update'])->name('google.update');
            Route::post('google/oauth', [Admin\GoogleController::class, 'connect'])->middleware('admin.recent')->name('google.connect');
            Route::get('google/oauth/callback', [Admin\GoogleController::class, 'callback'])->name('google.callback');
            Route::post('google/deconectare', [Admin\GoogleController::class, 'disconnect'])->name('google.disconnect');
            Route::post('google/sitemap', [Admin\GoogleController::class, 'submitSitemap'])->name('google.sitemap');
            Route::post('google/date', [Admin\GoogleController::class, 'refresh'])->name('google.refresh');
        });

        // AI assistant
        Route::middleware('admin.can:ai')->group(function () {
            Route::get('asistent', [Admin\AiController::class, 'index'])->name('ai.index');
            Route::put('asistent', [Admin\AiController::class, 'update'])->name('ai.update');
            Route::post('asistent/test', [Admin\AiController::class, 'test'])->name('ai.test');
            Route::get('asistent/conversatii/{conversation}', [Admin\AiController::class, 'show'])->name('ai.show');
            Route::delete('asistent/conversatii/{conversation}', [Admin\AiController::class, 'destroy'])->name('ai.destroy');
            Route::post('asistent/curata', [Admin\AiController::class, 'purge'])->middleware('admin.recent')->name('ai.purge');
        });
    });
});
