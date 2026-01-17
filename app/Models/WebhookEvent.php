<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $table = 'webhook_events';

    protected $fillable = [
        'event_type',
        'source',
        'session_id',
        'ussd_session_id',
        'payload',
        'headers',
        'ip_address',
        'processing_status',
        'processing_error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
        'processed_at' => 'datetime',
    ];

    /**
     * Get the USSD session associated with this event
     */
    public function ussdSession(): BelongsTo
    {
        return $this->belongsTo(USSDSession::class, 'ussd_session_id');
    }

    /**
     * Mark event as processed
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'processing_status' => 'processed',
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark event as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'processing_status' => 'failed',
            'processing_error' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Scope to filter by source
     */
    public function scopeFromSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to filter by event type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Scope to filter by processing status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('processing_status', $status);
    }

    /**
     * Scope to filter unprocessed events
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('processing_status', 'pending');
    }
}
