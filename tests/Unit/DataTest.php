<?php

use Byl\Laravel\Data\Checkout;
use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Data\Page;
use Byl\Laravel\Data\Payment;
use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Enums\CheckoutStatus;
use Byl\Laravel\Enums\InvoiceStatus;
use Byl\Laravel\Enums\PaymentStatus;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Testing\CheckoutFactory;
use Byl\Laravel\Testing\PaymentFactory;
use Byl\Laravel\Testing\SubscriptionFactory;

it('танигдаагүй enum утга дээр null болно', function () {
    $invoice = Invoice::fromArray(['id' => 1, 'status' => 'brand_new_status']);

    expect($invoice->status)->toBeNull()
        ->and($invoice->raw('status'))->toBe('brand_new_status')
        ->and($invoice->isPaid())->toBeFalse();
});

it('дутуу талбартай хариу дээр ч бүтэн объект үүсгэнэ', function () {
    $invoice = Invoice::fromArray(['id' => 5]);

    expect($invoice->id)->toBe(5)
        ->and($invoice->amount)->toBeNull()
        ->and($invoice->dueDate)->toBeNull()
        ->and($invoice->customer)->toBeNull()
        ->and($invoice->status)->toBeNull();
});

it('toArray нь API-ийн хариуг хэвээр буцаана', function () {
    $payload = ['id' => 1, 'status' => 'paid', 'amount' => 10, 'extra' => ['a' => 1]];

    expect(Invoice::fromArray($payload)->toArray())->toBe($payload)
        ->and(json_decode(json_encode(Invoice::fromArray($payload)), true))->toBe($payload);
});

it('мөнгөн дүнг тоо болгон хувиргана', function () {
    expect(Invoice::fromArray(['amount' => '1500.50', 'status' => 'paid'])->amount)->toBe(1500.5)
        ->and(Invoice::fromArray(['status' => 'paid'])->status)->toBe(InvoiceStatus::Paid);
});

it('захиалгын төлөв эрхтэй эсэхийг зөв тодорхойлно', function (string $status, bool $entitled) {
    expect(Subscription::fromArray(['status' => $status])->isEntitled())->toBe($entitled);
})->with([
    ['trialing', true],
    ['active', true],
    ['past_due', true],
    ['canceled', false],
]);

it('цуцлалт хүссэн боловч дуусаагүй захиалгыг ялгана', function () {
    $requested = Subscription::fromArray(SubscriptionFactory::make([
        'status' => 'active',
        'canceled_at' => '2026-07-20T00:00:00.000000Z',
    ]));

    $finished = Subscription::fromArray(SubscriptionFactory::make([
        'status' => 'canceled',
        'canceled_at' => '2026-07-20T00:00:00.000000Z',
    ]));

    expect($requested->cancelRequested())->toBeTrue()
        ->and($requested->isEntitled())->toBeTrue()
        ->and($finished->cancelRequested())->toBeFalse()
        ->and($finished->status)->toBe(SubscriptionStatus::Canceled);
});

it('Page нь хуудаслалтын мэдээллийг уншиж давтагдана', function () {
    $page = Page::fromResponse([
        'data' => [SubscriptionFactory::make(['id' => 1]), SubscriptionFactory::make(['id' => 2])],
        'meta' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 5],
    ], fn (array $item) => Subscription::fromArray($item));

    expect($page->count())->toBe(2)
        ->and($page->total)->toBe(5)
        ->and($page->lastPage)->toBe(3)
        ->and($page->hasMorePages())->toBeTrue()
        ->and($page->isNotEmpty())->toBeTrue()
        ->and(collect($page)->pluck('id')->all())->toBe([1, 2]);
});

it('Page нь meta байхгүй хариу дээр ч ажиллана', function () {
    $page = Page::fromResponse(['data' => [['id' => 1]]], fn (array $item) => Subscription::fromArray($item));

    expect($page->currentPage)->toBe(1)
        ->and($page->lastPage)->toBe(1)
        ->and($page->total)->toBe(1)
        ->and($page->hasMorePages())->toBeFalse();
});

it('subscription webhook-ийн бодит payload-ыг бүрэн уншина', function () {
    // Byl-ээс ирсэн `subscription.updated` event-ийн `data.object`.
    $subscription = Subscription::fromArray([
        'id' => 3,
        'price' => [
            'id' => 1584,
            'type' => 'recurring',
            'lookup_key' => 'trustapi_monthly',
            'unit_amount' => 39000,
            'recurring_interval' => 'month',
            'recurring_interval_count' => 1,
        ],
        'status' => 'active',
        'is_test' => false,
        'product' => [
            'id' => 1495,
            'name' => 'TrustAPI',
            'client_reference_id' => null,
        ],
        'customer' => [
            'id' => 15716,
            'name' => 'Byl',
            'email' => 'miigaa.sv@gmail.com',
            'client_reference_id' => 'prj_vXyKeplKCwyzfZTTmY1vpj',
        ],
        'created_at' => '2026-08-12T11:04:28.000000Z',
        'project_id' => 712,
        'updated_at' => '2026-08-22T05:59:46.000000Z',
        'canceled_at' => null,
        'trial_ends_at' => null,
        'current_period_end' => '2026-09-12T15:59:59.000000Z',
        'current_period_start' => '2026-08-12T11:04:28.000000Z',
    ]);

    expect($subscription->lookupKey)->toBe('trustapi_monthly')
        ->and($subscription->priceId)->toBe(1584)
        ->and($subscription->productId)->toBe(1495)
        ->and($subscription->product?->name)->toBe('TrustAPI')
        ->and($subscription->customerId)->toBe(15716)
        ->and($subscription->customer?->clientReferenceId)->toBe('prj_vXyKeplKCwyzfZTTmY1vpj')
        ->and($subscription->projectId)->toBe(712)
        ->and($subscription->price?->unitAmount)->toBe(39000.0)
        ->and($subscription->isEntitled())->toBeTrue();
});

