<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Enums\UserRole;

// Admin Authentication Routes (No middleware - accessible to everyone)
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // Redirect admin root to dashboard or login
    Route::get('/', function () {
        if (\Illuminate\Support\Facades\Auth::check() && \Illuminate\Support\Facades\Auth::user()->hasRole(UserRole::ADMIN->value)) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('admin.login');
    })->name('home');
});

// Admin Routes (Protected by auth and admin middleware)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/businesses', [AdminController::class, 'businesses'])->name('businesses');
    Route::get('/businesses/{business}', [AdminController::class, 'showBusiness'])->name('businesses.show');
    Route::post('/businesses/{business}/review', [AdminController::class, 'startReview'])->name('businesses.review');
    Route::post('/businesses/{business}/approve', [AdminController::class, 'approveBusiness'])->name('businesses.approve');
    Route::post('/businesses/{business}/reject', [AdminController::class, 'rejectBusiness'])->name('businesses.reject');
    Route::post('/businesses/{business}/suspend', [AdminController::class, 'suspendBusiness'])->name('businesses.suspend');
    Route::get('/businesses/{business}/documents/{documentType}', [AdminController::class, 'downloadDocument'])->name('businesses.documents.download');
    
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::patch('/users/{user}/roles', [AdminController::class, 'updateUserRoles'])->name('users.roles');
    Route::patch('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus'])->name('users.toggle-status');
    
    Route::get('/analytics', [AdminController::class, 'analytics'])->name('analytics');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});