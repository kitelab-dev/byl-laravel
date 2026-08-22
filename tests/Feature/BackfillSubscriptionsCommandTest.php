<?php

use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Testing\SubscriptionFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

function subscriptionListResponse(array $subscriptions): array
{
    return [
        'data' => $subscriptions,
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => count($subscriptions)],
    ];
}

function localSubscription(object $user, array $attributes = []): void
{
    $user->bylSubscriptions()->create([
        'byl_id' => 4,
        'byl_customer_id' => 12,
        'product_id' => 7,
        'price_id' => 3,
        'status' => SubscriptionStatus::Active->value,
        'current_period_start' => Carbon::now()->subDay(),
        'current_period_end' => Carbon::now()->addMonth(),
        ...$attributes,
    ]);
}

it('lookup_key хоосон үлдсэн мөрүүдийг API-аас нөхнө', function () {
    Http::fake([
        'byl.mn/*/subscriptions*' => Http::response(subscriptionListResponse([
            SubscriptionFactory::make(['id' => 4]),
        ])),
    ]);

    $user = $this->createUser(['byl_customer_id' => 12]);

    // Webhook lookup key явуулдаггүй байх үед үүссэн мөр.
    localSubscription($user, ['lookup_key' => null]);

    $this->artisan('byl:backfill-subscriptions')
        ->expectsOutputToContain('1 billable шалгаж, 1 захиалгыг шинэчлэв.')
        ->assertSuccessful();

    expect($user->bylSubscriptions()->count())->toBe(1)
        ->and($user->bylSubscription()->lookup_key)->toBe('starter_monthly')
        ->and($user->subscribed('starter_monthly'))->toBeTrue();
});

it('харилцагч холбоогүй billable-д хүсэлт явуулахгүй', function () {
    Http::fake();

    $this->createUser();

    $this->artisan('byl:backfill-subscriptions')->assertSuccessful();

    Http::assertNothingSent();
});

it('нэг харилцагч дээрх алдаа бүх backfill-ийг зогсоохгүй', function () {
    $first = $this->createUser(['byl_customer_id' => 12]);
    $second = $this->createUser(['email' => 'second@example.mn', 'byl_customer_id' => 13]);

    Http::fake([
        'byl.mn/*/subscriptions?customer_id=12*' => Http::response(['message' => 'Server error'], 500),
        'byl.mn/*/subscriptions*' => Http::response(subscriptionListResponse([
            SubscriptionFactory::make(['id' => 9]),
        ])),
    ]);

    $this->artisan('byl:backfill-subscriptions')
        ->expectsOutputToContain('1 billable дээр алдаа гарсан')
        ->assertSuccessful();

    expect($first->bylSubscriptions()->count())->toBe(0)
        ->and($second->bylSubscriptions()->count())->toBe(1);
});

it('billable модель тохируулаагүй бол алдаа буцаана', function () {
    config()->set('byl.billable.model', null);

    $this->artisan('byl:backfill-subscriptions')->assertFailed();
});