it('lookup key-г үнийн объект, дээд түвшин, string гурвуулангаас уншина', function () {
    expect(Subscription::fromArray(['price' => ['id' => 3, 'lookup_key' => 'nested']])->lookupKey)->toBe('nested')
        ->and(Subscription::fromArray(['lookup_key' => 'top_level'])->lookupKey)->toBe('top_level')
        ->and(Subscription::fromArray(['price' => 'echoed_key'])->lookupKey)->toBe('echoed_key')
        // Lookup key тохируулаагүй үнэ — Byl null буцаана.
        ->and(Subscription::fromArray(['price' => ['id' => 3, 'lookup_key' => null]])->lookupKey)->toBeNull()
        ->and(Subscription::fromArray(['price_id' => 3])->lookupKey)->toBeNull();
});

it('checkout webhook-ийн бодит payload-аас харилцагч ба item-ийг уншина', function () {
    // Byl-ээс ирсэн `checkout.completed` event-ийн `data.object`.
    $checkout = Checkout::fromArray([
        'id' => 71091,
        'url' => 'https://byl.mn/h/checkout/71091/HU4Z0nJrvGdAINbFcmWzTalg8W6L6Cfx',
        'mode' => 'payment',
        'items' => [
            [
                'price' => [
                    'id' => 1610,
                    'type' => 'recurring',
                    'unit_amount' => 450,
                    'recurring_interval' => 'month',
                    'recurring_interval_count' => 1,
                ],
                'product' => [
                    'id' => 1517,
                    'name' => 'Mybot pro',
                    'client_reference_id' => null,
                ],
                'quantity' => 1,
                'amount_unit' => 450,
                'amount_total' => 450,
                'amount_subtotal' => 450,
                'adjustable_quantity' => null,
            ],
        ],
        'status' => 'complete',
        'customer' => [
            'id' => 17913,
            'name' => 'Мөнхсайхан Чимэдбазар',
            'client_reference_id' => 'a28ddf8c-51be-464b-a11a-c2ad6beb8a5d',
        ],
        'is_guest' => false,
        'created_at' => '2026-08-22T05:01:07.000000Z',
        'expires_at' => '2026-10-21T16:00:00.000000Z',
        'project_id' => 127,
        'updated_at' => '2026-08-22T05:01:34.000000Z',
        'amount_total' => '450.000000000000',
        'coupon_codes' => [],
        'phone_number' => null,
        'customer_email' => 'chmunkhsaikhan@gmail.com',
        'amount_subtotal' => '450.000000000000',
        'subscription_id' => null,
        'delivery_address' => null,
        'email_collection' => true,
        'client_reference_id' => 'a28ddf8c-51be-464b-a11a-c2ad6beb8a5d',
        'phone_number_collection' => false,
        'delivery_address_collection' => false,
    ]);

    expect($checkout->isComplete())->toBeTrue()
        ->and($checkout->customerId)->toBe(17913)
        ->and($checkout->customerEmail)->toBe('chmunkhsaikhan@gmail.com')
        ->and($checkout->clientReferenceId)->toBe('a28ddf8c-51be-464b-a11a-c2ad6beb8a5d')
        ->and($checkout->amountTotal)->toBe(450.0)
        ->and($checkout->isGuest)->toBeFalse()
        ->and($checkout->items)->toHaveCount(1)
        ->and($checkout->items->first()->priceId)->toBe(1610)
        ->and($checkout->items->first()->productName)->toBe('Mybot pro')
        ->and($checkout->items->first()->quantity)->toBe(1)
        ->and($checkout->items->first()->amountTotal)->toBe(450.0);
});

