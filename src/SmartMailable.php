<?php

namespace SmartMailer;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

abstract class SmartMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    /**
     * The email type for SmartMailer
     *
     * @var string
     */
    protected $emailType;

    /**
     * The recipient email address
     *
     * @var string|array
     */
    protected $recipientEmail;

    /**
     * Set the email type for SmartMailer
     *
     * @param string $type
     * @return $this
     */
    public function withEmailType(string $type)
    {
        $this->emailType = $type;
        return $this;
    }

    /**
     * Set the recipient email address
     *
     * @param string|array $email
     * @return $this
     */
    public function to($email)
    {
        $this->recipientEmail = $email;
        return $this;
    }

    /**
     * Get the email type
     *
     * @return string|null
     */
    public function getEmailType()
    {
        return $this->emailType;
    }

    /**
     * Get the recipient email
     *
     * @return string|array|null
     */
    public function getRecipientEmail()
    {
        return $this->recipientEmail;
    }

    /**
     * Render the mailable into a view.
     *
     * @return string
     */
    public function render()
    {
        return $this->build()->render();
    }
} 