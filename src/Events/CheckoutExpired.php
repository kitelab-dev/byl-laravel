<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Checkout;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `checkout.expired` — checkout төлөгдөөгүй хугацаа дууссан.
 */
class CheckoutExpired
{
    public function __construct(
        public readonly Checkout $checkout,
        public readonly WebhookEvent $event,
    ) {}
}
