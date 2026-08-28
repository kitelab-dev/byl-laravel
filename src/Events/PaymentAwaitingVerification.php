<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Payment;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `payment.awaiting_verification` — харилцагч банкны шилжүүлэг хийснээ
 * мэдэгдсэн бөгөөд merchant-ийн баталгаажуулалтыг хүлээж байна. Мөнгө
 * баталгаажаагүй тул энэ үед бараа/эрхийг олгож болохгүй.
 */
class PaymentAwaitingVerification
{
    public function __construct(
        public readonly Payment $payment,
        public readonly WebhookEvent $event,
    ) {}
}
