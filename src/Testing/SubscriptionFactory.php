<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;

/**
 * Тестэд зориулсан захиалгын payload. Бүтэц нь Byl-ийн `subscription.*`
 * webhook болон захиалгын API-ийн хариутай яг ижил — `price`, `product`,
 * `customer` объект бүр өөрийн түвшинд, дээд түвшний `price_id` /
 * `product_id` / `customer_id` талбар байхгүй.
 */
class SubscriptionFactory
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function make(array $attributes = []): array
    {
        return array_merge([
            'id' => $attributes['id'] ?? 4,
            'price' => [
                'id' => 3,
                'type' => 'recurring',
                'lookup_key' => 'starter_monthly',
                'unit_amount' => 30000,
                'recurring_interval' => 'month',
                'recurring_interval_count' => 1,
            ],
            'status' => 'active',
            'is_test' => false,
            'product' => [
                'id' => 7,
                'name' => 'Starter багц',
                'client_reference_id' => null,
            ],
            'customer' => [
                'id' => 12,
                'name' => 'Бат-Эрдэнэ',
                'email' => 'customer@example.mn',
                'client_reference_id' => null,
            ],
            'created_at' => Carbon::now()->subMonth()->toJSON(),
            'project_id' => 1,
            'updated_at' => Carbon::now()->toJSON(),
            'canceled_at' => null,
            'trial_ends_at' => null,
            'current_period_end' => Carbon::now()->addMonth()->toJSON(),
            'current_period_start' => Carbon::now()->subDays(2)->toJSON(),
        ], $attributes);
    }

    /**
     * Туршилтын захиалга.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function trialing(array $attributes = []): array
    {
        $trialDays = (int) ($attributes['trial_days'] ?? 14);

        // Туршилт эхлүүлэх хүсэлт нь `price` талбарт lookup key дамжуулж
        // болдог — хариунд байх ёстой үнийн объектыг тэр string дарж
        // бичихгүйн тулд lookup key болгож буулгана.
        $lookupKey = is_string($attributes['price'] ?? null) ? $attributes['price'] : null;

        unset($attributes['trial_days']);

        if ($lookupKey !== null) {
            unset($attributes['price']);
        }

        $subscription = self::make(array_merge([
            'status' => 'trialing',
            'current_period_start' => Carbon::now()->toJSON(),
            'current_period_end' => Carbon::now()->addDays($trialDays)->endOfDay()->toJSON(),
            'trial_ends_at' => Carbon::now()->addDays($trialDays)->endOfDay()->toJSON(),
        ], $attributes));

        if ($lookupKey !== null) {
            $subscription['price']['lookup_key'] = $lookupKey;
        }

        return $subscription;
    }

    /**
     * Lookup key тохируулаагүй үнэ — Byl `lookup_key`-г `null`-аар буцаана.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function withoutLookupKey(array $attributes = []): array
    {
        $subscription = self::make($attributes);

        $subscription['price']['lookup_key'] = null;

        return $subscription;
    }
}
