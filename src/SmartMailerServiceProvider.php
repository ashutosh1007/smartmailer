<?php

namespace SmartMailer;

use Illuminate\Support\ServiceProvider;
use SmartMailer\Services\MailLogger;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
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
        // Register the dashboard authorization gate
        $this->registerDashboardGate();

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
        $routePrefix = config('smart_mailer.dashboard.route_prefix', 'smartmailer');
        $authMiddleware = $this->getRouteMiddleware();

        Route::middleware($authMiddleware)
            ->prefix($routePrefix)
            ->name('smartmailer.')
            ->group(function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/smartmailer.php');
            });
    }

    /**
     * Define the SmartMailer dashboard authorization gate.
     */
    protected function registerDashboardGate(): void
    {
        $gateName = config('smart_mailer.dashboard.gate');

        if ($gateName) {
             Gate::define($gateName, function ($user = null) {
                // Basic check: ensure user is logged in.
                // You should customize this logic in your AuthServiceProvider
                // for more specific checks (e.g., roles, permissions).
                return $user !== null;
            });
        }
    }

     /**
     * Get the middleware group for the dashboard routes.
     */
    protected function getRouteMiddleware(): array
    {
        $middleware = ['web', 'auth']; // Default middleware
        $gateName = config('smart_mailer.dashboard.gate');

        if ($gateName) {
            $middleware[] = 'can:'.$gateName;
        }

        return $middleware;
    }

    protected function validateConfig(): void
    {
        $config = $this->app['config']->get('smart_mailer', []);

        if (!isset($config['connections']) || !is_array($config['connections']) || empty($config['connections'])) {
            throw new \InvalidArgumentException('SmartMailer: Missing or invalid `connections` array in configuration.');
        }

        $requiredConnectionKeys = ['name', 'host', 'port', 'encryption', 'username', 'password'];
        foreach ($config['connections'] as $index => $connection) {
            if (!is_array($connection)) {
                throw new \InvalidArgumentException("SmartMailer: Connection at index {$index} must be an array.");
            }
            foreach ($requiredConnectionKeys as $key) {
                if (!isset($connection[$key])) {
                    throw new \InvalidArgumentException("SmartMailer: Missing key '{$key}' in connection configuration at index {$index}.");
                }
            }
        }

        if (!isset($config['from_addresses']) || !is_array($config['from_addresses']) || empty($config['from_addresses'])) {
            throw new \InvalidArgumentException('SmartMailer: Missing or invalid `from_addresses` array in configuration.');
        }

        $requiredFromKeys = ['name', 'address', 'mailer'];
        foreach ($config['from_addresses'] as $type => $fromAddress) {
             if (!is_array($fromAddress)) {
                throw new \InvalidArgumentException("SmartMailer: From address configuration for type '{$type}' must be an array.");
            }
            foreach ($requiredFromKeys as $key) {
                if (!isset($fromAddress[$key])) {
                    throw new \InvalidArgumentException("SmartMailer: Missing key '{$key}' in from_address configuration for type '{$type}'.");
                }
            }
            // Optional: Validate 'mailer' value against connection names or 'rotate'
        }

        // Optional: Validate strategy
        if (isset($config['strategy']) && !in_array($config['strategy'], ['round_robin', 'random'])) {
             throw new \InvalidArgumentException("SmartMailer: Invalid strategy '{$config['strategy']}'. Must be 'round_robin' or 'random'.");
        }
        
        // Optional: Validate queue types structure if needed
        if (isset($config['queue']['types']) && !is_array($config['queue']['types'])){
            throw new \InvalidArgumentException('SmartMailer: `queue.types` configuration must be an array.');
        }
    }
}
