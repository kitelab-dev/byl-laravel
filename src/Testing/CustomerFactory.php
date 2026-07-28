<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;

/**
 * Тестэд зориулсан харилцагчийн бодит бүтэцтэй payload.
 */
class CustomerFactory
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function make(array $attributes = []): array
    {
        $id = $attributes['id'] ?? 12;

        return array_merge([
            'id' => $id,
            'name' => 'Бат-Эрдэнэ',
            'email' => 'customer@example.mn',
            'phone' => null,
            'client_reference_id' => null,
            'created_at' => Carbon::now()->toJSON(),
            'updated_at' => Carbon::now()->toJSON(),
        ], array_intersect_key($attributes, array_flip([
            'id', 'name', 'email', 'phone', 'client_reference_id',
            'subscriptions', 'created_at', 'updated_at',
        ])));
    }

    /**
     * Эрхтэй захиалгатай харилцагч.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $subscriptions
     * @return array<string, mixed>
     */
    public static function subscribed(array $attributes = [], array $subscriptions = []): array
    {
        return self::make($attributes + [
            'subscriptions' => $subscriptions ?: [SubscriptionFactory::make()],
        ]);
    }
}
