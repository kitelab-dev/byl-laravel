<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * `invoice.void` — нэхэмжлэх хүчингүй болсон.
 */
class InvoiceVoided
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly WebhookEvent $event,
    ) {}
}
