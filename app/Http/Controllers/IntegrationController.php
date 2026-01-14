<?php

namespace App\Http\Controllers;

use App\Models\ExternalAPIConfiguration;
use App\Services\ExternalAPIService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class IntegrationController extends Controller
{
    protected $externalApiService;

    public function __construct(ExternalAPIService $externalApiService)
    {
        $this->externalApiService = $externalApiService;
    }

    /**
     * Show integrations dashboard
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's custom APIs
        $customApis = ExternalAPIConfiguration::where('user_id', $user->id)
            ->where('category', 'custom')
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get marketplace APIs (templates)
        $marketplaceApis = ExternalAPIConfiguration::where('category', 'marketplace')
            ->where('is_marketplace_template', true)
            ->active()
            ->verified()
            ->orderBy('marketplace_category')
            ->orderBy('name')
            ->get()
            ->groupBy('marketplace_category');

        // Get user's marketplace API instances
        $userMarketplaceApis = ExternalAPIConfiguration::where('user_id', $user->id)
            ->where('category', 'marketplace')
            ->where('is_marketplace_template', false)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Integration/Index', [
            'customApis' => $customApis,
            'marketplaceApis' => $marketplaceApis,
            'userMarketplaceApis' => $userMarketplaceApis,
            'stats' => $this->getIntegrationStats($user->id),
        ]);
    }

    /**
     * Show marketplace
     */
    public function marketplace()
    {
        $marketplaceApis = ExternalAPIConfiguration::where('category', 'marketplace')
            ->where('is_marketplace_template', true)
            ->active()
            ->verified()
            ->orderBy('marketplace_category')
            ->orderBy('name')
            ->get()
            ->groupBy('marketplace_category');

        return Inertia::render('Integration/Marketplace', [
            'marketplaceApis' => $marketplaceApis,
            'categories' => $this->getMarketplaceCategories(),
        ]);
    }

    /**
     * Show custom API builder
     */
    public function create()
    {
        return Inertia::render('Integration/Create', [
            'authTypes' => $this->getAuthTypes(),
            'httpMethods' => $this->getHttpMethods(),
            'operators' => $this->getOperators(),
        ]);
    }

    /**
     * Store new custom API
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'endpoint_url' => 'required|url',
            'method' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'timeout' => 'integer|min:5|max:120',
            'retry_attempts' => 'integer|min:0|max:5',
            'auth_type' => 'required|in:api_key,bearer_token,basic,oauth,none',
            'auth_config' => 'array',
            'headers' => 'array',
            'request_mapping' => 'array',
            'response_mapping' => 'array',
            'success_criteria' => 'array',
            'error_handling' => 'array',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['category'] = 'custom';
        $validated['is_active'] = true;
        $validated['test_status'] = 'pending';

        $apiConfig = ExternalAPIConfiguration::create($validated);

        return redirect()->route('integration.show', $apiConfig)
            ->with('success', 'API configuration created successfully!');
    }

    /**
     * Show API configuration details
     */
    public function show(ExternalAPIConfiguration $apiConfig)
    {
        // Ensure user owns this API or it's a marketplace template
        if ($apiConfig->user_id !== Auth::id() && !$apiConfig->is_marketplace_template) {
            abort(403);
        }

        return Inertia::render('Integration/Show', [
            'apiConfig' => $apiConfig->load('ussd'),
            'usageStats' => $this->getUsageStats($apiConfig),
            'recentLogs' => $this->getRecentLogs($apiConfig->id),
        ]);
    }

    /**
     * Show API configuration edit form
     */
    public function edit(ExternalAPIConfiguration $apiConfig)
    {
        // Ensure user owns this API
        if ($apiConfig->user_id !== Auth::id()) {
            abort(403);
        }

        return Inertia::render('Integration/Edit', [
            'apiConfig' => $apiConfig,
            'authTypes' => $this->getAuthTypes(),
            'httpMethods' => $this->getHttpMethods(),
            'operators' => $this->getOperators(),
        ]);
    }

    /**
     * Update API configuration
     */
    public function update(Request $request, ExternalAPIConfiguration $apiConfig)
    {
        // Ensure user owns this API
        if ($apiConfig->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'endpoint_url' => 'required|url',
            'method' => 'required|in:GET,POST,PUT,DELETE,PATCH',
            'timeout' => 'integer|min:5|max:120',
            'retry_attempts' => 'integer|min:0|max:5',
            'auth_type' => 'required|in:api_key,bearer_token,basic,oauth,none',
            'auth_config' => 'array',
            'headers' => 'array',
            'request_mapping' => 'array',
            'response_mapping' => 'array',
            'success_criteria' => 'array',
            'error_handling' => 'array',
        ]);

        $apiConfig->update($validated);

        return redirect()->route('integration.show', $apiConfig)
            ->with('success', 'API configuration updated successfully!');
    }

    /**
     * Delete API configuration
     */
    public function destroy(ExternalAPIConfiguration $apiConfig)
    {
        // Ensure user owns this API
        if ($apiConfig->user_id !== Auth::id()) {
            abort(403);
        }

        $apiConfig->delete();

        return redirect()->route('integration.index')
            ->with('success', 'API configuration deleted successfully!');
    }

    /**
     * Test API configuration
     */
    public function test(Request $request, ExternalAPIConfiguration $apiConfig)
    {
        // Ensure user owns this API or it's a marketplace template
        if ($apiConfig->user_id !== Auth::id() && !$apiConfig->is_marketplace_template) {
            abort(403);
        }

        // Get optional test data from request (for testing with custom values)
        $testData = $request->input('test_data', []);

        $result = $this->externalApiService->testApiConfiguration($apiConfig, $testData);

        return response()->json($result);
    }

    /**
     * Add marketplace API to user's account
     */
    public function addMarketplaceApi(Request $request)
    {
        $validated = $request->validate([
            'template_id' => 'required|exists:external_api_configurations,id',
            'auth_config' => 'required|array',
        ]);

        // Get the marketplace template
        $template = ExternalAPIConfiguration::findOrFail($validated['template_id']);
        
        if (!$template->is_marketplace_template) {
            abort(400, 'Invalid marketplace template');
        }

        // Create user's instance of the marketplace API
        $userApi = $template->replicate();
        $userApi->user_id = Auth::id();
        $userApi->is_marketplace_template = false;
        $userApi->auth_config = $validated['auth_config'];
        $userApi->test_status = 'pending';
        $userApi->save();

        return redirect()->route('integration.show', $userApi)
            ->with('success', 'Marketplace API added to your account! Please test the configuration.');
    }

    /**
     * Get integration statistics
     */
    private function getIntegrationStats(int $userId): array
    {
        $customApis = ExternalAPIConfiguration::where('user_id', $userId)
            ->where('category', 'custom')
            ->count();

        $marketplaceApis = ExternalAPIConfiguration::where('user_id', $userId)
            ->where('category', 'marketplace')
            ->where('is_marketplace_template', false)
            ->count();

        $totalCalls = ExternalAPIConfiguration::where('user_id', $userId)
            ->sum('total_calls');

        $successfulCalls = ExternalAPIConfiguration::where('user_id', $userId)
            ->sum('successful_calls');

        $successRate = $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100, 2) : 0;

        return [
            'custom_apis' => $customApis,
            'marketplace_apis' => $marketplaceApis,
            'total_calls' => $totalCalls,
            'successful_calls' => $successfulCalls,
            'success_rate' => $successRate,
        ];
    }

    /**
     * Get marketplace categories
     */
    private function getMarketplaceCategories(): array
    {
        return [
            'airtime' => [
                'name' => 'Airtime & Data',
                'description' => 'Mobile airtime and data bundle services',
                'icon' => 'phone',
            ],
            'banking' => [
                'name' => 'Banking & Finance',
                'description' => 'Bank transfers, balance checks, and financial services',
                'icon' => 'bank',
            ],
            'payment' => [
                'name' => 'Payment Gateways',
                'description' => 'Payment processing and gateway integrations',
                'icon' => 'credit-card',
            ],
            'utility' => [
                'name' => 'Utilities',
                'description' => 'Electricity, water, and other utility payments',
                'icon' => 'bolt',
            ],
        ];
    }

    /**
     * Get authentication types
     */
    private function getAuthTypes(): array
    {
        return [
            'api_key' => 'API Key',
            'bearer_token' => 'Bearer Token',
            'basic' => 'Basic Authentication',
            'oauth' => 'OAuth 2.0',
            'none' => 'No Authentication',
        ];
    }

    /**
     * Get HTTP methods
     */
    private function getHttpMethods(): array
    {
        return [
            'GET' => 'GET',
            'POST' => 'POST',
            'PUT' => 'PUT',
            'DELETE' => 'DELETE',
            'PATCH' => 'PATCH',
        ];
    }

    /**
     * Get operators for success criteria
     */
    private function getOperators(): array
    {
        return [
            'equals' => 'Equals',
            'not_equals' => 'Not Equals',
            'contains' => 'Contains',
            'greater_than' => 'Greater Than',
            'less_than' => 'Less Than',
            'exists' => 'Exists',
            'not_exists' => 'Not Exists',
        ];
    }

    /**
     * Get usage statistics for an API
     */
    private function getUsageStats(ExternalAPIConfiguration $apiConfig): array
    {
        return [
            'total_calls' => $apiConfig->total_calls,
            'successful_calls' => $apiConfig->successful_calls,
            'failed_calls' => $apiConfig->failed_calls,
            'success_rate' => $apiConfig->getSuccessRate(),
            'average_response_time' => $apiConfig->average_response_time,
            'last_tested' => $apiConfig->last_tested_at,
            'test_status' => $apiConfig->test_status,
        ];
    }

    /**
     * Get recent logs for an API
     */
    private function getRecentLogs(int $apiConfigId): array
    {
        // This would typically come from a logs table
        // For now, return empty array
        return [];
    }
}
