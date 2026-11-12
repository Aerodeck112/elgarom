<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');
Route::get('/confidentialitate', [SiteController::class, 'privacy'])->name('privacy');
Route::get('/sigla/{entity}', [SiteController::class, 'logo'])->name('entity.logo');
Route::get('/robots.txt', [SiteController::class, 'robots']);
Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap');
Route::get('/health', [SiteController::class, 'health'])->name('health');

require __DIR__.'/admin.php';
