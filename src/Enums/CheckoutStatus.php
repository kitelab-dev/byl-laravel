<?php

namespace Byl\Laravel\Enums;

enum CheckoutStatus: string
{
    case Open = 'open';
    case Complete = 'complete';
    case Expired = 'expired';

    public function isComplete(): bool
    {
        return $this === self::Complete;
    }
}
