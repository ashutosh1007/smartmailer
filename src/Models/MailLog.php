<?php

namespace SmartMailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailLog extends Model
{
    protected $fillable = [
        'message_id',
        'type',
        'from_email',
        'from_name',
        'to_email',
        'cc',
        'bcc',
        'subject',
        'smtp_host',
        'smtp_username',
        'connection_name',
        'status',
        'error_message',
        'metadata',
        'mailable_data',
        'queued_at',
        'sent_at',
    ];

    protected $casts = [
        'to_email' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    // Add status constants for better maintainability
    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    // Add validation rules
    public static $rules = [
        'type' => 'required|string',
        'from_email' => 'required|email',
        'to_email' => 'required',
        'subject' => 'required|string',
        'smtp_host' => 'required|string',
        'smtp_username' => 'required|string',
        'connection_name' => 'required|string',
    ];

    /**
     * Scope a query to only include emails of a specific type.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include emails with a specific status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include emails sent within a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSentBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }

    /**
     * Mark the email as sent.
     *
     * @return bool
     */
    public function markAsSent()
    {
        return $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark the email as failed with an error message.
     *
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed($errorMessage)
    {
        return $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get the error logs for this mail.
     */
    public function errors()
    {
        return $this->hasMany(MailError::class);
    }

    // Add scope for failed emails
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    // Add scope for sent emails
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Get formatted to email addresses
     *
     * @return string
     */
    public function getFormattedToEmailAttribute()
    {
        if (is_array($this->to_email)) {
            return implode(', ', $this->to_email);
        }
        return $this->to_email ?? '';
    }
} 