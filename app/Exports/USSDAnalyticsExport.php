<?php

namespace App\Exports;

use App\Models\USSD;
use App\Models\USSDSession;
use App\Models\USSDSessionLog;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class USSDAnalyticsExport implements WithMultipleSheets
{
    protected $ussd;
    protected $startDate;
    protected $endDate;

    public function __construct(USSD $ussd, string $startDate, string $endDate)
    {
        $this->ussd = $ussd;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function sheets(): array
    {
        return [
            new USSDAnalyticsSessionsSheet($this->ussd, $this->startDate, $this->endDate),
            new USSDAnalyticsInteractionsSheet($this->ussd, $this->startDate, $this->endDate),
        ];
    }
}

class USSDAnalyticsSessionsSheet implements FromCollection, WithHeadings
{
    protected $ussd;
    protected $startDate;
    protected $endDate;

    public function __construct(USSD $ussd, string $startDate, string $endDate)
    {
        $this->ussd = $ussd;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        return USSDSession::where('ussd_id', $this->ussd->id)
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end)
            ->get([
                'session_id', 'phone_number', 'status', 'step_count', 'created_at', 'last_activity'
            ]);
    }

    public function headings(): array
    {
        return [
            'Session ID', 'Phone Number', 'Status', 'Step Count', 'Created At', 'Last Activity'
        ];
    }
}

class USSDAnalyticsInteractionsSheet implements FromCollection, WithHeadings
{
    protected $ussd;
    protected $startDate;
    protected $endDate;

    public function __construct(USSD $ussd, string $startDate, string $endDate)
    {
        $this->ussd = $ussd;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function collection()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        return USSDSessionLog::where('ussd_id', $this->ussd->id)
            ->where('action_timestamp', '>=', $start)
            ->where('action_timestamp', '<=', $end)
            ->get([
                'action_type', 'status', 'input_data', 'output_data', 'response_time', 'error_message', 'flow_id', 'action_timestamp'
            ]);
    }

    public function headings(): array
    {
        return [
            'Action Type', 'Status', 'Input Data', 'Output Data', 'Response Time', 'Error Message', 'Flow ID', 'Timestamp'
        ];
    }
} 