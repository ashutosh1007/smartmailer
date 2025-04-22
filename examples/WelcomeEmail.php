<?php

namespace Examples;

use SmartMailer\SmartMailable;
use Carbon\Carbon;

class WelcomeEmail extends SmartMailable
{
    protected $user;

    /**
     * Create a new message instance.
     *
     * @param array $user
     * @return void
     */
    public function __construct(array $user)
    {
        parent::__construct();
        $this->user = $user;

        // Example of customizing the queue
        $this->useQueue('welcome-emails')
             ->useConnection('redis')
             ->withDelay(Carbon::now()->addMinutes(5));
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.welcome')
                   ->subject('Welcome to Our Platform!')
                   ->with([
                       'name' => $this->user['name'],
                       'email' => $this->user['email']
                   ]);
    }
} 