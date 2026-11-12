<?php

use Adobrovolsky97\Illuminar\Http\Controllers\IlluminarController;
use Adobrovolsky97\Illuminar\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

Route::group([
    'domain'     => config('illuminar.domain'),
    'prefix'     => trim((string)config('illuminar.path', 'illuminar'), '/'),
    'middleware' => array_merge((array)config('illuminar.middleware', ['web']), [Authorize::class]),
], function () {
    Route::get('', [IlluminarController::class, 'index'])->name('illuminar');
    Route::get('data', [IlluminarController::class, 'getData'])->name('illuminar.data');
    Route::delete('clear', [IlluminarController::class, 'clear'])->name('illuminar.clear');
    Route::get('assets/{file}', [IlluminarController::class, 'asset'])
        ->where('file', 'app\.js|app\.css|favicon\.ico')
        ->name('illuminar.asset');
});
