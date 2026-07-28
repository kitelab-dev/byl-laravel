<?php

namespace Byl\Laravel\Facades;

use Byl\Laravel\BylManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Byl\Laravel\BylClient client()
 * @method static \Byl\Laravel\BylClient project(int|string $projectId, ?string $token = null)
 * @method static \Byl\Laravel\BylClient withToken(string $token, int|string|null $projectId = null)
 * @method static \Byl\Laravel\Testing\BylFake fake()
 * @method static bool isFaked()
 * @method static void resolveBillableUsing(?\Closure $resolver)
 * @method static \Closure|null billableResolver()
 * @method static \Byl\Laravel\Endpoints\Invoices invoices()
 * @method static \Byl\Laravel\Endpoints\Checkouts checkouts()
 * @method static \Byl\Laravel\Endpoints\Customers customers()
 * @method static \Byl\Laravel\Endpoints\Subscriptions subscriptions()
 * @method static \Byl\Laravel\Endpoints\BillingPortal billingPortal()
 * @method static array get(string $path, array $query = [])
 * @method static array post(string $path, array $payload = [])
 * @method static array delete(string $path)
 * @method static string url(string $path = '')
 * @method static int|string projectId()
 *
 * @see BylManager
 */
class Byl extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BylManager::class;
    }
}
