<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\BusinessRegistrationStatus;
use Illuminate\Support\Facades\Auth;

class VerifiedBusinessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user has any businesses
        $primaryBusiness = $user->businesses()->primary()->first();
        
        if (!$primaryBusiness) {
            return redirect()->route('business.register')->with('error', 'You need to register a business before accessing USSD services.');
        }

        // Check business registration status
        $status = $primaryBusiness->registration_status;

        // Allow only verified businesses (temporarily allowing all for testing)
        if (!$status->isVerified()) {
            $message = match($status) {
                BusinessRegistrationStatus::EMAIL_VERIFICATION_PENDING => 'Please complete email verification for your business.',
                BusinessRegistrationStatus::CAC_INFO_PENDING => 'Please complete CAC information for your business.',
                BusinessRegistrationStatus::DIRECTOR_INFO_PENDING => 'Please complete director information for your business.',
                BusinessRegistrationStatus::COMPLETED_UNVERIFIED => 'Your business is pending admin approval. You cannot access USSD services until approved.',
                BusinessRegistrationStatus::UNDER_REVIEW => 'Your business is currently under review. You cannot access USSD services until approved.',
                BusinessRegistrationStatus::REJECTED => 'Your business has been rejected. Please contact support for assistance.',
                BusinessRegistrationStatus::SUSPENDED => 'Your business has been suspended. Please contact support for assistance.',
                default => 'Your business is not verified. Please complete the verification process.'
            };

            // Temporarily allow access for testing - remove this in production
            // return redirect()->route('dashboard')->with('error', $message);
        }

        return $next($request);
    }
}
