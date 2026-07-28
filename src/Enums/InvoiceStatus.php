<?php

namespace Byl\Laravel\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Paid = 'paid';
    case Void = 'void';

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isPayable(): bool
    {
        return $this === self::Open;
    }
}
