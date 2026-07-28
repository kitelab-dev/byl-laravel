<?php

use Byl\Laravel\Enums\InvoiceStatus;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Exceptions\ValidationException;
use Byl\Laravel\Facades\Byl;
use Illuminate\Support\Facades\Http;

it('fake нь нэхэмжлэхийн бодит бүтэцтэй хариу буцаана', function () {
    Byl::fake();

    $invoice = Byl::invoices()->create(['amount' => 500, 'description' => 'Гутал']);

    expect($invoice->id)->toBeInt()
        ->and($invoice->amount)->toBe(500.0)
        ->and($invoice->description)->toBe('Гутал')
        ->and($invoice->status)->toBe(InvoiceStatus::Open)
        ->and($invoice->url)->toContain('/h/invoice/');

    Byl::fake()->assertInvoiceCreated(fn (array $payload) => $payload['amount'] === 500);
});

it('fake нь checkout, customer, portal session-ийг боловсруулна', function () {
    $fake = Byl::fake();

    $customer = Byl::customers()->create(['email' => 'a@b.mn', 'client_reference_id' => 'user_1']);
    $checkout = Byl::checkouts()->builder()->addPrice('starter_monthly')->customer($customer->id)->create();
    $session = Byl::billingPortal()->createSession($customer->id);

    expect($customer->clientReferenceId)->toBe('user_1')
        ->and($checkout->url)->toContain('/h/checkout/')
        ->and($session->url)->toContain('/h/billing-portal/')
        ->and($session->isExpired())->toBeFalse();

    $fake->assertCustomerCreated()
        ->assertCheckoutCreated(fn (array $payload) => $payload['customer_id'] === $customer->id)
        ->assertPortalSessionCreated()
        ->assertSentCount(3);
});

it('fake нь захиалгын үйлдлүүдийг боловсруулна', function () {
    $fake = Byl::fake();

    $trial = Byl::subscriptions()->startTrial(12, 3, 14);
    $canceled = Byl::subscriptions()->cancel(4);
    $resumed = Byl::subscriptions()->resume(4);
    $list = Byl::subscriptions()->list();

    expect($trial->status)->toBe(SubscriptionStatus::Trialing)
        ->and($trial->trialEndsAt?->isFuture())->toBeTrue()
        ->and($canceled->id)->toBe(4)
        ->and($canceled->cancelRequested())->toBeTrue()
        ->and($resumed->cancelRequested())->toBeFalse()
        ->and($list->isEmpty())->toBeTrue();

    $fake->assertTrialStarted(fn (array $payload) => $payload['trial_days'] === 14)
        ->assertSubscriptionCanceled(4)
        ->assertSubscriptionResumed(4);
});

it('fake хариуг өөрөө тодорхойлж болно', function () {
    $fake = Byl::fake()->respond('GET', 'invoices/*', [
        'id' => 99,
        'status' => 'paid',
        'amount' => 12345,
    ]);

    $invoice = Byl::invoices()->find(99);

    expect($invoice->amount)->toBe(12345.0)
        ->and($invoice->isPaid())->toBeTrue();

    $fake->assertSent('GET', 'invoices/99');
});

it('fake алдаатай хариу симуляц хийж болно', function () {
    Byl::fake()->respondWithError('POST', 'invoices', 422, [
        'message' => 'The amount field is required.',
        'errors' => ['amount' => ['The amount field is required.']],
    ]);

    expect(fn () => Byl::invoices()->create([]))
        ->toThrow(ValidationException::class, 'The amount field is required.');
});

it('fake нь хүсэлт илгээгээгүйг шалгана', function () {
    Byl::fake()->assertNothingSent()->assertNotSent('POST', 'invoices');
});

it('fake токен тохируулаагүй орчинд ч ажиллана', function () {
    config()->set('byl.token', null);
    config()->set('byl.project_id', null);

    Byl::fake();

    expect(Byl::invoices()->createFor(1000)->id)->toBeInt();
});

it('fake нь өөр төслийн хүсэлтийг ч боловсруулна', function () {
    $fake = Byl::fake();

    $invoice = Byl::project(7)->invoices()->createFor(1000);

    expect($invoice->id)->toBeInt();

    $fake->assertSentForProject(7, 'POST', 'invoices')
        ->assertSent('POST', 'invoices');
});

it('fake нь Http::fake-тэй нэг Factory дээр ажиллана', function () {
    Byl::fake();

    Byl::invoices()->createFor(1000);

    Http::assertSentCount(1);
});
