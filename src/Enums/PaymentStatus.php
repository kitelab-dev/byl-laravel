<?php

namespace Byl\Laravel\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    /**
     * Төлбөр хүлээгдэж байна — банкны шилжүүлгийн хувьд merchant
     * баталгаажуулаагүй байгаа гэсэн үг.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }
}
