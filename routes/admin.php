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
    
    // Network Pricing Management (Dynamic: AT Cost + Markup)
    Route::post('/network-pricing', [AdminController::class, 'createNetworkPricing'])->name('network-pricing.create');
    Route::put('/network-pricing/{networkPricing}', [AdminController::class, 'updateNetworkPricing'])->name('network-pricing.update');
    
    // Business Discount Management Routes
    Route::post('/businesses/{business}/discount', [AdminController::class, 'updateBusinessDiscount'])->name('businesses.discount.update');
    
    // Billing Management Routes
    Route::get('/billing-change-requests', [AdminController::class, 'billingChangeRequests'])->name('billing-change-requests');
    Route::post('/billing-change-requests/{billingChangeRequest}/approve', [AdminController::class, 'approveBillingChangeRequest'])->name('billing-change-requests.approve');
    Route::post('/billing-change-requests/{billingChangeRequest}/reject', [AdminController::class, 'rejectBillingChangeRequest'])->name('billing-change-requests.reject');
    Route::post('/businesses/{business}/update-billing-method', [AdminController::class, 'updateBusinessBillingMethod'])->name('businesses.update-billing-method');
    Route::post('/businesses/{business}/toggle-account-suspension', [AdminController::class, 'toggleAccountSuspension'])->name('businesses.toggle-account-suspension');

    // Invoices Management Routes (postpaid billing)
    Route::get('/invoices', [AdminController::class, 'invoices'])->name('invoices');
    Route::post('/invoices/generate', [AdminController::class, 'generateInvoice'])->name('invoices.generate');
    Route::post('/invoices/{invoice}/mark-paid', [AdminController::class, 'markInvoicePaid'])->name('invoices.mark-paid');
    
    // Comprehensive Billing Report
    Route::get('/billing-report', [AdminController::class, 'billingReport'])->name('billing-report');
    Route::get('/billing-report/business/{business}/sessions', [AdminController::class, 'businessBillingSessions'])->name('billing-report.business.sessions');
    
    // Webhook Events Management
    Route::get('/webhook-events', [AdminController::class, 'webhookEvents'])->name('webhook-events');
    Route::get('/webhook-events/{webhookEvent}', [AdminController::class, 'showWebhookEvent'])->name('webhook-events.show');
});