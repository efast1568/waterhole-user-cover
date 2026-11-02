<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Subarist\WaterholeUserCover\Http\Controllers\UserCoverController;

Route::middleware('web')->group(function () {
    Route::get('users/{user}/cover/edit', [UserCoverController::class, 'editor'])
        ->middleware([
            'auth',
            \Waterhole\Http\Middleware\Localize::class,
        ])
        ->name('users.cover.edit');

    Route::post('users/{user}/cover', [UserCoverController::class, 'upload'])
        ->name('users.cover.upload');

    Route::patch('users/{user}/cover/position', [UserCoverController::class, 'position'])
        ->name('users.cover.position');

    Route::delete('users/{user}/cover', [UserCoverController::class, 'remove'])
        ->name('users.cover.remove');
});
