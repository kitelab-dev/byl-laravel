<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Тестэд зориулсан checkout-ийн payload. Бүтэц нь Byl-ийн
 * `checkout.completed` webhook болон checkout-ийн API-ийн хариутай ижил —
 * харилцагч нь `customer` объект, item дээр `price` ба `product` объект
 * тус тусдаа, дүн нь string хэлбэрээр ирдэг.
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
            'mode' => 'payment',
            'items' => [],
            'status' => 'open',
            'customer' => null,
            'is_guest' => true,
            'created_at' => Carbon::now()->toJSON(),
            'expires_at' => Carbon::now()->addHours(6)->toJSON(),
            'project_id' => 1,
            'updated_at' => Carbon::now()->toJSON(),
            'amount_total' => '1000.000000000000',
            'coupon_codes' => [],
            'phone_number' => null,
            'customer_email' => null,
            'amount_subtotal' => '1000.000000000000',
            'subscription_id' => null,
            'delivery_address' => null,
            'email_collection' => true,
            'client_reference_id' => null,
            'phone_number_collection' => false,
            'delivery_address_collection' => false,
        ], $attributes);
    }

    /**
     * Webhook-д ирдэг төлөгдсөн checkout (items, харилцагчтай).
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function completed(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        return self::make(array_merge([
            'status' => 'complete',
            'is_guest' => false,
            'customer' => [
                'id' => 12,
                'name' => 'Бат-Эрдэнэ',
                'client_reference_id' => null,
            ],
            'customer_email' => 'customer@example.mn',
            'items' => [
                [
                    'price' => [
                        'id' => 17,
                        'type' => 'recurring',
                        'lookup_key' => 'starter_monthly',
                        'unit_amount' => 1000,
                        'recurring_interval' => 'month',
                        'recurring_interval_count' => 1,
                    ],
                    'product' => [
                        'id' => 15,
                        'name' => 'Product 1',
                        'client_reference_id' => null,
                    ],
                    'quantity' => 1,
                    'amount_unit' => 1000,
                    'amount_total' => 1000,
                    'amount_subtotal' => 1000,
                    'adjustable_quantity' => null,
                ],
            ],
        ], $attributes), $baseUrl);
    }
}
