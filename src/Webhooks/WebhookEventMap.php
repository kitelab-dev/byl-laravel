<?php

namespace Byl\Laravel\Webhooks;

use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Events\CheckoutCompleted;
use Byl\Laravel\Events\CheckoutExpired;
use Byl\Laravel\Events\InvoicePaid;
use Byl\Laravel\Events\InvoiceVoided;
use Byl\Laravel\Events\SubscriptionCanceled;
use Byl\Laravel\Events\SubscriptionCreated;
use Byl\Laravel\Events\SubscriptionPastDue;
use Byl\Laravel\Events\SubscriptionRenewalDue;
use Byl\Laravel\Events\SubscriptionRenewed;
use Byl\Laravel\Events\SubscriptionUpdated;

/**
 * Byl-ийн event төрлийг SDK-ийн Laravel event класстай холбоно.
 */
class WebhookEventMap
{
    /**
     * Тухайн event-т тохирох Laravel event объектыг үүсгэнэ. Танигдаагүй
     * төрөл бол `null` — merchant-ууд WebhookReceived-ээр барьж болно.
     */
    public static function make(WebhookEvent $event): ?object
    {
        return match ($event->type()) {
            WebhookEventType::InvoicePaid => new InvoicePaid($event->invoice(), $event),
            WebhookEventType::InvoiceVoid => new InvoiceVoided($event->invoice(), $event),
            WebhookEventType::CheckoutCompleted => new CheckoutCompleted($event->checkout(), $event),
            WebhookEventType::CheckoutExpired => new CheckoutExpired($event->checkout(), $event),
            WebhookEventType::SubscriptionCreated => new SubscriptionCreated($event->subscription(), $event),
            WebhookEventType::SubscriptionUpdated => new SubscriptionUpdated($event->subscription(), $event),
            WebhookEventType::SubscriptionRenewed => new SubscriptionRenewed($event->subscription(), $event),
            WebhookEventType::SubscriptionRenewalDue => new SubscriptionRenewalDue($event->subscription(), $event),
            WebhookEventType::SubscriptionPastDue => new SubscriptionPastDue($event->subscription(), $event),
            WebhookEventType::SubscriptionCanceled => new SubscriptionCanceled($event->subscription(), $event),
            default => null,
        };
    }
}
