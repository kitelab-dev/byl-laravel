<?php

use Byl\Laravel\Enums\RecurringInterval;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Exceptions\ConflictException;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\SubscriptionFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('захиалга лавлаж холбоотой мэдээллийг parse хийнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/subscriptions/4' => Http::response(bylResponse(SubscriptionFactory::make([
            'id' => 4,
            'customer' => [
                'id' => 12,
                'name' => 'Бат-Эрдэнэ',
                'email' => 'customer@example.mn',
                'client_reference_id' => 'user_842',
            ],
        ]))),
    ]);

    $subscription = Byl::subscriptions()->find(4);

    expect($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->isEntitled())->toBeTrue()
        ->and($subscription->cancelRequested())->toBeFalse()
        ->and($subscription->customer?->clientReferenceId)->toBe('user_842')
        ->and($subscription->price?->lookupKey)->toBe('starter_monthly')
        ->and($subscription->price?->recurringInterval)->toBe(RecurringInterval::Month)
        ->and($subscription->price?->isRecurring())->toBeTrue()
        ->and($subscription->product?->name)->toBe('Starter багц')
        ->and($subscription->productId)->toBe(7)
        ->and($subscription->daysUntilPeriodEnd())->toBeGreaterThan(0);
});

it('захиалгын жагсаалтыг filter-тэй авна', function () {
    Http::fake([
        'byl.mn/*/subscriptions*' => Http::response([
            'data' => [SubscriptionFactory::make(['id' => 4])],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1],
        ]),
    ]);

    $page = Byl::subscriptions()->forCustomer(12, SubscriptionStatus::Active);

    expect($page)->toHaveCount(1)
        ->and($page->total)->toBe(1)
        ->and($page->hasMorePages())->toBeFalse()
        ->and($page->first()->id)->toBe(4);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'customer_id=12')
        && str_contains($request->url(), 'status=active'));
});

it('all() нь бүх хуудсыг дамжина', function () {
    Http::fakeSequence()
        ->push([
            'data' => [SubscriptionFactory::make(['id' => 1])],
            'meta' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
        ])
        ->push([
            'data' => [SubscriptionFactory::make(['id' => 2])],
            'meta' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 1, 'total' => 2],
        ]);

    $ids = Byl::subscriptions()->all(['status' => 'active'])->pluck('id')->all();

    expect($ids)->toBe([1, 2]);
    Http::assertSentCount(2);
});

it('туршилтын захиалга эхлүүлнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/subscriptions' => Http::response(bylResponse(SubscriptionFactory::trialing([
            'id' => 9,
            'trial_days' => 14,
        ])), 201),
    ]);

    $subscription = Byl::subscriptions()->startTrial(12, 3, 14);

    expect($subscription->onTrial())->toBeTrue()
        ->and($subscription->trialEndsAt)->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request['customer_id'] === 12
        && $request['price_id'] === 3
        && $request['trial_days'] === 14);
});

it('lookup key-ээр туршилт эхлүүлнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/subscriptions' => Http::response(bylResponse(SubscriptionFactory::trialing([
            'id' => 9,
            'trial_days' => 14,
            'price' => 'starter_monthly',
        ])), 201),
    ]);

    $subscription = Byl::subscriptions()->startTrial(12, 'starter_monthly', 14);

    expect($subscription->onTrial())->toBeTrue()
        ->and($subscription->price?->lookupKey)->toBe('starter_monthly');

    Http::assertSent(fn (Request $request) => $request['price'] === 'starter_monthly'
        && ! isset($request['price_id']));
});

it('жагсаалтыг lookup key-ээр filter хийнэ', function () {
    Http::fake([
        'byl.mn/*/subscriptions*' => Http::response([
            'data' => [SubscriptionFactory::make(['id' => 4])],
            'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1],
        ]),
    ]);

    expect(Byl::subscriptions()->list(['price' => 'starter_monthly']))->toHaveCount(1);

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'price=starter_monthly'));
});

it('захиалгыг цуцалж, дахин буцаана', function () {
    Http::fake([
        'byl.mn/*/subscriptions/4/cancel' => Http::response(bylResponse(SubscriptionFactory::make([
            'id' => 4,
            'canceled_at' => '2026-07-25T04:10:00.000000Z',
        ]))),
        'byl.mn/*/subscriptions/4/resume' => Http::response(bylResponse(SubscriptionFactory::make([
            'id' => 4,
            'canceled_at' => null,
        ]))),
    ]);

    $canceled = Byl::subscriptions()->cancel(4);
    $resumed = Byl::subscriptions()->resume(4);

    expect($canceled->cancelRequested())->toBeTrue()
        ->and($canceled->isEntitled())->toBeTrue()
        ->and($resumed->cancelRequested())->toBeFalse();
});

it('бүрэн цуцлагдсан захиалга дээр 409 алдаа буцаана', function () {
    Http::fake([
        'byl.mn/*' => Http::response(['message' => 'Subscription is already canceled.'], 409),
    ]);

    expect(fn () => Byl::subscriptions()->cancel(4))
        ->toThrow(ConflictException::class, 'Subscription is already canceled.');
});
