<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiController;
use App\Http\Controllers\API\VpnConfigController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/login', [ApiController::class, 'login']);

// Server credential verification - protected by API key
Route::post('/verify-credentials', [VpnConfigController::class, 'verifyUserCredentials']);

// Protected routes - using api_key middleware
Route::middleware('api_key')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Server related routes
    Route::get('/servers', [ApiController::class, 'getServers']);
    Route::get('/servers/{id}', [ApiController::class, 'getServer']);
    Route::get('/servers/{id}/config', [ApiController::class, 'generateServerConfig']);

    // VPN configurations
    Route::get('/vpn/servers', [VpnConfigController::class, 'getAvailableServers']);
    Route::get('/vpn/servers/{serverId}/config', [VpnConfigController::class, 'getServerConfig']);
});
