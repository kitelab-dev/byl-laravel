<?php

use Byl\Laravel\Billing\Models\Subscription;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\CustomerFactory;
use Byl\Laravel\Testing\SubscriptionFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

function subscribe(object $user, array $attributes = []): Subscription
{
    return $user->bylSubscriptions()->create([
        'byl_id' => $attributes['byl_id'] ?? 4,
        'byl_customer_id' => 12,
        'product_id' => 7,
        'price_id' => 3,
        'lookup_key' => 'starter_monthly',
        'status' => SubscriptionStatus::Active->value,
        'current_period_start' => Carbon::now()->subDay(),
        'current_period_end' => Carbon::now()->addMonth(),
        ...$attributes,
    ]);
}

it('эрхтэй захиалгыг таньж байна', function () {
    $user = $this->createUser();

    expect($user->subscribed())->toBeFalse();

    subscribe($user);

    expect($user->subscribed())->toBeTrue()
        ->and($user->subscribed('starter_monthly'))->toBeTrue()
        ->and($user->subscribed('growth_monthly'))->toBeFalse()
        ->and($user->subscribedToPrice(3))->toBeTrue()
        ->and($user->subscribedToPrice(9))->toBeFalse()
        ->and($user->subscribedToProduct(7))->toBeTrue()
        ->and($user->subscribedToProduct(8))->toBeFalse()
        ->and($user->bylSubscription()?->byl_id)->toBe(4);
});

it('цуцлагдсан захиалга эрх өгөхгүй', function () {
    $user = $this->createUser();

    subscribe($user, [
        'status' => SubscriptionStatus::Canceled->value,
        'canceled_at' => Carbon::now()->subMonth(),
    ]);

    expect($user->subscribed())->toBeFalse()
        ->and($user->subscriptionEnded())->toBeTrue()
        ->and($user->bylSubscriptions()->first()->ended())->toBeTrue();
});

it('туршилт болон grace period-ийг ялгана', function () {
    $user = $this->createUser();

    $subscription = subscribe($user, [
        'status' => SubscriptionStatus::Trialing->value,
        'trial_ends_at' => Carbon::now()->addDays(14),
    ]);

    expect($user->onTrial())->toBeTrue()
        ->and($user->subscribed())->toBeTrue()
        ->and($user->onGracePeriod())->toBeFalse();

    $subscription->update([
        'status' => SubscriptionStatus::Active->value,
        'canceled_at' => Carbon::now(),
    ]);

    expect($user->fresh()->onTrial())->toBeFalse()
        ->and($user->fresh()->onGracePeriod())->toBeTrue()
        ->and($user->fresh()->subscribed())->toBeTrue();
});

it('past_due төлөв эрхтэй хэвээр байна', function () {
    $user = $this->createUser();

    subscribe($user, ['status' => SubscriptionStatus::PastDue->value]);

    expect($user->subscribed())->toBeTrue()
        ->and($user->pastDue())->toBeTrue();
});

it('харилцагчийг үүсгэж byl_customer_id-г хадгална', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/customers' => Http::response(bylResponse(
            CustomerFactory::make(['id' => 12, 'client_reference_id' => '1'])
        ), 201),
    ]);

    $user = $this->createUser();

    $customer = $user->createOrGetBylCustomer();

    expect($customer->id)->toBe(12)
        ->and($user->fresh()->byl_customer_id)->toBe(12)
        ->and($user->fresh()->bylCustomerId())->toBe(12);

    Http::assertSent(fn ($request) => $request['client_reference_id'] === (string) $user->id
        && $request['email'] === 'customer@example.mn'
        && $request['name'] === 'Бат-Эрдэнэ');
});

it('client_reference_column тохиргоог хэрэглэнэ', function () {
    config()->set('byl.billable.client_reference_column', 'email');

    Http::fake(['byl.mn/*' => Http::response(bylResponse(CustomerFactory::make(['id' => 12])), 201)]);

    $user = $this->createUser();

    expect($user->bylClientReferenceId())->toBe('customer@example.mn');

    $user->createOrGetBylCustomer();

    Http::assertSent(fn ($request) => $request['client_reference_id'] === 'customer@example.mn');
});

it('Byl дээрх захиалгуудыг локал хүснэгтэд нөхнө', function () {
    Http::fake([
        'byl.mn/*/subscriptions*' => Http::response([
            'data' => [
                SubscriptionFactory::make(['id' => 4, 'status' => 'active']),
                SubscriptionFactory::make(['id' => 9, 'status' => 'canceled', 'price_id' => 5]),
            ],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 2],
        ]),
    ]);

    $user = $this->createUser(['byl_customer_id' => 12]);

    $synced = $user->syncBylSubscriptions();

    expect($synced)->toHaveCount(2)
        ->and($user->bylSubscriptions()->count())->toBe(2)
        ->and($user->subscribed())->toBeTrue()
        ->and($user->bylSubscription()->lookup_key)->toBe('starter_monthly')
        ->and($user->bylSubscription()->status)->toBe(SubscriptionStatus::Active);
});

it('дахин sync хийхэд захиалга давхардахгүй', function () {
    Http::fake([
        'byl.mn/*/subscriptions*' => Http::response([
            'data' => [SubscriptionFactory::make(['id' => 4, 'status' => 'active'])],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1],
        ]),
    ]);

    $user = $this->createUser(['byl_customer_id' => 12]);

    $user->syncBylSubscriptions();
    $user->syncBylSubscriptions();

    expect($user->bylSubscriptions()->count())->toBe(1);
});

it('харилцагчгүй үед sync хоосон буцаана', function () {
    Byl::fake();

    expect($this->createUser()->syncBylSubscriptions())->toBeEmpty();

    Byl::fake()->assertNothingSent();
});
