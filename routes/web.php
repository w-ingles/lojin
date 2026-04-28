<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Todas as rotas são capturadas aqui e direcionadas para a SPA Vue.
| O Vue Router assume o controle da navegação no cliente.
| Rotas de API devem ficar em routes/api.php.
*/

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
