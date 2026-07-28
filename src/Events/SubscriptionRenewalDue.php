<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `subscription.renewal_due` — Сунгалтын сануулга илгээгдсэн.
 */
class SubscriptionRenewalDue
{
    public function __construct(
        public readonly Subscription $subscription,
        public readonly WebhookEvent $event,
    ) {}
}
