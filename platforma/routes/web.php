<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SeoController;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/health', [SeoController::class, 'health'])->name('health');
Route::get('/documente/{document}/{slug?}', [SeoController::class, 'document'])->whereNumber('document')->name('document.download');

// Form endpoints are shared by all languages; the form sends its language in _locale.
Route::post('/solicita-oferta', [QuoteController::class, 'store'])->middleware('throttle:20,10')->name('quote.store');
Route::post('/mesaj', [QuoteController::class, 'message'])->middleware('throttle:20,10')->name('message.store');

require __DIR__.'/admin.php';
require __DIR__.'/assistant.php';

// Public pages per language. Prefixed languages are registered first so "/de" never reaches the Romanian catch-all.
$locales = config('elgarom.locales');
uksort($locales, fn ($a, $b) => ($a === Locales::default()) <=> ($b === Locales::default()));
foreach ($locales as $code => $l) {
    Route::middleware('locale:'.$code)->prefix($l['prefix'])->name($code === Locales::default() ? '' : $code.'.')->group(function () use ($l) {
        Route::get('/', [PageController::class, 'home'])->name('home');
        Route::get('/'.$l['segments']['sent'], [QuoteController::class, 'sent'])->name('quote.sent');
        Route::get('/'.$l['segments']['services'].'/{slug}', [PageController::class, 'service'])->where('slug', '[a-z0-9\-]+')->name('service');
        Route::get('/'.$l['segments']['projects'].'/{slug}', [PageController::class, 'project'])->where('slug', '[a-z0-9\-]+')->name('project');
        Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('page');
    });
}

Route::fallback([SeoController::class, 'fallback']);
