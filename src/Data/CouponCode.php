<?php

namespace Byl\Laravel\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

final class CouponCode extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?string $code,
        public readonly ?string $couponName,
        public readonly ?float $discountAmount,
        public readonly ?Carbon $redeemedAt,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: Arr::get($data, 'code'),
            couponName: Arr::get($data, 'coupon_name'),
            discountAmount: self::number(Arr::get($data, 'discount_amount')),
            redeemedAt: self::date(Arr::get($data, 'redeemed_at')),
            raw: $data,
        );
    }
}
