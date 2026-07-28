<?php

namespace Byl\Laravel\Enums;

enum WebhookEventType: string
{
    case Test = 'test';
    case InvoicePaid = 'invoice.paid';
    case InvoiceVoid = 'invoice.void';
    case CheckoutCompleted = 'checkout.completed';
    case CheckoutExpired = 'checkout.expired';
    case SubscriptionCreated = 'subscription.created';
    case SubscriptionUpdated = 'subscription.updated';
    case SubscriptionRenewed = 'subscription.renewed';
    case SubscriptionRenewalDue = 'subscription.renewal_due';
    case SubscriptionPastDue = 'subscription.past_due';
    case SubscriptionCanceled = 'subscription.canceled';
}
