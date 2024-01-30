<?php

use Adobrovolsky97\Illuminar\Http\Controllers\IlluminarController;

Route::group(['middleware' => ['web'], 'prefix' => 'illuminar'], function () {
    Route::get('', [IlluminarController::class, 'index'])->name('illuminar');
    Route::get('data', [IlluminarController::class, 'getData']);
    Route::delete('clear', [IlluminarController::class, 'clear']);
});
