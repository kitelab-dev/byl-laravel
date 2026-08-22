<?php

namespace Byl\Laravel;

use Byl\Laravel\Billing\Listeners\SyncBylSubscription;
use Byl\Laravel\Console\BackfillSubscriptionsCommand;
use Byl\Laravel\Events\WebhookReceived;
use Byl\Laravel\Http\Middleware\EnsureSubscribed;
use Byl\Laravel\Http\Middleware\VerifyBylSignature;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class BylServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/byl.php', 'byl');

        $this->app->singleton(BylManager::class, fn ($app) => new BylManager(
            $app->make(Factory::class),
            $app->make(Repository::class),
        ));

        $this->app->alias(BylManager::class, 'byl');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/byl.php' => $this->app->configPath('byl.php'),
            ], 'byl-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'byl-migrations');

            $this->commands([BackfillSubscriptionsCommand::class]);
        }

        $this->registerMiddleware();
        $this->registerSubscriptionSync();

        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
    }

    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Өөрийн route дээр гарын үсгийн шалгалтыг хэрэглэх:
        // Route::post(...)->middleware('byl-signature')
        $router->aliasMiddleware('byl-signature', VerifyBylSignature::class);

        // Эрхтэй захиалгатай хэрэглэгчийг л оруулах:
        // Route::get(...)->middleware('byl.subscribed:starter_monthly')
        $router->aliasMiddleware('byl.subscribed', EnsureSubscribed::class);
    }

    /**
     * Subscription webhook-ийг локал хүснэгттэй тааруулж байх listener.
     * Billable модель тохируулаагүй бол listener өөрөө юу ч хийхгүй.
     */
    protected function registerSubscriptionSync(): void
    {
        if (! $this->app->make(Repository::class)->get('byl.billable.sync_webhooks', true)) {
            return;
        }

        $this->app->make(Dispatcher::class)->listen(WebhookReceived::class, SyncBylSubscription::class);
    }
}
