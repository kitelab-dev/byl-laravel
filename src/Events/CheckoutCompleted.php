<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Checkout;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `checkout.completed` — checkout амжилттай төлөгдсөн.
 */
class CheckoutCompleted
{
    public function __construct(
        public readonly Checkout $checkout,
        public readonly WebhookEvent $event,
    ) {}
}
