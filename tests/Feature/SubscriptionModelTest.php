<?php

use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\SubscriptionFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = $this->createUser(['byl_customer_id' => 12]);

    $this->subscription = $this->user->bylSubscriptions()->create([
        'byl_id' => 4,
        'byl_customer_id' => 12,
        'product_id' => 7,
        'price_id' => 3,
        'lookup_key' => 'starter_monthly',
        'status' => SubscriptionStatus::Active->value,
        'current_period_start' => Carbon::now()->subDay(),
        'current_period_end' => Carbon::now()->addMonth(),
    ]);
});

it('сунгалтын checkout үүсгэнэ', function () {
    $fake = Byl::fake();

    $checkout = $this->subscription->renewCheckout(3);

    expect($checkout->url)->toContain('/h/checkout/');

    $fake->assertCheckoutCreated(fn (array $payload) => $payload['customer_id'] === 12
        && $payload['subscription_id'] === 4
        && $payload['items'] === [['price' => 'starter_monthly', 'quantity' => 3]]);
});

it('багц солих checkout үүсгэнэ', function () {
    $fake = Byl::fake();

    $this->subscription->swapCheckout('growth_monthly', options: ['success_url' => 'https://example.mn/ok']);

    $fake->assertCheckoutCreated(fn (array $payload) => $payload['subscription_id'] === 4
        && $payload['items'] === [['price' => 'growth_monthly', 'quantity' => 1]]
        && $payload['success_url'] === 'https://example.mn/ok');
});

it('цуцлахад локал төлөв шинэчлэгдэнэ', function () {
    Http::fake([
        'byl.mn/*/subscriptions/4/cancel' => Http::response(bylResponse(SubscriptionFactory::make([
            'id' => 4,
            'status' => 'active',
            'canceled_at' => Carbon::now()->toJSON(),
        ]))),
    ]);

    $this->subscription->cancel();

    expect($this->subscription->canceled())->toBeTrue()
        ->and($this->subscription->onGracePeriod())->toBeTrue()
        ->and($this->user->fresh()->subscribed())->toBeTrue()      // мөчлөг дуустал эрхтэй
        ->and($this->user->fresh()->onGracePeriod())->toBeTrue();
});

it('цуцлалт буцаахад локал төлөв шинэчлэгдэнэ', function () {
    Http::fake([
        'byl.mn/*/subscriptions/4/resume' => Http::response(bylResponse(SubscriptionFactory::make([
            'id' => 4,
            'status' => 'active',
            'canceled_at' => null,
        ]))),
    ]);

    $this->subscription->update(['canceled_at' => Carbon::now()]);

    $this->subscription->resume();

    expect($this->subscription->canceled())->toBeFalse()
        ->and($this->subscription->fresh()->canceled_at)->toBeNull();
});

it('Byl дээрх төлөвөөр дахин таарна', function () {
    Http::fake([
        'byl.mn/*/subscriptions/4' => Http::response(bylResponse(SubscriptionFactory::make([
            'id' => 4,
            'status' => 'past_due',
        ]))),
    ]);

    $this->subscription->syncFromByl();

    expect($this->subscription->status)->toBe(SubscriptionStatus::PastDue)
        ->and($this->subscription->pastDue())->toBeTrue()
        ->and($this->subscription->valid())->toBeTrue();
});

it('lookup key ирээгүй хариу дээр байгаа утгыг дарж бичихгүй', function () {
    $data = SubscriptionFactory::make(['id' => 4]);
    unset($data['price']);

    Http::fake(['byl.mn/*/subscriptions/4' => Http::response(bylResponse($data))]);

    $this->subscription->syncFromByl();

    expect($this->subscription->lookup_key)->toBe('starter_monthly');
});

it('үлдсэн хоногийг тооцоолно', function () {
    expect($this->subscription->daysUntilPeriodEnd())->toBeGreaterThan(25)
        ->and($this->subscription->hasPrice('starter_monthly'))->toBeTrue()
        ->and($this->subscription->hasPrice(3))->toBeTrue()
        ->and($this->subscription->hasPrice('growth_monthly'))->toBeFalse()
        ->and($this->subscription->hasPrice(9))->toBeFalse();
});
