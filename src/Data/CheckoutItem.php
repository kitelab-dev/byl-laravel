<?php

namespace Byl\Laravel\Data;

use Illuminate\Support\Arr;

final class CheckoutItem extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $priceId,
        public readonly ?string $productName,
        public readonly ?int $quantity,
        public readonly ?float $amountUnit,
        public readonly ?float $amountSubtotal,
        public readonly ?float $amountTotal,
        public readonly ?Price $price,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $price = Arr::get($data, 'price');

        // Webhook payload-д бүтээгдэхүүн item-ийн дор тусдаа, API-д үнийн дор ирдэг.
        $product = Arr::get($data, 'product') ?? Arr::get($data, 'price.product');

        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            priceId: Arr::get($data, 'price_id') !== null
                ? (int) Arr::get($data, 'price_id')
                : (Arr::get($data, 'price.id') !== null ? (int) Arr::get($data, 'price.id') : null),
            productName: Arr::get($data, 'product_name') ?? Arr::get($product ?? [], 'name'),
            quantity: Arr::get($data, 'quantity') !== null ? (int) Arr::get($data, 'quantity') : null,
            amountUnit: self::number(Arr::get($data, 'amount_unit')),
            amountSubtotal: self::number(Arr::get($data, 'amount_subtotal')),
            amountTotal: self::number(Arr::get($data, 'amount_total')),
            price: is_array($price) ? Price::fromArray($price) : null,
            raw: $data,
        );
    }
}
