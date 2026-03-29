<?php

use App\Http\Controllers\OperacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/operacoes'));

Route::middleware('auth')->group(function () {

    Route::prefix('operacoes')->name('operacoes.')->group(function () {
        Route::get('/',                   [OperacaoController::class, 'index'])->name('index');
        Route::get('/exportar',           [OperacaoController::class, 'exportar'])->name('exportar');
        Route::get('/{operacao}',         [OperacaoController::class, 'show'])->name('show');
        Route::patch('/{operacao}/status',[OperacaoController::class, 'updateStatus'])->name('updateStatus');
    });
});

require __DIR__.'/auth.php';