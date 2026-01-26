<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\USSDController;
use App\Http\Controllers\USSDSimulatorController;
use App\Http\Controllers\ActivityController;
use Illuminate\Http\Request;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\DynamicFlowController;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'showAuth' => auth()->check()
    ]);
})->name('welcome');


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

// USSD Routes (Requires auth and verified business - these are for authenticated users with verified businesses)
Route::middleware(['auth', 'verified.business'])->group(function () {
    Route::get('/ussd', [USSDController::class, 'index'])->name('ussd.index');
    Route::get('/ussd/create', [USSDController::class, 'create'])->name('ussd.create');
    Route::post('/ussd', [USSDController::class, 'store'])->name('ussd.store');
    Route::get('/ussd/{ussd}', [USSDController::class, 'show'])->name('ussd.show');
    Route::get('/ussd/{ussd}/edit', [USSDController::class, 'edit'])->name('ussd.edit');
    Route::put('/ussd/{ussd}', [USSDController::class, 'update'])->name('ussd.update');
    Route::delete('/ussd/{ussd}', [USSDController::class, 'destroy'])->name('ussd.destroy');
    Route::patch('/ussd/{ussd}/toggle-status', [USSDController::class, 'toggleStatus'])->name('ussd.toggle-status');
    Route::get('/ussd/{ussd}/configure', [USSDController::class, 'configure'])->name('ussd.configure');
    
    // Integration Routes
    Route::get('/integration', [IntegrationController::class, 'index'])->name('integration.index');
    Route::get('/integration/marketplace', [IntegrationController::class, 'marketplace'])->name('integration.marketplace');
    Route::get('/integration/create', [IntegrationController::class, 'create'])->name('integration.create');
    Route::post('/integration', [IntegrationController::class, 'store'])->name('integration.store');
    Route::get('/integration/{apiConfig}', [IntegrationController::class, 'show'])->name('integration.show');
    Route::get('/integration/{apiConfig}/edit', [IntegrationController::class, 'edit'])->name('integration.edit');
    Route::put('/integration/{apiConfig}', [IntegrationController::class, 'update'])->name('integration.update');
    Route::delete('/integration/{apiConfig}', [IntegrationController::class, 'destroy'])->name('integration.destroy');
    Route::post('/integration/{apiConfig}/test', [IntegrationController::class, 'test'])->name('integration.test');
    Route::post('/integration/marketplace/add', [IntegrationController::class, 'addMarketplaceApi'])->name('integration.marketplace.add');
    
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
    
    // USSD Production Management Routes
    Route::post('/ussd/{ussd}/go-live', [USSDController::class, 'goLive'])->name('ussd.go-live');
    Route::post('/ussd/{ussd}/go-testing', [USSDController::class, 'goTesting'])->name('ussd.go-testing');
    Route::get('/ussd/{ussd}/production-status', [USSDController::class, 'getProductionStatus'])->name('ussd.production-status');
    Route::get('/ussd/{ussd}/environment', [USSDController::class, 'environment'])->name('ussd.environment');
    Route::get('/environment', [USSDController::class, 'environmentOverview'])->name('environment.overview');
    Route::get('/ussd/{ussd}/production', [USSDController::class, 'production'])->name('ussd.production');
    
    // Gateway and Webhook Configuration Routes
    Route::put('/ussd/{ussd}/configure-gateway', [USSDController::class, 'configureGateway'])->name('ussd.configure-gateway');
    Route::put('/ussd/{ussd}/configure-webhook', [USSDController::class, 'configureWebhook'])->name('ussd.configure-webhook');
    
    // Billing Routes (Requires verified business)
    Route::get('/billing', [BillingController::class, 'billingDashboard'])->name('billing.dashboard');
    Route::post('/billing/add-funds', [BillingController::class, 'addFunds'])->name('billing.add-funds');
    Route::get('/billing/summary', [BillingController::class, 'getSummary'])->name('billing.summary');
    Route::get('/billing/stats', [BillingController::class, 'getStats'])->name('billing.stats');
    Route::get('/billing/sessions', [BillingController::class, 'getSessionHistory'])->name('billing.sessions');
    Route::get('/billing/sessions/filtered', [BillingController::class, 'getFilteredSessions'])->name('billing.sessions.filtered');
    Route::post('/billing/add-test-funds', [BillingController::class, 'addTestFunds'])->name('billing.add-test-funds');
    Route::get('/billing/export', [BillingController::class, 'export'])->name('billing.export');
    
    // Billing Method Change Requests
    Route::post('/billing/request-method-change', [BillingController::class, 'requestBillingMethodChange'])->name('billing.request-method-change');
    Route::post('/billing/cancel-method-change-request', [BillingController::class, 'cancelBillingMethodChangeRequest'])->name('billing.cancel-method-change-request');
    Route::get('/billing/method-change-status', [BillingController::class, 'getBillingMethodChangeRequestStatus'])->name('billing.method-change-status');
    
    // Payment Routes (Requires verified business)
    Route::get('/payment', [PaymentController::class, 'showPaymentPage'])->name('payment.index');
    Route::post('/payment/initialize', [PaymentController::class, 'initialize'])->name('payment.initialize');
    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/cancel', [PaymentController::class, 'cancel'])->name('payment.cancel');
    Route::post('/payment/webhook/{gateway}', [PaymentController::class, 'webhook'])->name('payment.webhook');
    Route::get('/payment/history', [PaymentController::class, 'history'])->name('payment.history');
    Route::get('/payment/{payment}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{payment}/verify', [PaymentController::class, 'verifyManualPayment'])->name('payment.verify');
    
    // Analytics Routes (Requires verified business)
    Route::get('/analytics', [AnalyticsController::class, 'dashboard'])->name('analytics.dashboard');
    Route::get('/analytics/ussd/{ussd}', [AnalyticsController::class, 'ussdAnalytics'])->name('analytics.ussd');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    Route::get('/analytics/export/{ussd}', [AnalyticsController::class, 'export'])->name('analytics.export.ussd');
    
    // Activity Routes (Requires verified business)
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/recent', [ActivityController::class, 'getRecentActivities'])->name('activities.recent');
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

// CSRF Token Route
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

// Test route for flash messages
Route::get('/test-flash', function () {
    session()->flash('success', 'This is a test flash message!');
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('test.flash');



// Dynamic Flow Testing Routes
Route::prefix('dynamic-flow')->group(function () {
    Route::post('/handle', [DynamicFlowController::class, 'handle'])->name('dynamic.flow.handle');
    Route::post('/test-step', [DynamicFlowController::class, 'testStep'])->name('dynamic.flow.test.step');
});

// Dynamic Flow Management Routes
Route::middleware(['auth'])->group(function () {
    Route::prefix('ussd/{ussd}/dynamic-flow')->group(function () {
        Route::get('/builder', [DynamicFlowController::class, 'builder'])->name('ussd.dynamic-flow.builder');
        Route::post('/steps', [DynamicFlowController::class, 'storeStep'])->name('ussd.dynamic-flow.steps.store');
        Route::put('/steps/{step}', [DynamicFlowController::class, 'updateStep'])->name('ussd.dynamic-flow.steps.update');
        Route::delete('/steps/{step}', [DynamicFlowController::class, 'destroyStep'])->name('ussd.dynamic-flow.steps.destroy');
        Route::post('/configs', [DynamicFlowController::class, 'storeConfig'])->name('ussd.dynamic-flow.configs.store');
        Route::delete('/configs/{config}', [DynamicFlowController::class, 'destroyConfig'])->name('ussd.dynamic-flow.configs.destroy');
    });
});

// Cache Management Route (Admin only)
Route::post('/cache/clear', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        
        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully',
            'output' => 'Application cache, config cache, route cache, and view cache have been cleared.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to clear cache',
            'error' => $e->getMessage()
        ], 500);
    }
})->name('cache.clear');

require __DIR__ . '/auth.php';
require __DIR__ .'/admin.php';
