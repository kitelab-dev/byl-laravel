<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;

/**
 * Тестэд зориулсан захиалгын бодит бүтэцтэй payload.
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
            'status' => 'active',
            'project_id' => 1,
            'customer_id' => 12,
            'product_id' => 7,
            'price_id' => 3,
            'current_period_start' => Carbon::now()->subDays(2)->toJSON(),
            'current_period_end' => Carbon::now()->addMonth()->toJSON(),
            'trial_ends_at' => null,
            'canceled_at' => null,
            'is_test' => false,
            'price' => [
                'id' => 3,
                'type' => 'recurring',
                'unit_amount' => 30000,
                'recurring_interval' => 'month',
                'recurring_interval_count' => 1,
                'lookup_key' => 'starter_monthly',
                'product' => [
                    'id' => 7,
                    'name' => 'Starter багц',
                    'client_reference_id' => null,
                ],
            ],
            'created_at' => Carbon::now()->subMonth()->toJSON(),
            'updated_at' => Carbon::now()->toJSON(),
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

        unset($attributes['trial_days']);

        return self::make(array_merge([
            'status' => 'trialing',
            'current_period_start' => Carbon::now()->toJSON(),
            'current_period_end' => Carbon::now()->addDays($trialDays)->endOfDay()->toJSON(),
            'trial_ends_at' => Carbon::now()->addDays($trialDays)->endOfDay()->toJSON(),
        ], $attributes));
    }
}
