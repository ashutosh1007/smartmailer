# SmartMailer

A robust Laravel package for advanced email management with multiple SMTP server support, intelligent failover, and comprehensive monitoring.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/your-vendor/smart-mailer.svg)](https://packagist.org/packages/your-vendor/smart-mailer)
[![Total Downloads](https://img.shields.io/packagist/dt/your-vendor/smart-mailer.svg)](https://packagist.org/packages/your-vendor/smart-mailer)
[![License](https://img.shields.io/packagist/l/your-vendor/smart-mailer.svg)](https://packagist.org/packages/your-vendor/smart-mailer)

## Features

- 🔄 Multiple SMTP Server Management with Rotation Strategies
- 📧 Type-based Email Routing
- 📊 Comprehensive Dashboard
- 🔄 Automatic Failover
- 📝 Detailed Logging
- ⏱️ Queue Integration
- 📈 Real-time Statistics

## Installation

1. Install the package via Composer:
```bash
composer require your-vendor/smart-mailer
```

2. Publish the configuration file:
```bash
php artisan vendor:publish --provider="SmartMailer\SmartMailerServiceProvider" --tag="config"
```

3. Publish and run the migrations:
```bash
php artisan vendor:publish --provider="SmartMailer\SmartMailerServiceProvider" --tag="migrations"
php artisan migrate
```

4. Publish the views (optional):
```bash
php artisan vendor:publish --provider="SmartMailer\SmartMailerServiceProvider" --tag="views"
```

## Configuration

### Basic Configuration (config/smart_mailer.php)

```php
return [
    'from_addresses' => [
        'marketing' => [
            'name' => 'Marketing Team',
            'address' => 'marketing@example.com',
            'mailer' => 'rotate'
        ],
        'support' => [
            'name' => 'Support Team',
            'address' => 'support@example.com',
            'mailer' => 'smtp2'
        ]
    ],
    'connections' => [
        [
            'name' => 'smtp1',
            'host' => 'smtp1.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'user1@example.com',
            'password' => 'secret1'
        ],
        [
            'name' => 'smtp2',
            'host' => 'smtp2.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'user2@example.com',
            'password' => 'secret2'
        ]
    ],
    'strategy' => 'round_robin', // or 'random'

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable standard file logging (using Laravel's Log facade).
    |
    */
    'logging' => [
        'enabled' => env('SMARTMAILER_LOGGING_ENABLED', false),
        'channel' => env('SMARTMAILER_LOG_CHANNEL', null), // null uses default channel
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging email details to the database.
    |
    */
    'database_logging' => [
        'enabled' => env('SMARTMAILER_DB_LOGGING_ENABLED', true),
    ],

    'queue' => [
        // Default queue connection (null means default Laravel connection)
        'connection' => null,

        // Default queue name (null means default queue)
        'name' => null,

        // Queue settings per email type
        'types' => [
            'marketing' => [
                'connection' => 'redis',
                'queue' => 'marketing-emails',
                'timeout' => 120, // Optional job timeout
            ],
            'bulk' => [
                'connection' => 'redis',
                'queue' => 'bulk-emails',
                'timeout' => 300,
            ],
            'support' => [
                'connection' => 'sync', // Use 'sync' for immediate processing
                'queue' => 'high',
            ],
        ],
    ],
];
```

## Usage

### Basic Usage

```php
use SmartMailer\Facades\SmartMailer;

// This will automatically queue the WelcomeEmail if it implements ShouldQueue
SmartMailer::to('recipient@example.com')
    ->type('marketing')
    ->send(new WelcomeEmail($user));
```

### Creating a Mailable

By default, `SmartMailable` extends Laravel's `Mailable` and implements `Illuminate\Contracts\Queue\ShouldQueue`. This means emails created using `SmartMailable` will automatically be pushed to the queue when you call `SmartMailer::send()`. The specific queue connection and name will be determined by the `queue` configuration in `config/smart_mailer.php` based on the email `type`.

```php
use SmartMailer\SmartMailable;

class WelcomeEmail extends SmartMailable
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        // The view data and subject are defined here
        return $this->view('emails.welcome')
                   ->subject('Welcome to Our Platform!');
    }
}
```

### Overriding Queue Settings per Mailable

You can override the default queue settings defined in the configuration file directly within your `SmartMailable` class:

```php
class MarketingCampaignEmail extends SmartMailable
{
    public function __construct()
    {
        // Override default queue settings for this specific email
        $this->onQueue('special-marketing-queue')
             ->onConnection('sqs')
             ->delay(now()->addMinutes(10));
    }

    // ... build method ...
}
```

## SMTP Server Rotation

### Round-Robin Strategy
Evenly distributes emails across all configured SMTP servers:

```php
'strategy' => 'round_robin'
```

### Random Strategy
Randomly selects an SMTP server for each email:

```php
'strategy' => 'random'
```

### Per-Type Configuration
Configure specific SMTP servers for different email types:

```php
'from_addresses' => [
    'marketing' => [
        'mailer' => 'rotate'  // Uses rotation
    ],
    'support' => [
        'mailer' => 'smtp2'   // Uses specific server
    ]
]
```

## Dashboard

Access the dashboard at `/smartmailer` (configurable) to:
- Monitor email status
- View SMTP server health
- Check statistics
- Retry failed emails
- Search and filter logs

### Dashboard Features
- Real-time email status monitoring
- SMTP server health checks
- Success/failure statistics
- Detailed error logs
- Email retry functionality
- Advanced filtering options

## Error Handling

```php
try {
    SmartMailer::to($email)
        ->type('marketing')
        ->send($mailable);
} catch (Exception $e) {
    // Error is automatically logged
    Log::error('Email sending failed: ' . $e->getMessage());
}
```

## Events

SmartMailer dispatches several events you can listen for:

```php
SmartMailer\Events\EmailSent
SmartMailer\Events\EmailFailed
SmartMailer\Events\SmtpServerDown
```

## Advanced Usage

### Custom Metadata
```php
class CustomEmail extends SmartMailable
{
    public function getMetadata()
    {
        return [
            'campaign_id' => '12345',
            'template_version' => '2.0'
        ];
    }
}
```

### Failover Configuration
```php
'connections' => [
    [
        'name' => 'primary',
        'priority' => 1,
        // ... SMTP configuration
    ],
    [
        'name' => 'backup',
        'priority' => 2,
        // ... SMTP configuration
    ]
]
```

## Testing

```bash
composer test
```

## Security

If you discover any security-related issues, please email security@example.com instead of using the issue tracker.

## Credits

- [Your Name](https://github.com/yourusername)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

For support, please contact support@example.com or create an issue in our [issue tracker](https://github.com/your-vendor/smart-mailer/issues).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Roadmap

- [ ] Support for AWS SES
- [ ] Enhanced analytics
- [ ] API endpoints for external integration
- [ ] Template management system
- [ ] Spam score checking