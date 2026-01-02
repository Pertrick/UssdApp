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
    
    Route::post('/gateway', [USSDGatewayController::class, 'handleUSSD'])
        ->middleware('throttle:60,1');
    
    // Rate limit: 30 events per minute (events are sent once per session)
    Route::post('/events', [USSDGatewayController::class, 'handleEvents'])
        ->middleware('throttle:30,1');
    
    Route::get('/health', [USSDGatewayController::class, 'healthCheck'])
        ->middleware('throttle:120,1');
    
    Route::get('/test', [USSDGatewayController::class, 'testUSSD'])
        ->middleware('throttle:30,1');
});
