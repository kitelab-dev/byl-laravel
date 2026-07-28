<?php

namespace Byl\Laravel\Tests\Feature;

use Byl\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class WebhookRouteDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('byl.webhook.route.enabled', false);
    }

    public function test_route_is_not_registered_when_disabled(): void
    {
        $this->assertFalse(Route::has('byl.webhook'));

        $this->postJson('byl/webhook', [])->assertNotFound();
    }
}
