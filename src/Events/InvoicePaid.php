<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `invoice.paid` — нэхэмжлэх амжилттай төлөгдсөн.
 */
class InvoicePaid
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly WebhookEvent $event,
    ) {}
}
