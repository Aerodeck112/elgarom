<?php

use App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::prefix('crm')->name('admin.')->group(function () {
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

        // CRM: companies, contacts, activities
        Route::middleware('admin.can:crm.view')->group(function () {
            Route::get('companii', [Admin\CompaniesController::class, 'index'])->name('companies.index');
            Route::get('companii/noua', [Admin\CompaniesController::class, 'create'])->middleware('admin.can:crm.manage')->name('companies.create');
            Route::get('companii/{company}', [Admin\CompaniesController::class, 'show'])->name('companies.show');
            Route::get('api/companii/{company}/contacte', [Admin\CompaniesController::class, 'contactsJson'])->name('companies.contacts');
        });
        Route::middleware('admin.can:crm.manage')->group(function () {
            Route::post('companii', [Admin\CompaniesController::class, 'store'])->name('companies.store');
            Route::get('companii/{company}/modifica', [Admin\CompaniesController::class, 'edit'])->name('companies.edit');
            Route::put('companii/{company}', [Admin\CompaniesController::class, 'update'])->name('companies.update');
            Route::post('companii/{company}/contacte', [Admin\CompaniesController::class, 'contactStore'])->name('contacts.store');
            Route::put('companii/{company}/contacte/{contact}', [Admin\CompaniesController::class, 'contactUpdate'])->name('contacts.update');
            Route::delete('companii/{company}/contacte/{contact}', [Admin\CompaniesController::class, 'contactDestroy'])->name('contacts.destroy');
            Route::post('companii/{company}/activitati', [Admin\CompaniesController::class, 'activityStore'])->name('activities.store');
            Route::post('companii/{company}/activitati/{activity}/gata', [Admin\CompaniesController::class, 'activityDone'])->name('activities.done');
        });
        Route::delete('companii/{company}', [Admin\CompaniesController::class, 'destroy'])->middleware('admin.can:crm.delete')->name('companies.destroy');

        // Requests for quotation
        Route::middleware('admin.can:rfq.view')->group(function () {
            Route::get('cereri', [Admin\RfqController::class, 'index'])->name('rfqs.index');
            Route::get('cereri/noua', [Admin\RfqController::class, 'create'])->middleware('admin.can:rfq.manage')->name('rfqs.create');
            Route::get('cereri/{rfq}', [Admin\RfqController::class, 'show'])->name('rfqs.show');
        });
        Route::middleware('admin.can:rfq.manage')->group(function () {
            Route::post('cereri', [Admin\RfqController::class, 'store'])->name('rfqs.store');
            Route::get('cereri/{rfq}/modifica', [Admin\RfqController::class, 'edit'])->name('rfqs.edit');
            Route::put('cereri/{rfq}', [Admin\RfqController::class, 'update'])->name('rfqs.update');
            Route::post('cereri/{rfq}/status', [Admin\RfqController::class, 'status'])->name('rfqs.status');
        });

        // Calculators (deterministic engine on validated Excel models)
        Route::middleware('admin.can:calc.use')->group(function () {
            Route::get('calculatoare/{area}', [Admin\CalculatorController::class, 'index'])->whereIn('area', ['piese', 'stante'])->name('calc.index');
            Route::get('calculator/{key}', [Admin\CalculatorController::class, 'create'])->name('calc.create');
            Route::post('calculator/{key}', [Admin\CalculatorController::class, 'store'])->name('calc.store');
            Route::post('api/calculator/{key}', [Admin\CalculatorController::class, 'evaluate'])->middleware('throttle:240,1')->name('calc.evaluate');
            Route::post('calculator/{key}/parametri', [Admin\CalculatorController::class, 'params'])->middleware('admin.can:rates.edit')->name('calc.params');
            Route::get('calcule/{calculation}', [Admin\CalculatorController::class, 'show'])->name('calc.show');
            Route::put('calcule/{calculation}', [Admin\CalculatorController::class, 'update'])->name('calc.update');
            Route::post('calcule/{calculation}/copie', [Admin\CalculatorController::class, 'duplicate'])->name('calc.duplicate');
            Route::delete('calcule/{calculation}', [Admin\CalculatorController::class, 'destroy'])->name('calc.destroy');

            Route::get('stante', [Admin\ToolProjectController::class, 'index'])->name('tools.index');
            Route::get('stante/nou', [Admin\ToolProjectController::class, 'create'])->name('tools.create');
            Route::post('stante', [Admin\ToolProjectController::class, 'store'])->name('tools.store');
            Route::get('stante/{tool}', [Admin\ToolProjectController::class, 'show'])->name('tools.show');
            Route::get('stante/{tool}/modifica', [Admin\ToolProjectController::class, 'edit'])->name('tools.edit');
            Route::put('stante/{tool}', [Admin\ToolProjectController::class, 'update'])->name('tools.update');
            Route::post('stante/{tool}/linii', [Admin\ToolProjectController::class, 'lineStore'])->name('tools.lines.store');
            Route::delete('stante/{tool}/linii/{line}', [Admin\ToolProjectController::class, 'lineDestroy'])->name('tools.lines.destroy');

            Route::get('zincare', [Admin\ZincController::class, 'index'])->name('zinc.index');
            Route::get('zincare/nou', [Admin\ZincController::class, 'create'])->name('zinc.create');
            Route::post('zincare', [Admin\ZincController::class, 'store'])->name('zinc.store');
            Route::get('zincare/{zinc}', [Admin\ZincController::class, 'edit'])->name('zinc.edit');
            Route::put('zincare/{zinc}', [Admin\ZincController::class, 'update'])->name('zinc.update');
        });
        Route::middleware('admin.can:formulas.manage')->group(function () {
            Route::get('modele-excel', [Admin\CalculatorController::class, 'models'])->name('models.index');
            Route::post('modele-excel', [Admin\CalculatorController::class, 'upload'])->middleware('admin.recent')->name('models.upload');
            Route::post('modele-excel/{version}/activeaza', [Admin\CalculatorController::class, 'activate'])->middleware('admin.recent')->name('models.activate');
        });

        // Quotes
        Route::middleware('admin.can:quotes.view')->group(function () {
            Route::get('oferte', [Admin\QuoteController::class, 'index'])->name('quotes.index');
            Route::get('oferte/noua', [Admin\QuoteController::class, 'create'])->middleware('admin.can:quotes.manage')->name('quotes.create');
            Route::get('oferte/{quote}', [Admin\QuoteController::class, 'show'])->name('quotes.show');
            Route::get('oferte/{quote}/compara/{other}', [Admin\QuoteController::class, 'compare'])->name('quotes.compare');
            Route::get('oferte/{quote}/pdf', [Admin\QuoteController::class, 'pdf'])->middleware('admin.can:export')->name('quotes.pdf');
            Route::get('oferte/{quote}/excel', [Admin\QuoteController::class, 'excel'])->middleware('admin.can:export')->name('quotes.excel');
        });
        Route::middleware('admin.can:quotes.manage')->group(function () {
            Route::post('oferte', [Admin\QuoteController::class, 'store'])->name('quotes.store');
            Route::get('oferte/{quote}/modifica', [Admin\QuoteController::class, 'edit'])->name('quotes.edit');
            Route::put('oferte/{quote}', [Admin\QuoteController::class, 'update'])->name('quotes.update');
            Route::post('oferte/{quote}/import', [Admin\QuoteController::class, 'import'])->name('quotes.import');
            Route::post('calcule/{calculation}/oferta', [Admin\QuoteController::class, 'fromCalculation'])->name('quotes.from-calc');
            Route::post('oferte/{quote}/aprobare', [Admin\QuoteController::class, 'submit'])->name('quotes.submit');
            Route::post('oferte/{quote}/status', [Admin\QuoteController::class, 'status'])->name('quotes.status');
            Route::post('oferte/{quote}/revizie', [Admin\QuoteController::class, 'revise'])->name('quotes.revise');
            Route::post('oferte/{quote}/copie', [Admin\QuoteController::class, 'duplicate'])->name('quotes.duplicate');
        });
        Route::delete('oferte/{quote}', [Admin\QuoteController::class, 'destroy'])->middleware(['admin.can:quotes.delete', 'admin.recent'])->name('quotes.destroy');
        Route::middleware('admin.can:quotes.approve')->group(function () {
            Route::post('oferte/{quote}/aproba', [Admin\QuoteController::class, 'approve'])->name('quotes.approve');
            Route::post('oferte/{quote}/returneaza', [Admin\QuoteController::class, 'sendBack'])->name('quotes.back');
        });

        // Library resources (permission checked per resource inside the controller)
        Route::get('biblioteca/{key}', [Admin\CrudController::class, 'index'])->name('res.index');
        Route::get('biblioteca/{key}/nou', [Admin\CrudController::class, 'create'])->name('res.create');
        Route::post('biblioteca/{key}', [Admin\CrudController::class, 'store'])->name('res.store');
        Route::get('biblioteca/{key}/{id}', [Admin\CrudController::class, 'edit'])->whereNumber('id')->name('res.edit');
        Route::put('biblioteca/{key}/{id}', [Admin\CrudController::class, 'update'])->whereNumber('id')->name('res.update');
        Route::delete('biblioteca/{key}/{id}', [Admin\CrudController::class, 'destroy'])->whereNumber('id')->name('res.destroy');

        // Group companies (public site cards + legal identity of quote issuers)
        Route::middleware('admin.can:site')->group(function () {
            Route::get('grup', [Admin\EntitiesController::class, 'index'])->name('entities.index');
            Route::get('grup/{entity}', [Admin\EntitiesController::class, 'edit'])->name('entities.edit');
            Route::put('grup/{entity}', [Admin\EntitiesController::class, 'update'])->name('entities.update');
            Route::post('grup/{entity}/confirma', [Admin\EntitiesController::class, 'confirm'])->middleware('admin.recent')->name('entities.confirm');
        });

        Route::get('tutorial', [Admin\DashboardController::class, 'tutorial'])->middleware('admin.can:dashboard')->name('tutorial');
        Route::post('noutati/vazut', [Admin\DashboardController::class, 'newsSeen'])->middleware('admin.can:dashboard')->name('news.seen');
        Route::get('tarife', [Admin\CalculatorController::class, 'rates'])->middleware('admin.can:calc.use')->name('rates');
        Route::post('curs-bnr', [Admin\SettingsController::class, 'fxRefresh'])->middleware(['admin.can:settings', 'throttle:6,1'])->name('fx.refresh');
        Route::middleware('admin.can:ai.use')->group(function () {
            Route::get('integrare-ai', [Admin\AiController::class, 'index'])->name('ai.index');
            Route::post('integrare-ai', [Admin\AiController::class, 'run'])->middleware('throttle:20,1')->name('ai.run');
        });
        Route::get('modul/{module}', [Admin\DashboardController::class, 'planned'])->name('planned');

        // Settings groups (permission per group inside the controller)
        Route::get('setari/{key}', [Admin\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('setari/{key}', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::post('smtp/test', [Admin\SettingsController::class, 'smtpTest'])->name('smtp.test');
        Route::post('smtp/reincearca/{id?}', [Admin\SettingsController::class, 'outboxRetry'])->name('outbox.retry');

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
    });
});
