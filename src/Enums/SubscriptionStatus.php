<?php

namespace Byl\Laravel\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';

    /**
     * Эрхтэй төлөвүүд. Хэрэглэгчийн эрхийг `subscription.canceled` event
     * ирэх хүртэл нээлттэй байлгахыг Byl зөвлөдөг.
     */
    public function isEntitled(): bool
    {
        return $this !== self::Canceled;
    }
}
