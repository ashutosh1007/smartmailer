<?php

namespace SmartMailer;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Mail\Mailable;
use SmartMailer\Services\MailLogger;
use Swift_SmtpTransport;
use Swift_Mailer;
use Exception;
use Illuminate\Support\Facades\Event;

class SmartMailer
{
    protected $to;
    protected $type;
    protected $config;
    protected $logger;
    protected $currentLog;

    public function __construct(MailLogger $logger)
    {
        $this->config = config('smart_mailer');
        $this->logger = $logger;
    }

    /**
     * Set the recipient of the email
     * 
     * @param string|array $recipient
     * @return $this
     */
    public function to($recipient)
    {
        $this->to = $recipient;
        return $this;
    }

    /**
     * Set the type of email (marketing, support, bulk, etc.)
     * 
     * @param string $type
     * @return $this
     */
    public function type(string $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Send the email using the configured strategy
     * 
     * @param Mailable $mailable
     * @return bool
     * @throws Exception
     */
    public function send($mailable)
    {
        // If it's a SmartMailable and should be queued, handle it appropriately
        if ($mailable instanceof SmartMailable && $mailable instanceof \Illuminate\Contracts\Queue\ShouldQueue) {
            return $this->queueMailable($mailable);
        }

        return $this->sendMailable($mailable);
    }

    /**
     * Queue the mailable for sending
     * 
     * @param SmartMailable $mailable
     * @return mixed
     */
    protected function queueMailable(SmartMailable $mailable)
    {
        // Set the email type and recipient on the mailable
        $mailable->withEmailType($this->type)
                ->to($this->to);

        // Use Laravel's queue system
        return Mail::queue($mailable);
    }

    /**
     * Send the mailable immediately
     * 
     * @param Mailable $mailable
     * @return bool
     * @throws Exception
     */
    public function sendMailable($mailable)
    {
        if (!$this->to) {
            throw new Exception('Recipient not specified. Use the to() method before sending.');
        }

        if (!$this->type) {
            throw new Exception('Email type not specified. Use the type() method before sending.');
        }

        // Get the from address configuration for this type
        $fromConfig = $this->getFromConfig();
        
        // Get all available connections for fallback
        $connections = $this->getAllAvailableConnections($fromConfig['mailer'] ?? null);
        $lastException = null;

        // Try each connection until one succeeds
        foreach ($connections as $connection) {
            try {
                return $this->attemptSend($mailable, $connection, $fromConfig);
            } catch (Exception $e) {
                $lastException = $e;
                // Log the failure for this connection
                if ($this->currentLog) {
                    $this->logger->logError($e, $this->currentLog);
                }
                continue;
            }
        }

        // If we get here, all connections failed
        report($lastException);
        throw new Exception('Failed to send email after trying all available SMTP connections: ' . $lastException->getMessage());
    }

    /**
     * Get all available SMTP connections for fallback
     * 
     * @param string|null $mailer
     * @return array
     */
    protected function getAllAvailableConnections(?string $mailer)
    {
        $connections = $this->config['connections'] ?? [];
        
        if (empty($connections)) {
            throw new Exception('No SMTP connections configured.');
        }

        // If specific mailer is requested and it's not 'rotate'
        if ($mailer && $mailer !== 'rotate') {
            // Put the requested mailer first in the array
            $orderedConnections = [];
            foreach ($connections as $connection) {
                if ($connection['name'] === $mailer) {
                    array_unshift($orderedConnections, $connection);
                } else {
                    $orderedConnections[] = $connection;
                }
            }
            return $orderedConnections;
        }

        // For rotation strategy, start with the selected connection based on strategy
        $strategy = $this->config['strategy'] ?? 'round_robin';
        $firstConnection = $strategy === 'random' 
            ? $this->getRandomConnection($connections)
            : $this->getRoundRobinConnection($connections);

        // Reorder connections to start with the selected one
        $orderedConnections = [$firstConnection];
        foreach ($connections as $connection) {
            if ($connection['name'] !== $firstConnection['name']) {
                $orderedConnections[] = $connection;
            }
        }

        return $orderedConnections;
    }

    /**
     * Attempt to send email using a specific connection
     * 
     * @param Mailable $mailable
     * @param array $connection
     * @param array $fromConfig
     * @return bool
     */
    protected function attemptSend($mailable, $connection, $fromConfig)
    {
        // Configure and set the mailer
        $mailer = $this->configureMailer($connection);
        Mail::setSwiftMailer($mailer);

        // Prepare logging data
        $logConfig = [
            'type' => $this->type,
            'smtp_host' => $connection['host'],
            'smtp_username' => $connection['username'],
            'connection_name' => $connection['name'],
            'mailable_data' => serialize($mailable),
            'metadata' => [
                'mailable_class' => get_class($mailable),
                'connection_used' => $connection['name'],
                'from_address' => $fromConfig['address'],
                'from_name' => $fromConfig['name'],
                'encryption' => $connection['encryption'],
                'port' => $connection['port'],
                'attempt_time' => now()->toDateTimeString(),
            ]
        ];

        // Use Laravel's events instead of beforeSending/afterSending
        Event::listen('Illuminate\Mail\Events\MessageSending', function ($event) use ($logConfig) {
            $this->currentLog = $this->logger->logSending($event->message, $logConfig);
        });

        Event::listen('Illuminate\Mail\Events\MessageSent', function ($event) {
            if ($this->currentLog) {
                $this->logger->logSent($event->message, $this->currentLog);
            }
        });

        try {
            // Build the mailable first
            $mailable->build();
            
            // Get the rendered view
            $renderer = app('mailer')->render(
                $mailable->view ?? $mailable->markdown,
                $mailable->buildViewData()
            );

            // Send the email using the rendered view
            Mail::send([], [], function ($message) use ($mailable, $fromConfig, $renderer) {
                $message->from($fromConfig['address'], $fromConfig['name']);
                $message->to($this->to);
                $message->subject($mailable->subject);

                // Set the HTML content
                $message->setBody($renderer, 'text/html');

                // Handle CC and BCC if they exist in the mailable
                if (method_exists($mailable, 'cc') && $mailable->cc) {
                    $message->cc($mailable->cc);
                }
                if (method_exists($mailable, 'bcc') && $mailable->bcc) {
                    $message->bcc($mailable->bcc);
                }

                // Add any custom headers or metadata to the message
                if (method_exists($mailable, 'getMetadata')) {
                    foreach ($mailable->getMetadata() as $key => $value) {
                        $message->getHeaders()->addTextHeader('X-Metadata-'.$key, $value);
                    }
                }
            });

            return true;
        } finally {
            // Clean up event listeners
            Event::forget('Illuminate\Mail\Events\MessageSending');
            Event::forget('Illuminate\Mail\Events\MessageSent');
        }
    }

    /**
     * Get the from address configuration for the current type
     * 
     * @return array
     * @throws Exception
     */
    protected function getFromConfig()
    {
        $fromConfig = $this->config['from_addresses'][$this->type] ?? null;

        if (!$fromConfig) {
            throw new Exception("No configuration found for email type: {$this->type}");
        }

        return $fromConfig;
    }

    /**
     * Select an SMTP connection based on the mailer setting and strategy
     * 
     * @param string|null $mailer
     * @return array
     * @throws Exception
     */
    protected function selectSmtpConnection(?string $mailer)
    {
        $connections = $this->config['connections'] ?? [];
        
        if (empty($connections)) {
            throw new Exception('No SMTP connections configured.');
        }

        // If specific mailer is requested and it's not 'rotate'
        if ($mailer && $mailer !== 'rotate') {
            foreach ($connections as $connection) {
                if ($connection['name'] === $mailer) {
                    return $connection;
                }
            }
            throw new Exception("Specified mailer '{$mailer}' not found in connections.");
        }

        // Handle rotation or random selection
        $strategy = $this->config['strategy'] ?? 'round_robin';

        if ($strategy === 'random') {
            return $this->getRandomConnection($connections);
        }

        return $this->getRoundRobinConnection($connections);
    }

    /**
     * Get a random SMTP connection
     * 
     * @param array $connections
     * @return array
     */
    protected function getRandomConnection(array $connections)
    {
        return $connections[array_rand($connections)];
    }

    /**
     * Get the next connection in round-robin fashion
     * 
     * @param array $connections
     * @return array
     */
    protected function getRoundRobinConnection(array $connections)
    {
        $cacheKey = 'smart_mailer_counter';
        $count = count($connections);
        
        // Get and increment the counter atomically
        $counter = Cache::increment($cacheKey);
        if ($counter === false) {
            Cache::put($cacheKey, 1);
            $counter = 1;
        }

        return $connections[($counter - 1) % $count];
    }

    /**
     * Configure the Swift Mailer with the selected connection
     * 
     * @param array $connection
     * @return Swift_Mailer
     */
    protected function configureMailer(array $connection)
    {
        $transport = new Swift_SmtpTransport(
            $connection['host'],
            $connection['port'],
            $connection['encryption']
        );

        $transport->setUsername($connection['username']);
        $transport->setPassword($connection['password']);

        return new Swift_Mailer($transport);
    }
}
