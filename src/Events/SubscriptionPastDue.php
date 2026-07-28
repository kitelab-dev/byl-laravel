<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `subscription.past_due` — Хугацаа хэтэрсэн (grace period).
 */
class SubscriptionPastDue
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly WebhookEvent $event,
    ) {}
}
