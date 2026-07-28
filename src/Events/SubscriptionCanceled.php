<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `subscription.canceled` — Бүрэн цуцлагдсан — хэрэглэгчийн эрхийг энэ үед хаана.
 */
class SubscriptionCanceled
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly WebhookEvent $event,
    ) {}
}
