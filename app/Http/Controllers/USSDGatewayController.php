<?php

namespace App\Http\Controllers;

use App\Services\AfricasTalkingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class USSDGatewayController extends Controller
{
    protected $africasTalkingService;

    public function __construct(AfricasTalkingService $africasTalkingService)
    {
        $this->africasTalkingService = $africasTalkingService;
    }

    /**
     * Handle incoming USSD requests from AfricasTalking
     */
    public function handleUSSD(Request $request)
    {
        try {
            // Validate required fields
            $request->validate([
                'sessionId' => 'required|string',
                'serviceCode' => 'required|string',
                'phoneNumber' => 'required|string',
                'text' => 'nullable|string'
            ]);

            // Process the USSD request
            $response = $this->africasTalkingService->processUSSDRequest($request->all());

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
}

