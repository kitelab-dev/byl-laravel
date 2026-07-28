<?php

namespace Byl\Laravel\Enums;

enum PriceType: string
{
    case OneTime = 'one_time';
    case Recurring = 'recurring';

    public function isRecurring(): bool
    {
        return $this === self::Recurring;
    }
}
