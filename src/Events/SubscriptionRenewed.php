<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `subscription.renewed` — Мөчлөг амжилттай сунгагдсан.
 */
class SubscriptionRenewed
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly WebhookEvent $event,
    ) {}
}
