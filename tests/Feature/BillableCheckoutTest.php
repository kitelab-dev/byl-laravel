<?php

use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Facades\Byl;

it('шинэ захиалгын checkout үүсгэж харилцагчийг холбоно', function () {
    $fake = Byl::fake();

    $user = $this->createUser();

    $checkout = $user->newSubscription('starter_monthly')
        ->successUrl('https://example.mn/success')
        ->allowPromotionCodes()
        ->checkout();

    expect($checkout->url)->toContain('/h/checkout/')
        ->and($user->fresh()->bylCustomerId())->not->toBeNull();

    $fake->assertCustomerCreated(fn (array $payload) => $payload['client_reference_id'] === (string) $user->id)
        ->assertCheckoutCreated(function (array $payload) use ($user) {
            expect($payload['items'])->toBe([['price' => 'starter_monthly', 'quantity' => 1]])
                ->and($payload['customer_id'])->toBe($user->fresh()->bylCustomerId())
                ->and($payload['success_url'])->toBe('https://example.mn/success')
                ->and($payload['allow_promotion_codes'])->toBeTrue();

            return true;
        });
});

it('хэд хэдэн мөчлөгийн төлбөрийг нэг дор авна', function () {
    $fake = Byl::fake();

    $this->createUser(['byl_customer_id' => 12])->newSubscription(3)->cycles(3)->checkout();

    $fake->assertCheckoutCreated(fn (array $payload) => $payload['items'] === [['price_id' => 3, 'quantity' => 3]])
        ->assertNotSent('POST', 'customers');
});

it('туршилтын захиалгыг локал хүснэгтэд бичнэ', function () {
    $fake = Byl::fake();

    $user = $this->createUser(['byl_customer_id' => 12]);

    $subscription = $user->newSubscription(3)->startTrial(14);

    expect($subscription->status)->toBe(SubscriptionStatus::Trialing)
        ->and($subscription->trial_ends_at?->isFuture())->toBeTrue()
        ->and($subscription->billable_id)->toBe($user->id)
        ->and($user->onTrial())->toBeTrue()
        ->and($user->subscribed())->toBeTrue();

    $fake->assertTrialStarted(fn (array $payload) => $payload['customer_id'] === 12
        && $payload['price_id'] === 3
        && $payload['trial_days'] === 14);
});

it('lookup key-ээр туршилт эхлүүлэхийг ойлгомжтой хориглоно', function () {
    Byl::fake();

    expect(fn () => $this->createUser()->newSubscription('starter_monthly')->startTrial(14))
        ->toThrow(LogicException::class, 'үнийн ID шаардлагатай');
});

it('нэг удаагийн checkout-д харилцагч холбогдоно', function () {
    $fake = Byl::fake();

    $user = $this->createUser(['byl_customer_id' => 12]);

    $user->checkout()
        ->addPriceData(15000, 'Гутал', quantity: 2)
        ->collectDeliveryAddress()
        ->create();

    $fake->assertCheckoutCreated(fn (array $payload) => $payload['customer_id'] === 12
        && $payload['delivery_address_collection'] === true
        && $payload['items'][0]['price_data']['unit_amount'] === 15000);
});

it('харилцагчид нэхэмжлэх үүсгэнэ', function () {
    $fake = Byl::fake();

    $invoice = $this->createUser(['byl_customer_id' => 12])->invoiceFor(25000, 'Гишүүнчлэл');

    expect($invoice->amount)->toBe(25000.0);

    $fake->assertInvoiceCreated(fn (array $payload) => $payload['customer_id'] === 12
        && $payload['description'] === 'Гишүүнчлэл');
});

it('billing portal руу чиглүүлнэ', function () {
    Byl::fake();

    $user = $this->createUser(['byl_customer_id' => 12]);

    expect($user->billingPortalUrl())->toContain('/h/billing-portal/')
        ->and($user->redirectToBillingPortal()->getStatusCode())->toBe(302);

    Byl::fake()->assertPortalSessionCreated(fn (array $payload) => $payload['customer_id'] === 12);
});
