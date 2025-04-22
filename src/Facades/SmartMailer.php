<?php

namespace SmartMailer\Facades;

use Illuminate\Support\Facades\Facade;

class SmartMailer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'smartmailer';
    }
}
