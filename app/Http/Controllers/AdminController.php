<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use App\Models\Role;
use App\Enums\UserRole;
use App\Enums\BusinessRegistrationStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Show admin dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::whereHas('roles', function($query) {
                $query->where('name', UserRole::USER->value);
            })->count(),
            'total_businesses' => Business::count(),
            'pending_approvals' => Business::whereIn('registration_status', [
                BusinessRegistrationStatus::COMPLETED_UNVERIFIED->value,
                BusinessRegistrationStatus::UNDER_REVIEW->value
            ])->count(),
            'verified_businesses' => Business::where('registration_status', BusinessRegistrationStatus::VERIFIED->value)->count(),
            'recent_registrations' => Business::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        $recentBusinesses = Business::with('user')
            ->latest()
            ->take(5)
            ->get();

        $pendingBusinesses = Business::with('user')
            ->whereIn('registration_status', [
                BusinessRegistrationStatus::COMPLETED_UNVERIFIED->value,
                BusinessRegistrationStatus::UNDER_REVIEW->value
            ])
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentBusinesses' => $recentBusinesses,
            'pendingBusinesses' => $pendingBusinesses,
        ]);
    }

    /**
     * Show all businesses for admin review
     */
    public function businesses(Request $request)
    {
        $query = Business::with('user');

        // Filter by verification status
        if ($request->has('status')) {
            switch ($request->status) {
                case 'pending':
                    $query->where('verified', false);
                    break;
                case 'verified':
                    $query->where('verified', true);
                    break;
                case 'all':
                    // No filter
                    break;
            }
        }

        // Search by business name or user name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                  ->orWhere('business_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $businesses = $query->latest()->paginate(15);

        return Inertia::render('Admin/Businesses', [
            'businesses' => $businesses,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show business details for admin review
     */
    public function showBusiness(Business $business)
    {
        $business->load('user');
        
        return Inertia::render('Admin/BusinessDetail', [
            'business' => $business,
        ]);
    }

    /**
     * Start reviewing a business
     */
    public function startReview(Business $business)
    {
        $business->update([
            'registration_status' => BusinessRegistrationStatus::UNDER_REVIEW
        ]);

        return back()->with('success', 'Business review started successfully!');
    }

    /**
     * Approve a business
     */
    public function approveBusiness(Request $request, Business $business)
    {
        $request->validate([
            'approval_notes' => 'nullable|string|max:500'
        ]);

        $business->update([
            'verified' => true,
            'verified_at' => now(),
            'registration_status' => BusinessRegistrationStatus::VERIFIED,
            'approval_notes' => $request->approval_notes
        ]);

        return back()->with('success', 'Business approved successfully!');
    }

    /**
     * Reject a business
     */
    public function rejectBusiness(Request $request, Business $business)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $business->update([
            'verified' => false,
            'verified_at' => null,
            'registration_status' => BusinessRegistrationStatus::REJECTED,
            'rejection_reason' => $request->rejection_reason
        ]);

        return back()->with('success', 'Business rejected successfully!');
    }

    /**
     * Suspend a business
     */
    public function suspendBusiness(Request $request, Business $business)
    {
        $request->validate([
            'suspension_reason' => 'required|string|max:500'
        ]);

        $business->update([
            'registration_status' => BusinessRegistrationStatus::SUSPENDED,
            'suspension_reason' => $request->suspension_reason
        ]);

        return back()->with('success', 'Business suspended successfully!');
    }

    /**
     * Show all users for admin management
     */
    public function users(Request $request)
    {
        $query = User::with(['businesses', 'roles']);

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);
        $roles = Role::all();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Update user roles
     */
    public function updateUserRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user->roles()->sync($request->roles);

        return back()->with('success', 'User roles updated successfully!');
    }

    /**
     * Toggle user status (enable/disable)
     */
    public function toggleUserStatus(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'enabled' : 'disabled';
        return back()->with('success', "User {$status} successfully!");
    }

    /**
     * Show admin settings
     */
    public function settings()
    {
        $roles = Role::all();
        
        return Inertia::render('Admin/Settings', [
            'roles' => $roles,
        ]);
    }

    /**
     * Get business documents for download
     */
    public function downloadDocument(Business $business, $documentType)
    {
        $path = null;
        $filename = null;

        switch ($documentType) {
            case 'cac':
                $path = $business->cac_document_path;
                $filename = "CAC_Document_{$business->business_name}.pdf";
                break;
            case 'director_id':
                $path = $business->director_id_path;
                $filename = "Director_ID_{$business->business_name}.pdf";
                break;
            default:
                abort(404);
        }

        if (!$path || !Storage::exists($path)) {
            abort(404, 'Document not found');
        }

        return Storage::download($path, $filename);
    }

    /**
     * Get admin analytics
     */
    public function analytics()
    {
        $monthlyRegistrations = Business::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $verificationStats = [
            'total' => Business::count(),
            'verified' => Business::where('verified', true)->count(),
            'pending' => Business::where('verified', false)->count(),
            'rejected' => Business::where('registration_status', 'rejected')->count(),
        ];

        $recentActivity = Business::with('user')
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Admin/Analytics', [
            'monthlyRegistrations' => $monthlyRegistrations,
            'verificationStats' => $verificationStats,
            'recentActivity' => $recentActivity,
        ]);
    }
}
