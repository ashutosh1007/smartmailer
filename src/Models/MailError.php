<?php

namespace SmartMailer\Models;

use Illuminate\Database\Eloquent\Model;

class MailError extends Model
{
    protected $fillable = [
        'mail_log_id',
        'error_message',
        'error_code',
        'stack_trace',
        'attempted_at'
    ];

    protected $casts = [
        'attempted_at' => 'datetime'
    ];

    /**
     * Get the mail log that owns this error.
     */
    public function mailLog()
    {
        return $this->belongsTo(MailLog::class);
    }
} 