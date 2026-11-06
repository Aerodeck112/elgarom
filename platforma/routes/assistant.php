<?php

use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;

Route::prefix('asistent')->middleware('throttle:60,1')->group(function () {
    Route::post('mesaj', [AssistantController::class, 'message'])->name('assistant.message');
    Route::get('istoric', [AssistantController::class, 'history'])->name('assistant.history');
    Route::delete('conversatie', [AssistantController::class, 'destroy'])->name('assistant.destroy');
});
