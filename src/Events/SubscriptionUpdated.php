<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `subscription.updated` — Багц солигдсон, цуцлалт хүссэн эсвэл цуцлалт буцсан.
 */
class SubscriptionUpdated
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly WebhookEvent $event,
    ) {}
}
