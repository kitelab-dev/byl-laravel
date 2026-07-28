<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `subscription.created` — Шинэ захиалга үүссэн (checkout төлөгдсөн эсвэл trial эхэлсэн).
 */
class SubscriptionCreated
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly WebhookEvent $event,
    ) {}
}