it('банкны шилжүүлгийн payment payload-ыг бүрэн уншина', function () {
    // Byl-ээс ирсэн `payment.awaiting_verification` event-ийн `data.object`.
    $payment = Payment::fromArray([
        'id' => 4021,
        'project_id' => 712,
        'status' => 'pending',
        'driver' => 'bank_transfer',
        'amount' => '150000.000000000000',
        'description' => 'Захиалга #1024',
        'reference' => '482913',
        'bank_name' => 'Хаан банк',
        'account_number' => '5001234567',
        'claimed_at' => '2026-08-22T05:10:00.000000Z',
        'expires_at' => '2026-08-25T05:01:07.000000Z',
        'customer_email' => 'chmunkhsaikhan@gmail.com',
        'phone_number' => '99112233',
        'is_test' => false,
        'created_at' => '2026-08-22T05:01:07.000000Z',
        'payable' => [
            'type' => 'checkout',
            'id' => 71091,
            'url' => 'https://byl.mn/h/checkout/71091/HU4Z0nJrvGdAINbFcmWzTalg8W6L6Cfx',
            'status' => 'pending',
        ],
    ]);

    expect($payment->id)->toBe(4021)
        ->and($payment->projectId)->toBe(712)
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->isPending())->toBeTrue()
        ->and($payment->isPaid())->toBeFalse()
        ->and($payment->driver)->toBe('bank_transfer')
        ->and($payment->isBankTransfer())->toBeTrue()
        ->and($payment->amount)->toBe(150000.0)
        ->and($payment->description)->toBe('Захиалга #1024')
        ->and($payment->reference)->toBe('482913')
        ->and($payment->bankName)->toBe('Хаан банк')
        ->and($payment->accountNumber)->toBe('5001234567')
        ->and($payment->claimedAt?->toDateTimeString())->toBe('2026-08-22 05:10:00')
        ->and($payment->expiresAt?->toDateTimeString())->toBe('2026-08-25 05:01:07')
        ->and($payment->customerEmail)->toBe('chmunkhsaikhan@gmail.com')
        ->and($payment->phoneNumber)->toBe('99112233')
        ->and($payment->isTest)->toBeFalse()
        ->and($payment->createdAt?->toDateTimeString())->toBe('2026-08-22 05:01:07')
        ->and($payment->payableType())->toBe('checkout')
        ->and($payment->payableId())->toBe(71091)
        ->and($payment->payableUrl())->toBe('https://byl.mn/h/checkout/71091/HU4Z0nJrvGdAINbFcmWzTalg8W6L6Cfx')
        ->and($payment->payableStatus())->toBe('pending')
        ->and($payment->payableNumber())->toBeNull()
        ->and($payment->isForCheckout())->toBeTrue()
        ->and($payment->isForInvoice())->toBeFalse();
});

it('нэхэмжлэхийн payment дээр payable дугаарыг уншина', function () {
    $payment = Payment::fromArray(PaymentFactory::make([
        'payable' => [
            'type' => 'invoice',
            'id' => 71,
            'url' => 'https://byl.mn/h/invoice/71',
            'status' => 'open',
            'number' => 'INV-0071',
        ],
    ]));

    expect($payment->payableType())->toBe('invoice')
        ->and($payment->payableNumber())->toBe('INV-0071')
        ->and($payment->isForInvoice())->toBeTrue()
        ->and($payment->isForCheckout())->toBeFalse();
});

it('дутуу талбартай payment дээр ч бүтэн объект үүсгэнэ', function () {
    $payment = Payment::fromArray(['id' => 9]);

    expect($payment->id)->toBe(9)
        ->and($payment->status)->toBeNull()
        ->and($payment->amount)->toBeNull()
        ->and($payment->reference)->toBeNull()
        ->and($payment->claimedAt)->toBeNull()
        ->and($payment->payable)->toBeNull()
        ->and($payment->payableType())->toBeNull()
        ->and($payment->payableId())->toBeNull()
        ->and($payment->isTest)->toBeFalse()
        ->and($payment->isPending())->toBeFalse()
        ->and($payment->toArray())->toBe(['id' => 9]);
});

it('танигдаагүй payment төлөв дээр null болно', function () {
    $payment = Payment::fromArray(['id' => 1, 'status' => 'brand_new_status']);

    expect($payment->status)->toBeNull()
        ->and($payment->raw('status'))->toBe('brand_new_status')
        ->and($payment->isPaid())->toBeFalse();
});

it('payment төлөвийн helper-ууд зөв ажиллана', function () {
    expect(PaymentStatus::Paid->isPaid())->toBeTrue()
        ->and(PaymentStatus::Pending->isPaid())->toBeFalse()
        ->and(PaymentStatus::Pending->isPending())->toBeTrue()
        ->and(PaymentStatus::Failed->isPending())->toBeFalse()
        ->and(PaymentStatus::tryFrom('refunded'))->toBe(PaymentStatus::Refunded);
});

it('баталгаажуулалт хүлээж буй checkout нь pending төлөвтэй', function () {
    $checkout = Checkout::fromArray(CheckoutFactory::make(['status' => 'pending']));

    expect($checkout->status)->toBe(CheckoutStatus::Pending)
        ->and($checkout->status->isPending())->toBeTrue()
        ->and($checkout->isPending())->toBeTrue()
        ->and($checkout->isComplete())->toBeFalse()
        ->and($checkout->isExpired())->toBeFalse()
        ->and(CheckoutStatus::Complete->isPending())->toBeFalse();
});
