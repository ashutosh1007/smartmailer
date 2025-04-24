<?php
return [
    /*
    |--------------------------------------------------------------------------
    | From Address Configurations
    |--------------------------------------------------------------------------
    |
    | Configure different types of email senders with their respective
    | from addresses and mailer settings.
    |
    */
    'from_addresses' => [
        'marketing' => [
            'name' => 'Marketing Team',
            'address' => 'marketing@example.com',
            'mailer' => 'rotate' // Use rotation strategy for this type
        ],
        'support' => [
            'name' => 'Support Team',
            'address' => 'support@example.com',
            'mailer' => 'smtp2' // Use specific SMTP connection
        ],
        'bulk' => [
            'name' => 'Bulk Mailer',
            'address' => 'bulk@example.com',
            'mailer' => 'rotate' // Use rotation strategy for this type
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default queue settings for mail jobs.
    | These can be overridden per mailable using the provided methods.
    |
    */
    'queue' => [
        // Default queue connection to use (null means default connection)
        'connection' => null,

        // Default queue name to use (null means default queue)
        'name' => null,

        // Queue settings per email type
        'types' => [
            'marketing' => [
                'connection' => 'redis',
                'queue' => 'marketing-emails',
                'timeout' => 120,
            ],
            'bulk' => [
                'connection' => 'redis',
                'queue' => 'bulk-emails',
                'timeout' => 300,
            ],
            'support' => [
                'connection' => 'sync',
                'queue' => 'high',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rotation Strategy
    |--------------------------------------------------------------------------
    |
    | Define how SMTP connections should be rotated when using the 'rotate'
    | mailer setting. Available options: 'round_robin', 'random'
    |
    */
    'strategy' => 'round_robin',

    /*
    |--------------------------------------------------------------------------
    | SMTP Connections
    |--------------------------------------------------------------------------
    |
    | List of available SMTP connections. Each connection must have a unique
    | name that can be referenced in the from_addresses mailer setting.
    |
    */
    'connections' => [
        [
            'name' => 'smtp1',
            'host' => 'smtp1.mailserver.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'smtp1@example.com',
            'password' => 'secret1'
        ],
        [
            'name' => 'smtp2',
            'host' => 'smtp2.mailserver.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'smtp2@example.com',
            'password' => 'secret2'
        ],
        [
            'name' => 'smtp3',
            'host' => 'smtp3.mailserver.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'smtp3@example.com',
            'password' => 'secret3'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging for SmartMailer operations.
    | When enabled, logs will be written to the default Laravel log channel.
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
    | When enabled, records will be created in the 'mail_logs' and 'mail_errors' tables.
    |
    */
    'database_logging' => [
        'enabled' => env('SMARTMAILER_DB_LOGGING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure settings related to the SmartMailer dashboard.
    |
    */
    'dashboard' => [
        // The prefix for the dashboard routes.
        'route_prefix' => env('SMARTMAILER_ROUTE_PREFIX', 'smartmailer'),

        // The authorization gate to check before allowing access to the dashboard.
        // Set to `null` to disable authorization checks.
        // You must define this gate in your AuthServiceProvider.
        'gate' => env('SMARTMAILER_DASHBOARD_GATE', 'viewSmartMailerDashboard'),
    ],
];
