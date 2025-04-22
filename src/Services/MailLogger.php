<?php

namespace SmartMailer\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use SmartMailer\Models\MailLog;
use Swift_Message;
use Exception;

class MailLogger
{
    protected $loggingEnabled;
    protected $logChannel;
    protected $dbLoggingEnabled;

    public function __construct()
    {
        $this->loggingEnabled = Config::get('smart_mailer.logging.enabled', false);
        $this->logChannel = Config::get('smart_mailer.logging.channel');
        $this->dbLoggingEnabled = Config::get('smart_mailer.database_logging.enabled', true);
    }

    /**
     * Log an email being sent
     *
     * @param Swift_Message $message
     * @param array $config
     * @return MailLog
     */
    public function logSending($message, array $config)
    {
        // Get the from address and name
        $fromAddresses = $message->getFrom() ?? [];
        $fromEmail = array_key_first($fromAddresses) ?? $config['from_email'] ?? 'unknown@example.com';
        $fromName = $fromAddresses[$fromEmail] ?? null;

        // Format the to addresses
        $toAddresses = $message->getTo() ?? [];
        $toEmailsArray = array_keys($toAddresses);

        $logData = [
            'message_id' => $message->getId(),
            'type' => $config['type'] ?? 'unknown',
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'to_email' => $toEmailsArray,
            'subject' => $message->getSubject() ?? 'No Subject',
            'smtp_host' => $config['smtp_host'] ?? 'N/A',
            'smtp_username' => $config['smtp_username'] ?? 'N/A',
            'connection_name' => $config['connection_name'] ?? 'N/A',
            'status' => 'sending',
            // 'mailable_data' => $config['mailable_data'] ?? null, // Maybe too large for file logs
            'metadata' => $config['metadata'] ?? null,
            'queued_at' => now(),
        ];

        if ($this->loggingEnabled) {
            $logContext = $logData; // Use prepared data for context
            unset($logContext['metadata']); // Avoid overly verbose logs, keep essentials
            Log::channel($this->logChannel)->info('SmartMailer: Sending email', $logContext);
        }

        // Create a MailLog instance (in memory)
        $mailLogInstance = new MailLog($logData);

        // Save to database only if enabled
        if ($this->dbLoggingEnabled) {
            $mailLogInstance->save();
        }

        // Return the instance (saved or unsaved)
        return $mailLogInstance;
    }

    /**
     * Log a successfully sent email
     *
     * @param Swift_Message $message
     * @param MailLog $log
     * @return void
     */
    public function logSent($message, MailLog $log)
    {
        if ($this->loggingEnabled) {
            Log::channel($this->logChannel)->info('SmartMailer: Email sent successfully', [
                'mail_log_id' => $log->id,
                'message_id' => $log->message_id,
                'subject' => $log->subject,
                'to_email' => $log->to_email,
            ]);
        }

        // Update database only if enabled and the log object exists
        if ($this->dbLoggingEnabled && $log->exists) { 
            $log->update([
                'status' => MailLog::STATUS_SENT,
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Log an error that occurred while sending
     *
     * @param Exception $error
     * @param MailLog $log
     * @return void
     */
    public function logError(Exception $error, MailLog $log)
    {
        if ($this->loggingEnabled) {
            Log::channel($this->logChannel)->error('SmartMailer: Failed to send email', [
                'mail_log_id' => $log->id,
                'message_id' => $log->message_id,
                'error_message' => $error->getMessage(),
                'error_code' => $error->getCode(),
                'smtp_host' => $log->smtp_host,
                'connection_name' => $log->connection_name,
            ]);
        }

        // Log detailed error to database only if enabled and the log object exists
        if ($this->dbLoggingEnabled && $log->exists) {
            $log->errors()->create([
                'error_message' => $error->getMessage(),
                'error_code' => $error->getCode(),
                'stack_trace' => $error->getTraceAsString(),
                'attempted_at' => now()
            ]);

            $log->update([
                'status' => MailLog::STATUS_FAILED
            ]);
        }
    }
} 