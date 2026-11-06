<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/health', [SeoController::class, 'health'])->name('health');
Route::get('/documente/{document}/{slug?}', [SeoController::class, 'document'])->whereNumber('document')->name('document.download');

Route::post('/solicita-oferta', [QuoteController::class, 'store'])->middleware('throttle:20,10')->name('quote.store');
Route::get('/solicita-oferta/trimisa', [QuoteController::class, 'sent'])->name('quote.sent');
Route::post('/mesaj', [QuoteController::class, 'message'])->middleware('throttle:20,10')->name('message.store');

Route::get('/servicii/{slug}', [PageController::class, 'service'])->where('slug', '[a-z0-9\-]+')->name('service');
Route::get('/proiecte/{slug}', [PageController::class, 'project'])->where('slug', '[a-z0-9\-]+')->name('project');

require __DIR__.'/admin.php';
require __DIR__.'/assistant.php';

// CMS pages last, so explicit routes win.
Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('page');
Route::fallback([SeoController::class, 'fallback']);
