<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use App\Services\BillingService;
use App\Models\Business;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;
    protected $billingService;

    public function __construct(PaymentService $paymentService, BillingService $billingService)
    {
        $this->paymentService = $paymentService;
        $this->billingService = $billingService;
    }

    /**
     * Show payment page
     */
    public function showPaymentPage(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $amount = $request->get('amount', 0);
        
        $availableGateways = $this->paymentService->getAvailableGateways();

        return Inertia::render('Payment/Index', [
            'amount' => $amount,
            'availableGateways' => $availableGateways,
            'business' => $business
        ]);
    }

    /**
     * Initialize payment
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:10000',
            'gateway' => 'required|string|in:stripe,paypal,flutterwave,paystack,manual'
        ]);

        $business = Auth::user()->primaryBusiness;
        $amount = $request->amount;
        $gateway = $request->gateway;

        $result = $this->paymentService->initializePayment($business, $amount, $gateway);

        if ($result['success']) {
            return response()->json($result);
        } else {
            return response()->json($result, 422);
        }
    }

    /**
     * Payment success callback
     */
    public function success(Request $request)
    {
        $reference = $request->get('reference');
        
        if (!$reference) {
            return redirect()->route('billing.dashboard')
                ->with('error', 'Invalid payment reference.');
        }

        $payment = Payment::where('reference', $reference)->first();
        
        if (!$payment) {
            return redirect()->route('billing.dashboard')
                ->with('error', 'Payment not found.');
        }

        // Process the payment callback
        $success = $this->paymentService->processPaymentCallback($reference, $request->all());

        if ($success) {
            return redirect()->route('billing.dashboard')
                ->with('success', "Payment successful! \${$payment->amount} has been added to your account.");
        } else {
            return redirect()->route('billing.dashboard')
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }

    /**
     * Payment cancel callback
     */
    public function cancel(Request $request)
    {
        $reference = $request->get('reference');
        
        if ($reference) {
            $payment = Payment::where('reference', $reference)->first();
            if ($payment) {
                $payment->update(['status' => 'cancelled']);
            }
        }

        return redirect()->route('billing.dashboard')
            ->with('info', 'Payment was cancelled.');
    }

    /**
     * Payment webhook (for automatic verification)
     */
    public function webhook(Request $request, $gateway)
    {
        try {
            $payload = $request->all();
            $signature = $request->header('X-Signature');
            
            // Verify webhook signature based on gateway
            $isValid = $this->verifyWebhookSignature($gateway, $payload, $signature);
            
            if (!$isValid) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Extract reference from payload
            $reference = $this->extractReferenceFromPayload($gateway, $payload);
            
            if (!$reference) {
                return response()->json(['error' => 'Reference not found'], 400);
            }

            // Process the payment
            $success = $this->paymentService->processPaymentCallback($reference, $payload);

            if ($success) {
                return response()->json(['success' => true]);
            } else {
                return response()->json(['error' => 'Payment processing failed'], 500);
            }

        } catch (\Exception $e) {
            Log::error('Payment webhook error', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Show payment history
     */
    public function history(Request $request)
    {
        $business = Auth::user()->primaryBusiness;
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        $status = $request->get('status');
        $gateway = $request->get('gateway');

        $query = Payment::where('business_id', $business->id);

        // Apply filters
        if ($status) {
            $query->where('status', $status);
        }
        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Get payment statistics
        $paymentStats = [
            'total' => Payment::where('business_id', $business->id)->count(),
            'completed' => Payment::where('business_id', $business->id)->where('status', 'completed')->count(),
            'pending' => Payment::where('business_id', $business->id)->where('status', 'pending')->count(),
            'failed' => Payment::where('business_id', $business->id)->whereIn('status', ['failed', 'cancelled'])->count(),
        ];

        return Inertia::render('Payment/History', [
            'payments' => $payments,
            'paymentStats' => $paymentStats
        ]);
    }

    /**
     * Show payment details
     */
    public function show(Payment $payment)
    {
        // Ensure user owns this payment
        if ($payment->business_id !== Auth::user()->primaryBusiness->id) {
            abort(403);
        }

        // Load the payment with business relationship
        $payment->load('business');

        return Inertia::render('Payment/Show', [
            'payment' => $payment
        ]);
    }

    /**
     * Manual payment verification (for admin)
     */
    public function verifyManualPayment(Request $request, Payment $payment)
    {
        // Only allow admin or payment owner
        if (!Auth::user()->isAdmin() && $payment->business_id !== Auth::user()->primaryBusiness->id) {
            abort(403);
        }

        $request->validate([
            'verified' => 'required|boolean'
        ]);

        if ($request->verified) {
            $success = $this->paymentService->processPaymentCallback(
                $payment->reference, 
                ['verified' => true]
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment verified successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed'
                ], 500);
            }
        } else {
            $payment->update(['status' => 'failed']);
            return response()->json([
                'success' => true,
                'message' => 'Payment marked as failed'
            ]);
        }
    }

    /**
     * Verify webhook signature
     */
    protected function verifyWebhookSignature(string $gateway, array $payload, string $signature): bool
    {
        switch ($gateway) {
            case 'stripe':
                return $this->verifyStripeWebhook($payload, $signature);
            case 'paypal':
                return $this->verifyPayPalWebhook($payload, $signature);
            case 'flutterwave':
                return $this->verifyFlutterwaveWebhook($payload, $signature);
            case 'paystack':
                return $this->verifyPaystackWebhook($payload, $signature);
            default:
                return false;
        }
    }

    /**
     * Extract reference from payload
     */
    protected function extractReferenceFromPayload(string $gateway, array $payload): ?string
    {
        switch ($gateway) {
            case 'stripe':
                return $payload['data']['object']['metadata']['reference'] ?? null;
            case 'paypal':
                return $payload['resource']['invoice_number'] ?? null;
            case 'flutterwave':
                return $payload['tx_ref'] ?? null;
            case 'paystack':
                return $payload['data']['reference'] ?? null;
            default:
                return null;
        }
    }

    // Webhook verification methods (simplified for now)
    protected function verifyStripeWebhook(array $payload, string $signature): bool
    {
        // Implement Stripe webhook verification
        return true; // Simplified for now
    }

    protected function verifyPayPalWebhook(array $payload, string $signature): bool
    {
        // Implement PayPal webhook verification
        return true; // Simplified for now
    }

    protected function verifyFlutterwaveWebhook(array $payload, string $signature): bool
    {
        // Implement Flutterwave webhook verification
        return true; // Simplified for now
    }

    protected function verifyPaystackWebhook(array $payload, string $signature): bool
    {
        // Implement Paystack webhook verification
        return true; // Simplified for now
    }
}

