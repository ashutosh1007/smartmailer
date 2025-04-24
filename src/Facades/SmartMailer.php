<?php

namespace SmartMailer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Provides static access to the SmartMailer service.
 *
 * @method static \SmartMailer\SmartMailer to(string|array $recipient)
 * @method static \SmartMailer\SmartMailer type(string $type)
 * @method static bool send(\Illuminate\Mail\Mailable|\SmartMailer\SmartMailable $mailable)
 * @method static bool sendMailable(\Illuminate\Mail\Mailable|\SmartMailer\SmartMailable $mailable)
 *
 * @see \SmartMailer\SmartMailer
 */
class SmartMailer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'smartmailer';
    }
}
