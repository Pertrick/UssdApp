<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\USSDGatewayController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// USSD Gateway Routes (No authentication required - called by AfricasTalking)
Route::prefix('ussd')->group(function () {
    // Main USSD endpoint - AfricasTalking will call this
    // Rate limit: 60 requests per minute per IP (AfricasTalking may send multiple requests per session)
    Route::post('/gateway', [USSDGatewayController::class, 'handleUSSD'])
        ->middleware('throttle:60,1');
    
    // Health check endpoint - higher rate limit for monitoring
    Route::get('/health', [USSDGatewayController::class, 'healthCheck'])
        ->middleware('throttle:120,1');
    
    // Test endpoint (development only)
    Route::get('/test', [USSDGatewayController::class, 'testUSSD'])
        ->middleware('throttle:30,1');
});
