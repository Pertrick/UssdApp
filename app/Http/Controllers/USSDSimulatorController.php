<?php

namespace App\Http\Controllers;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Services\USSDSessionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class USSDSimulatorController extends Controller
{
    protected $sessionService;

    public function __construct(USSDSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Show the simulator UI for a USSD service
     */
    public function showSimulator(USSD $ussd)
    {
        return Inertia::render('USSD/Simulator', [
            'ussd' => $ussd,
        ]);
    }

    /**
     * Start a new USSD session
     */
    public function startSession(Request $request, USSD $ussd)
    {
        $phoneNumber = $request->input('phone_number');
        $userAgent = $request->header('User-Agent');
        $ipAddress = $request->ip();

        $session = $this->sessionService->startSession($ussd, $phoneNumber, $userAgent, $ipAddress);

        return response()->json([
            'success' => true,
            'session_id' => $session->session_id,
            'menu_text' => $session->currentFlow->menu_text,
            'flow_title' => $session->currentFlow->title,
            'current_flow_id' => $session->current_flow_id,
            'options' => $session->currentFlow->options()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    /**
     * Process user input for a session
     */
    public function processInput(Request $request, USSD $ussd)
    {
        $sessionId = $request->input('session_id');
        $input = $request->input('input');

        $session = USSDSession::where('ussd_id', $ussd->id)
            ->where('session_id', $sessionId)
            ->where('status', 'active')
            ->firstOrFail();

        $result = $this->sessionService->processInput($session, $input);

        return response()->json($result);
    }

    /**
     * Get session logs for monitoring
     */
    public function getSessionLogs(Request $request, USSD $ussd)
    {
        $sessionId = $request->input('session_id');
        $logs = $ussd->sessions()->where('session_id', $sessionId)->first()?->logs()->orderBy('action_timestamp')->get();
        return response()->json(['logs' => $logs]);
    }

    /**
     * Get analytics for a USSD service
     */
    public function getAnalytics(Request $request, USSD $ussd)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $analytics = $this->sessionService->getSessionAnalytics($ussd, $startDate, $endDate);
        return response()->json($analytics);
    }
}
