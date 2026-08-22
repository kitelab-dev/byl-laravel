<?php

use Byl\Laravel\Enums\CheckoutStatus;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\CheckoutFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/checkouts' => Http::response(bylResponse([
            'id' => 13338,
            'url' => 'https://byl.mn/h/checkout/13338/Yi7smBuk',
        ]), 201),
    ]);
});

it('checkout үүсгэж url буцаана', function () {
    $checkout = Byl::checkouts()->create([
        'success_url' => 'https://example.mn/purchase/success',
        'items' => [['price' => 'starter_monthly', 'quantity' => 1]],
    ]);

    expect($checkout->id)->toBe(13338)
        ->and($checkout->url)->toBe('https://byl.mn/h/checkout/13338/Yi7smBuk');
});

it('builder нь бүх параметрийг зөв payload болгоно', function () {
    Byl::checkouts()->builder()
        ->successUrl('https://example.mn/success')
        ->cancelUrl('https://example.mn/cancel')
        ->addPrice('starter_monthly', 2, ['min' => 1, 'max' => 5])
        ->addPriceId(3)
        ->addPriceData(1000, 'Product 1', 2, 'sku-1')
        ->allowPromotionCodes()
        ->collectPhoneNumber()
        ->collectDeliveryAddress()
        ->customer(12)
        ->customerEmail('customer@example.mn')
        ->clientReferenceId('order_991')
        ->subscription(4)
        ->discount(5400, 'Хямдрал')
        ->with('mode', 'payment')
        ->create();

    Http::assertSent(function (Request $request) {
        expect($request->data())->toBe([
            'success_url' => 'https://example.mn/success',
            'cancel_url' => 'https://example.mn/cancel',
            'allow_promotion_codes' => true,
            'phone_number_collection' => true,
            'delivery_address_collection' => true,
            'customer_id' => 12,
            'customer_email' => 'customer@example.mn',
            'client_reference_id' => 'order_991',
            'subscription_id' => 4,
            'mode' => 'payment',
            'items' => [
                [
                    'price' => 'starter_monthly',
                    'quantity' => 2,
                    'adjustable_quantity' => ['min' => 1, 'max' => 5, 'enabled' => true],
                ],
                ['price_id' => 3, 'quantity' => 1],
                [
                    'price_data' => [
                        'unit_amount' => 1000,
                        'product_data' => ['name' => 'Product 1', 'client_reference_id' => 'sku-1'],
                    ],
                    'quantity' => 2,
                ],
            ],
            'discounts' => [['amount' => 5400, 'description' => 'Хямдрал']],
        ]);

        return true;
    });
});

it('builder нь хоосон discounts талбарыг payload-д оруулахгүй', function () {
    $payload = Byl::checkouts()->builder()->addPrice('starter_monthly')->toArray();

    expect($payload)->toBe(['items' => [['price' => 'starter_monthly', 'quantity' => 1]]]);
});

it('checkout лавлахад items болон хөнгөлөлтийн код parse хийгдэнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/checkouts/13338' => Http::response(bylResponse(CheckoutFactory::completed([
            'payment_method' => 'qpay',
            'id' => 13338,
            'coupon_codes' => [
                [
                    'code' => 'SUMMER2025',
                    'coupon_name' => 'Summer Sale',
                    'discount_amount' => '10.00',
                    'redeemed_at' => '2025-08-03T10:18:43.000000Z',
                ],
            ],
        ]))),
    ]);

    $checkout = Byl::checkouts()->find(13338);

    expect($checkout->status)->toBe(CheckoutStatus::Complete)
        ->and($checkout->isComplete())->toBeTrue()
        ->and($checkout->paymentMethod)->toBe('qpay')
        ->and($checkout->items)->toHaveCount(1)
        ->and($checkout->items->first()->productName)->toBe('Product 1')
        ->and($checkout->items->first()->amountTotal)->toBe(1000.0)
        ->and($checkout->couponCodes->first()->code)->toBe('SUMMER2025')
        ->and($checkout->discountTotal())->toBe(10.0);
});

it('checkout объектыг controller-оос буцаахад төлбөрийн хуудас руу redirect хийнэ', function () {
    Route::get('/pay', fn () => Byl::checkouts()->create([
        'items' => [['price' => 'starter_monthly', 'quantity' => 1]],
    ]));

    $this->get('/pay')->assertRedirect('https://byl.mn/h/checkout/13338/Yi7smBuk');
});
