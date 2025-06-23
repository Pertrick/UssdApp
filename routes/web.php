<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\USSDController;

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

// USSD Routes (Requires auth - these are for authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/ussd/create', [USSDController::class, 'create'])->name('ussd.create');
    Route::post('/ussd/create', [USSDController::class, 'store']);
    Route::get('/ussd/configure', [USSDController::class, 'configure'])->name('ussd.configure');
    Route::get('/ussd/simulator', [USSDController::class, 'simulator'])->name('ussd.simulator');
});

require __DIR__.'/auth.php';
