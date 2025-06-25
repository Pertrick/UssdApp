<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\USSDController;
use App\Http\Controllers\USSDSimulatorController;
use Illuminate\Http\Request;
use App\Http\Controllers\AnalyticsController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

// Test route to debug redirect issue
Route::get('/test-register', function () {
    return response()->json([
        'message' => 'Test route working',
        'auth_check' => auth()->check(),
        'user_id' => auth()->id(),
        'session_id' => session()->getId()
    ]);
})->name('test.register');

// Test route for USSD flow management
Route::get('/test-ussd-flows', function () {
    return response()->json([
        'message' => 'USSD Flow routes are accessible',
        'routes' => [
            'store_flow' => route('ussd.flows.store', ['ussd' => 1]),
            'update_flow' => route('ussd.flows.update', ['ussd' => 1, 'flow' => 1]),
            'delete_flow' => route('ussd.flows.destroy', ['ussd' => 1, 'flow' => 1]),
            'store_option' => route('ussd.flows.options.store', ['ussd' => 1, 'flow' => 1]),
            'update_option' => route('ussd.flows.options.update', ['ussd' => 1, 'flow' => 1, 'option' => 1]),
            'delete_option' => route('ussd.flows.options.destroy', ['ussd' => 1, 'flow' => 1, 'option' => 1]),
        ]
    ]);
})->name('test.ussd.flows');

// Test route for authentication and USSD access
Route::get('/test-ussd-auth', function () {
    return response()->json([
        'authenticated' => auth()->check(),
        'user_id' => auth()->id(),
        'ussd_count' => auth()->check() ? auth()->user()->ussds()->count() : 0,
        'first_ussd' => auth()->check() ? auth()->user()->ussds()->first() : null,
    ]);
})->middleware(['auth'])->name('test.ussd.auth');

// Simple test route for flow creation
Route::post('/test-create-flow', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Test route working',
        'data' => $request->all()
    ]);
})->middleware(['auth'])->name('test.create.flow');

Route::get('/dashboard', [Controller::class, 'dashboard'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Business Registration Routes (No middleware - accessible to everyone)
Route::get('/business/register', [BusinessController::class, 'register'])->name('business.register');
Route::post('/business/register', [BusinessController::class, 'store'])->name('business.store');

// Business Setup Routes (No auth middleware - allows smooth registration flow)
// CAC Information
Route::get('/business/cac-info', [BusinessController::class, 'cacInfo'])
    ->name('business.cac-info');

Route::post('/business/cac-info', [BusinessController::class, 'storeCacInfo'])
    ->name('business.store-cac-info');

// Director Information
Route::get('/business/director-info', [BusinessController::class, 'directorInfo'])
    ->name('business.director-info');

Route::post('/business/director-info', [BusinessController::class, 'storeDirectorInfo'])
    ->name('business.store-director-info');

// Email Verification (Optional - Last Step)
Route::get('/business/verify-email', [BusinessController::class, 'verifyEmail'])
    ->name('business.verify-email');

Route::post('/business/verify-email', [BusinessController::class, 'sendVerificationEmail'])
    ->name('business.verification.send');

Route::post('/business/skip-email-verification', [BusinessController::class, 'skipEmailVerification'])
    ->name('business.skip-email-verification');

// Business Verification Routes (Admin only)
Route::middleware(['auth'])->group(function () {
    Route::patch('/business/{business}/verify', [BusinessController::class, 'verifyBusiness'])
        ->name('business.verify');
    Route::patch('/business/{business}/unverify', [BusinessController::class, 'unverifyBusiness'])
        ->name('business.unverify');
});

// USSD Routes (Requires auth - these are for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/ussd', [USSDController::class, 'index'])->name('ussd.index');
    Route::get('/ussd/create', [USSDController::class, 'create'])->name('ussd.create');
    Route::post('/ussd', [USSDController::class, 'store'])->name('ussd.store');
    Route::get('/ussd/{ussd}', [USSDController::class, 'show'])->name('ussd.show');
    Route::get('/ussd/{ussd}/edit', [USSDController::class, 'edit'])->name('ussd.edit');
    Route::put('/ussd/{ussd}', [USSDController::class, 'update'])->name('ussd.update');
    Route::delete('/ussd/{ussd}', [USSDController::class, 'destroy'])->name('ussd.destroy');
    Route::patch('/ussd/{ussd}/toggle-status', [USSDController::class, 'toggleStatus'])->name('ussd.toggle-status');
    Route::get('/ussd/{ussd}/configure', [USSDController::class, 'configure'])->name('ussd.configure');
    
    // USSD Flow Management Routes
    Route::post('/ussd/{ussd}/flows', [USSDController::class, 'storeFlow'])->name('ussd.flows.store');
    Route::put('/ussd/{ussd}/flows/{flow}', [USSDController::class, 'updateFlow'])->name('ussd.flows.update');
    Route::delete('/ussd/{ussd}/flows/{flow}', [USSDController::class, 'destroyFlow'])->name('ussd.flows.destroy');
    Route::post('/ussd/{ussd}/flows/{flow}/options', [USSDController::class, 'storeFlowOption'])->name('ussd.flows.options.store');
    Route::put('/ussd/{ussd}/flows/{flow}/options/{option}', [USSDController::class, 'updateFlowOption'])->name('ussd.flows.options.update');
    Route::delete('/ussd/{ussd}/flows/{flow}/options/{option}', [USSDController::class, 'destroyFlowOption'])->name('ussd.flows.options.destroy');
    
    Route::get('/ussd/{ussd}/simulator', [USSDSimulatorController::class, 'showSimulator'])->name('ussd.simulator');
    Route::post('/ussd/{ussd}/simulator/start', [USSDSimulatorController::class, 'startSession'])->name('ussd.simulator.start');
    Route::post('/ussd/{ussd}/simulator/input', [USSDSimulatorController::class, 'processInput'])->name('ussd.simulator.input');
    Route::get('/ussd/{ussd}/simulator/logs', [USSDSimulatorController::class, 'getSessionLogs'])->name('ussd.simulator.logs');
    Route::get('/ussd/{ussd}/simulator/analytics', [USSDSimulatorController::class, 'getAnalytics'])->name('ussd.simulator.analytics');
    
    // Analytics Routes
    Route::get('/analytics', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('/analytics/ussd/{ussd}', [AnalyticsController::class, 'ussdAnalytics'])->name('analytics.ussd');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('/analytics/export/{ussd}', [AnalyticsController::class, 'export'])->name('analytics.export.ussd');
});

// Test route for CSRF token
Route::get('/test-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'user_authenticated' => auth()->check(),
        'user_id' => auth()->id(),
    ]);
})->middleware(['auth'])->name('test.csrf');

require __DIR__ . '/auth.php';
