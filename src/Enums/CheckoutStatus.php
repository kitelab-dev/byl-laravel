<?php

namespace Byl\Laravel\Enums;

enum CheckoutStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Complete = 'complete';
    case Expired = 'expired';

    public function isComplete(): bool
    {
        return $this === self::Complete;
    }

    /**
     * Харилцагч банкны шилжүүлэг хийснээ мэдэгдсэн ч merchant хараахан
     * баталгаажуулаагүй — төлбөр төлөгдсөнд тооцогдохгүй.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }
}
