<?php

namespace Byl\Laravel\Data;

use Byl\Laravel\Enums\PriceType;
use Byl\Laravel\Enums\RecurringInterval;
use Illuminate\Support\Arr;

final class Price extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?PriceType $type,
        public readonly ?float $unitAmount,
        public readonly ?RecurringInterval $recurringInterval,
        public readonly ?int $recurringIntervalCount,
        public readonly ?string $lookupKey,
        public readonly ?Product $product,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $product = Arr::get($data, 'product');

        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            type: self::enum(PriceType::class, Arr::get($data, 'type')),
            unitAmount: self::number(Arr::get($data, 'unit_amount')),
            recurringInterval: self::enum(RecurringInterval::class, Arr::get($data, 'recurring_interval')),
            recurringIntervalCount: Arr::get($data, 'recurring_interval_count') !== null
                ? (int) Arr::get($data, 'recurring_interval_count')
                : null,
            lookupKey: Arr::get($data, 'lookup_key'),
            product: is_array($product) ? Product::fromArray($product) : null,
            raw: $data,
        );
    }

    public function isRecurring(): bool
    {
        return $this->type?->isRecurring() ?? false;
    }
}
