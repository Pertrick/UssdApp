<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Business\StoreBusinessRequest;
use App\Http\Requests\Business\StoreCACRequest;
use App\Http\Requests\Business\StoreDirectorRequest;
use Illuminate\Auth\Events\Registered;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class BusinessController extends Controller
{
    public function register()
    {
        return Inertia::render('Business/Registration');
    }

    public function store(StoreBusinessRequest $request)
    {
        $validated = $request->validated();
        
        DB::beginTransaction();
        try {
            // Create the user first
            $user = User::create([
                'name' => $validated['name'], 
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Create the business linked to the user
            $business = $user->businesses()->create([
                'business_name' => $validated['business_name'],
                'business_email' => $validated['business_email'],
                'phone' => $validated['phone'],
                'state' => $validated['state'],
                'city' => $validated['city'],
                'address' => $validated['address'],
                'registration_status' => 'cac_info_pending',
                'is_primary' => true,
            ]);

            Auth::login($user);
            
            DB::commit();
            
            return redirect()->route('business.cac-info');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Registration failed. Please try again.']);
        }
    }

    public function cacInfo()
    {
        // Check if user is authenticated, if not redirect to registration
        if (!Auth::check()) {
            return redirect()->route('business.register');
        }
        
        return Inertia::render('Business/CACVerification');
    }

    public function storeCacInfo(StoreCACRequest $request)
    {
        \Log::info('CAC Info submission received', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'files' => $request->allFiles()
        ]);

        $validated = $request->validated();

        \Log::info('CAC Info validation passed', [
            'validated_data' => $validated
        ]);

        // Don't update DB yet, return data to be stored in frontend state
        $cacData = [
            'cacNumber' => $validated['cacNumber'],
            'businessType' => $validated['businessType'],
            'registrationDate' => $validated['registrationDate'],
        ];

        if ($request->hasFile('cacDocument')) {
            try {
                // Store file temporarily in a separate location
                $path = $request->file('cacDocument')->store('temp/cac_documents', 'local');
                $cacData['tempCacDocumentPath'] = $path;
                
                \Log::info('CAC document stored', [
                    'path' => $path
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to store CAC document', [
                    'error' => $e->getMessage(),
                    'file' => $request->file('cacDocument')->getClientOriginalName()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload document. Please try again.'
                ], 500);
            }
        } else {
            \Log::warning('No CAC document found in request');
        }

        \Log::info('CAC Info submission successful', [
            'response_data' => $cacData
        ]);

        return response()->json([
            'success' => true,
            'data' => $cacData
        ]);
    }

    public function directorInfo()
    {
        // Check if user is authenticated, if not redirect to registration
        if (!Auth::check()) {
            return redirect()->route('business.register');
        }
        
        return Inertia::render('Business/DirectorInfo');
    }

    public function storeDirectorInfo(StoreDirectorRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();
        $business = $user->primaryBusiness();

        if (!$business) {
            return back()->withErrors(['error' => 'Business not found.']);
        }

        DB::beginTransaction();
        try {
            // Process CAC info from frontend state
            $cacData = json_decode($request->input('cacData'), true);
            
            // Move temporary CAC document to permanent location if exists
            $cacDocumentPath = null;
            if (!empty($cacData['tempCacDocumentPath'])) {
                $cacDocumentPath = str_replace('temp/', '', $cacData['tempCacDocumentPath']);
                Storage::move($cacData['tempCacDocumentPath'], $cacDocumentPath);
            }

            // Update CAC information
            $business->update([
                'cac_number' => $cacData['cacNumber'],
                'business_type' => $cacData['businessType'],
                'registration_date' => $cacData['registrationDate'],
                'cac_document_path' => $cacDocumentPath,
                'registration_status' => 'email_verification_pending'
            ]);

            // Process director's ID document
            $directorIdPath = null;
            if ($request->hasFile('idDocument')) {
                $directorIdPath = $request->file('idDocument')
                    ->store('director_documents', 'local');
            }

            // Update director information
            $business->update([
                'director_name' => $validated['directorName'],
                'director_phone' => $validated['directorPhone'],
                'director_email' => $validated['directorEmail'],
                'director_id_type' => $validated['idType'],
                'director_id_number' => $validated['idNumber'],
                'director_id_path' => $directorIdPath,
            ]);

            DB::commit();
            return redirect()->route('business.verify-email');
        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up any uploaded files
            if (isset($cacDocumentPath)) {
                Storage::delete($cacDocumentPath);
            }
            if (isset($directorIdPath)) {
                Storage::delete($directorIdPath);
            }
            return back()->withErrors(['error' => 'Failed to complete registration. Please try again.']);
        }
    }

    public function verifyEmail()
    {
        // Check if user is authenticated, if not redirect to registration
        if (!Auth::check()) {
            return redirect()->route('business.register');
        }
        
        return Inertia::render('Business/EmailVerification');
    }

    public function sendVerificationEmail()
    {
        $user = Auth::user();
        
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    public function skipEmailVerification()
    {
        $user = Auth::user();
        $business = $user->primaryBusiness();
        
        if ($business) {
            $business->update([
                'registration_status' => 'completed'
            ]);
        }
        
        $user->update([
            'email_verified_at' => now()
        ]);

        return redirect()->route('dashboard');
    }
}
