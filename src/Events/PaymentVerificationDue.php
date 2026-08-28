<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Payment;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `payment.verification_due` — баталгаажуулаагүй банкны шилжүүлгийн 3 хоногийн
 * хугацаа дуусахаас 24 цагийн өмнөх сануулга. Merchant-д мэдэгдэл илгээхэд
 * ашиглана.
 */
class PaymentVerificationDue
{
    public function __construct(
        public readonly Payment $payment,
        public readonly WebhookEvent $event,
    ) {}
}
