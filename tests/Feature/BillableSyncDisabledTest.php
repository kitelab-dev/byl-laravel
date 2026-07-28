<?php

namespace Byl\Laravel\Tests\Feature;

use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Testing\FakeWebhook;
use Byl\Laravel\Tests\TestCase;

class BillableSyncDisabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('byl.billable.sync_webhooks', false);
    }

    public function test_subscriptions_are_not_synced_when_disabled(): void
    {
        $user = $this->createUser();

        $webhook = FakeWebhook::subscription(WebhookEventType::SubscriptionCreated, [
            'id' => 4,
            'status' => 'active',
            'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
        ]);

        $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

        $this->assertSame(0, $user->bylSubscriptions()->count());
        $this->assertNull($user->fresh()->byl_customer_id);
    }
}
