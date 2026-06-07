<?php

use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the bootstrap/app.php file and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->name('api.')->group(function () {
    // users
    Route::get('user', [UserController::class, 'user']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']);

    // vaults
    Route::apiResource('vaults', VaultController::class);

    // tags (scoped under vault)
    Route::prefix('vaults/{vault}')->group(function () {
        Route::apiResource('tags', \App\Http\Controllers\Api\TagController::class)->except(['show']);
        Route::post('contacts/{contact}/tags', [\App\Http\Controllers\Api\ContactTagController::class, 'attach']);
        Route::delete('contacts/{contact}/tags/{tag}', [\App\Http\Controllers\Api\ContactTagController::class, 'detach']);
        Route::get('contacts', [\App\Http\Controllers\Api\ContactFilterController::class, 'index']);
    });


});
