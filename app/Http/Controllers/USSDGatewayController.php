<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use App\Services\AfricasTalkingService;
use App\Models\WebhookEvent;

class USSDGatewayController extends Controller
{
    protected $africasTalkingService;

    public function __construct(AfricasTalkingService $africasTalkingService)
    {
        $this->africasTalkingService = $africasTalkingService;
    }

    /**
     * Handle incoming USSD requests from AfricasTalking
     * 
     * SECURITY: This endpoint is called by AfricasTalking gateway.
     * IP whitelisting and signature verification are implemented.
     */
    public function handleUSSD(Request $request)
    {
        try {
            // SECURITY: Validate webhook request (IP, signature, rate limit)
            $webhookSecurity = app(\App\Services\WebhookSecurityService::class);
            $validation = $webhookSecurity->validateWebhookRequest($request);
            
            if (!$validation['valid']) {
                Log::warning('Webhook validation failed', [
                    'reason' => $validation['message'],
                    'code' => $validation['code'],
                    'ip' => $request->ip(),
                ]);
                
                return response('END Unauthorized request.', 403, [
                    'Content-Type' => 'text/plain'
                ]);
            }
            // SECURITY: Validate and sanitize input
            $validated = $request->validate([
                'sessionId' => 'required|string|max:255',
                'serviceCode' => 'required|string|max:50|regex:/^[*#0-9]+$/',
                'phoneNumber' => 'required|string|max:20|regex:/^\+?[0-9]+$/',
                'text' => 'nullable|string|max:500'
            ]);
            
            // SECURITY: Use SanitizationService for consistent sanitization
            $sanitizationService = app(\App\Services\SanitizationService::class);
            $sessionId = $sanitizationService->sanitizeInput($validated['sessionId'], 'session_id');
            $serviceCode = $sanitizationService->sanitizeServiceCode($validated['serviceCode']);
            $phoneNumber = $sanitizationService->sanitizeInput($validated['phoneNumber'], 'input_phone');
            $text = isset($validated['text']) ? $sanitizationService->sanitizeInput($validated['text'], 'ussd_selection') : '';

            // Process the USSD request with sanitized data
            $response = $this->africasTalkingService->processUSSDRequest([
                'sessionId' => $sessionId,
                'serviceCode' => $serviceCode,
                'phoneNumber' => $phoneNumber,
                'text' => $text
            ]);

            // Format response for AfricasTalking
            $responseType = $response['response'] ?? 'END';
            $message = $response['message'] ?? 'Thank you for using our service.';
            $freeFlow = $response['freeFlow'] ?? 'FC';

            // Build the response string
            $responseString = $responseType . ' ' . $message;

            Log::info('USSD Response', [
                'sessionId' => $request->sessionId,
                'response' => $responseString,
                'freeFlow' => $freeFlow
            ]);

            // Return response in AfricasTalking format
            return response($responseString, 200, [
                'Content-Type' => 'text/plain',
                'freeflow' => $freeFlow
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('USSD Validation Error', [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);

            return response('END Invalid request format.', 400, [
                'Content-Type' => 'text/plain'
            ]);

        } catch (\Exception $e) {
            Log::error('USSD Gateway Error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response('END An error occurred. Please try again.', 500, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * Health check endpoint for AfricasTalking
     */
    public function healthCheck()
    {
        return response('OK', 200, [
            'Content-Type' => 'text/plain'
        ]);
    }

    /**
     * Test endpoint for development
     */
    public function testUSSD(Request $request)
    {
        // Only allow in development/testing
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $testData = [
            'sessionId' => $request->input('sessionId', 'test-session-' . time()),
            'serviceCode' => $request->input('serviceCode', '*123#'),
            'phoneNumber' => $request->input('phoneNumber', '+2348012345678'),
            'text' => $request->input('text', '')
        ];

        $response = $this->africasTalkingService->processUSSDRequest($testData);

        return response()->json([
            'request' => $testData,
            'response' => $response
        ]);
    }

    /**
     * Handle AfricasTalking Events (session end notifications)
     * 
     * AfricasTalking sends POST requests to this endpoint when a USSD session ends.
     * Events include: session duration, cost, status, and other metadata.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleEvents(Request $request)
    {
        $webhookEvent = null;
        
        try {
            // Store raw webhook event for auditing and debugging
            $webhookEvent = WebhookEvent::create([
                'event_type' => 'session_end',
                'source' => 'africastalking',
                'session_id' => $request->input('sessionId') ?? $request->input('session_id'),
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
                'ip_address' => $request->ip(),
                'processing_status' => 'pending',
            ]);

            // Log the event for debugging
            Log::info('AfricasTalking Event Received', [
                'webhook_event_id' => $webhookEvent->id,
                'payload' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Extract event data (AfricasTalking sends different fields)
            $sessionId = $request->input('sessionId') ?? $request->input('session_id');
            $phoneNumber = $request->input('phoneNumber') ?? $request->input('phone_number');
            $serviceCode = $request->input('serviceCode') ?? $request->input('service_code');
            $status = $request->input('status') ?? $request->input('sessionStatus');
            $duration = $request->input('duration') ?? $request->input('sessionDuration');
            $actualCost = $request->input('cost') ?? $request->input('sessionCost'); // Actual cost charged by AfricasTalking
            $network = $request->input('network') ?? $request->input('networkCode');
            $errorMessage = $request->input('errorMessage') ?? $request->input('error_message');

            // Find the session by AfricasTalking session ID
            if ($sessionId) {
                $session = \App\Models\USSDSession::where('session_id', $sessionId)->first();

                // Link webhook event to session if found
                if ($session && $webhookEvent) {
                    $webhookEvent->update(['ussd_session_id' => $session->id]);
                }

                if ($session) {
                    // Update session with event data
                    $updateData = [];

                    if ($status) {
                        // Map AfricasTalking status to our status
                        if (in_array(strtolower($status), ['completed', 'success'])) {
                            $updateData['status'] = 'completed';
                        } elseif (in_array(strtolower($status), ['failed', 'error', 'timeout'])) {
                            $updateData['status'] = 'error';
                        }
                    }

                    // Update end_time if session ended
                    if ($status && in_array(strtolower($status), ['completed', 'failed', 'error', 'timeout'])) {
                        $updateData['end_time'] = now();
                    }

                    if ($errorMessage) {
                        $updateData['error_message'] = $errorMessage;
                    }

                    if ($network) {
                        $gatewayCostService = app(\App\Services\GatewayCostService::class);
                        $updateData['network_provider'] = $gatewayCostService->normalizeNetworkName($network);
                    }

                    // Update gateway cost with ACTUAL cost from AfricasTalking (if provided)
                    if ($actualCost !== null && is_numeric($actualCost) && $actualCost >= 0) {
                        $gatewayCostService = app(\App\Services\GatewayCostService::class);
                        
                        // Convert cost to smallest unit (kobo for NGN)
                        // AfricasTalking usually sends cost in main currency (NGN)
                        $currency = $session->gateway_cost_currency ?? config('app.currency', 'NGN');
                        $costInSmallestUnit = $gatewayCostService->convertToSmallestUnit((float)$actualCost, $currency);
                        
                        // Update gateway cost with actual cost from AfricasTalking
                        $updateData['gateway_cost'] = $costInSmallestUnit;
                        
                        // Log if there's a discrepancy with our estimated cost
                        if ($session->gateway_cost && $session->gateway_cost !== $costInSmallestUnit) {
                            $estimatedCost = $gatewayCostService->convertFromSmallestUnit($session->gateway_cost, $currency);
                            Log::warning('Gateway cost discrepancy detected', [
                                'session_id' => $session->id,
                                'estimated_cost' => $estimatedCost,
                                'actual_cost' => $actualCost,
                                'difference' => $actualCost - $estimatedCost,
                            ]);
                        }
                    }

                    // Update session if we have data
                    if (!empty($updateData)) {
                        $session->update($updateData);
                    }

                    // Log the event with session context
                    Log::info('USSD Session Event Processed', [
                        'webhook_event_id' => $webhookEvent->id ?? null,
                        'session_id' => $session->id,
                        'ussd_session_id' => $sessionId,
                        'status' => $status,
                        'duration' => $duration,
                        'actual_cost_from_at' => $actualCost,
                        'network' => $network,
                    ]);

                    // Mark webhook event as processed
                    if ($webhookEvent) {
                        $webhookEvent->markAsProcessed();
                    }
                } else {
                    Log::warning('USSD Session not found for event', [
                        'webhook_event_id' => $webhookEvent->id ?? null,
                        'session_id' => $sessionId,
                        'phone_number' => $phoneNumber,
                    ]);

                    // Mark as processed even if session not found (event was received successfully)
                    if ($webhookEvent) {
                        $webhookEvent->markAsProcessed();
                    }
                }
            } else {
                // No session ID provided - mark as processed anyway
                if ($webhookEvent) {
                    $webhookEvent->markAsProcessed();
                }
            }

            // Always return 200 OK to AfricasTalking
            // They expect a successful response even if we can't process the event
            return response()->json([
                'status' => 'received',
                'message' => 'Event processed successfully'
            ], 200);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() . "\nTrace: " . $e->getTraceAsString();
            
            Log::error('AfricasTalking Event Processing Error', [
                'webhook_event_id' => $webhookEvent->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            // Mark webhook event as failed if it was created
            if ($webhookEvent) {
                $webhookEvent->markAsFailed($errorMessage);
            }

            // Still return 200 to prevent AfricasTalking from retrying
            // Log the error for manual investigation
            return response()->json([
                'status' => 'error',
                'message' => 'Event received but processing failed'
            ], 200);
        }
    }
}

