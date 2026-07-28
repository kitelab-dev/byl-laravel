<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Тестэд зориулсан checkout-ийн бодит бүтэцтэй payload.
 */
class CheckoutFactory
{
    /**
     * Checkout үүсгэх endpoint зөвхөн id ба url буцаадаг.
     *
     * @return array{id: int|string, url: string}
     */
    public static function created(int|string $id, string $baseUrl = 'https://byl.mn'): array
    {
        return [
            'id' => $id,
            'url' => rtrim($baseUrl, '/').'/h/checkout/'.$id.'/'.Str::random(8),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function make(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        $id = $attributes['id'] ?? 13338;

        return array_merge([
            'id' => $id,
            'url' => rtrim($baseUrl, '/').'/h/checkout/'.$id.'/Yi7smBuk',
            'client_reference_id' => null,
            'mode' => 'payment',
            'status' => 'open',
            'expires_at' => Carbon::now()->addHours(6)->toJSON(),
            'amount_subtotal' => 1000,
            'amount_total' => 1000,
            'customer_id' => null,
            'customer_email' => null,
            'is_guest' => true,
            'allow_promotion_codes' => false,
            'created_at' => Carbon::now()->toJSON(),
            'updated_at' => Carbon::now()->toJSON(),
        ], $attributes);
    }

    /**
     * Webhook-д ирдэг төлөгдсөн checkout (items, coupon_codes-той).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function completed(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        return self::make(array_merge([
            'status' => 'complete',
            'payment_method' => 'qpay',
            'items' => [
                [
                    'id' => 69,
                    'price_id' => 17,
                    'quantity' => 1,
                    'amount_unit' => 1000,
                    'amount_total' => 1000,
                    'amount_subtotal' => 1000,
                    'price' => [
                        'id' => 17,
                        'unit_amount' => 1000,
                        'product_id' => 15,
                        'product' => [
                            'id' => 15,
                            'name' => 'Product 1',
                            'client_reference_id' => null,
                        ],
                    ],
                ],
            ],
            'coupon_codes' => [],
        ], $attributes), $baseUrl);
    }
}
