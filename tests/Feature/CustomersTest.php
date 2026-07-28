<?php

use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\CustomerFactory;
use Byl\Laravel\Testing\SubscriptionFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('харилцагч үүсгэнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/customers' => Http::response(bylResponse(CustomerFactory::make([
            'id' => 12,
            'client_reference_id' => 'user_842',
        ])), 201),
    ]);

    $customer = Byl::customers()->create([
        'email' => 'customer@example.mn',
        'name' => 'Бат-Эрдэнэ',
        'client_reference_id' => 'user_842',
    ]);

    expect($customer->id)->toBe(12)
        ->and($customer->clientReferenceId)->toBe('user_842')
        ->and($customer->isSubscribed())->toBeFalse();

    Http::assertSent(fn (Request $request) => $request['email'] === 'customer@example.mn');
});

it('upsert нь create-тэй ижил хүсэлт илгээнэ', function () {
    Http::fake(['byl.mn/*' => Http::response(bylResponse(CustomerFactory::make()), 201)]);

    Byl::customers()->upsert(['email' => 'customer@example.mn', 'client_reference_id' => 'user_1']);

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/customers'));
});

it('харилцагчийн эрхтэй захиалгуудыг parse хийнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/customers/12' => Http::response(bylResponse(CustomerFactory::subscribed(
            ['id' => 12],
            [SubscriptionFactory::make(['id' => 4, 'status' => 'active'])],
        ))),
    ]);

    $customer = Byl::customers()->find(12);

    expect($customer->subscriptions)->toHaveCount(1)
        ->and($customer->isSubscribed())->toBeTrue()
        ->and($customer->subscriptionForProduct(7)?->id)->toBe(4)
        ->and($customer->subscriptionForPrice(3)?->id)->toBe(4)
        ->and($customer->subscriptionForLookupKey('starter_monthly')?->id)->toBe(4)
        ->and($customer->subscriptionForLookupKey('growth_yearly'))->toBeNull();
});

it('client_reference_id-ээр лавлана', function () {
    Http::fake([
        'byl.mn/*/customers/by-client-reference-id/user_842' => Http::response(bylResponse(
            CustomerFactory::make(['id' => 12, 'client_reference_id' => 'user_842'])
        )),
    ]);

    expect(Byl::customers()->findByClientReferenceId('user_842')->id)->toBe(12);
});

it('олдоогүй client_reference_id дээр null буцаах хувилбартай', function () {
    Http::fake([
        'byl.mn/*' => Http::response(['message' => 'Not found.'], 404),
    ]);

    expect(Byl::customers()->findByClientReferenceIdOrNull('user_missing'))->toBeNull();
});
