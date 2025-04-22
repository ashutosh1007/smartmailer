<?php

namespace SmartMailer;

use Illuminate\Support\ServiceProvider;
use SmartMailer\Services\MailLogger;
use Illuminate\Support\Facades\Route;
use SmartMailer\Http\Middleware\HandleSmartMailerErrors;
use SmartMailer\Http\Controllers\MailLogController;

class SmartMailerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/smart_mailer.php', 'smart_mailer');

        // Register the mail logger
        $this->app->singleton(MailLogger::class, function ($app) {
            return new MailLogger();
        });

        // Register the main service
        $this->app->singleton('smartmailer', function ($app) {
            return new SmartMailer($app->make(MailLogger::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Add config validation
        $this->validateConfig();

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/smart_mailer.php' => config_path('smart_mailer.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Register routes BEFORE publishing views
        $this->registerRoutes();

        // Publish views
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/smartmailer'),
        ], 'views');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'smartmailer');
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix(config('smart_mailer.route_prefix', 'smartmailer'))
            ->name('smartmailer.')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/smartmailer.php');
            });
    }

    protected function validateConfig(): void
    {
        $config = $this->app['config']->get('smart_mailer', []);
        
        if (empty($config['connections'])) {
            throw new \Exception('SmartMailer: No SMTP connections configured.');
        }

        if (empty($config['from_addresses'])) {
            throw new \Exception('SmartMailer: No from addresses configured.');
        }
    }
}
