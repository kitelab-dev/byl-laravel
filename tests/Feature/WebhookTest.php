<?php

use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Events\CheckoutCompleted;
use Byl\Laravel\Events\InvoicePaid;
use Byl\Laravel\Events\SubscriptionCanceled;
use Byl\Laravel\Events\SubscriptionRenewed;
use Byl\Laravel\Events\WebhookReceived;
use Byl\Laravel\Testing\FakeWebhook;
use Byl\Laravel\Webhooks\WebhookSignature;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

it('гарын үсэг зөв бол event илгээнэ', function () {
    Event::fake();

    $webhook = FakeWebhook::invoicePaid(['id' => 71, 'amount' => 500]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())
        ->assertOk()
        ->assertJson(['received' => true]);

    Event::assertDispatched(WebhookReceived::class, fn (WebhookReceived $event) => $event->event->is(WebhookEventType::InvoicePaid)
        && $event->event->id === 87);

    Event::assertDispatched(InvoicePaid::class, function (InvoicePaid $event) {
        expect($event->invoice->id)->toBe(71)
            ->and($event->invoice->amount)->toBe(500.0)
            ->and($event->invoice->isPaid())->toBeTrue();

        return true;
    });
});

it('гарын үсэг буруу бол 401 буцаана', function () {
    Event::fake();

    $webhook = FakeWebhook::invoicePaid();

    $this->postJson(route('byl.webhook'), $webhook->payload(), [
        WebhookSignature::HEADER => 'wrong-signature',
    ])->assertStatus(401);

    Event::assertNotDispatched(WebhookReceived::class);
});

it('гарын үсэг байхгүй бол 401 буцаана', function () {
    $webhook = FakeWebhook::invoicePaid();

    $this->postJson(route('byl.webhook'), $webhook->payload())->assertStatus(401);
});

it('checkout.completed event-ийг хувиргана', function () {
    Event::fake();

    $webhook = FakeWebhook::checkoutCompleted(['id' => 13338]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    Event::assertDispatched(CheckoutCompleted::class, function (CheckoutCompleted $event) {
        expect($event->checkout->id)->toBe(13338)
            ->and($event->checkout->isComplete())->toBeTrue()
            ->and($event->checkout->items)->toHaveCount(1);

        return true;
    });
});

it('subscription event-үүдийг хувиргана', function (WebhookEventType $type, string $class) {
    Event::fake();

    $webhook = FakeWebhook::subscription($type, ['id' => 4]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    Event::assertDispatched($class);
})->with([
    [WebhookEventType::SubscriptionRenewed, SubscriptionRenewed::class],
    [WebhookEventType::SubscriptionCanceled, SubscriptionCanceled::class],
]);

it('canceled event дээр захиалгын төлөв canceled байна', function () {
    Event::fake();

    $webhook = FakeWebhook::subscription(WebhookEventType::SubscriptionCanceled, [
        'id' => 4,
        'status' => 'canceled',
        'canceled_at' => '2026-07-20T04:10:00.000000Z',
    ]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    Event::assertDispatched(SubscriptionCanceled::class, function (SubscriptionCanceled $event) {
        expect($event->subscription->status)->toBe(SubscriptionStatus::Canceled)
            ->and($event->subscription->isEntitled())->toBeFalse();

        return true;
    });
});

it('танигдаагүй төрөл дээр зөвхөн WebhookReceived илгээнэ', function () {
    Event::fake();

    $webhook = FakeWebhook::make('something.new', 'thing', ['id' => 1]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    Event::assertDispatched(WebhookReceived::class, fn (WebhookReceived $event) => $event->event->type() === null
        && $event->event->resource() === null);
    Event::assertNotDispatched(InvoicePaid::class);
});

it('давхардсан event-ийг тохиргоогоор хаана', function () {
    config()->set('byl.webhook.prevent_duplicate_events', true);

    Event::fake();

    $webhook = FakeWebhook::invoicePaid()->withEventId(555);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();
    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())
        ->assertOk()
        ->assertJson(['duplicate' => true]);

    Event::assertDispatchedTimes(InvoicePaid::class, 1);
});

it('давхардлын хамгаалалт анхдагчаар унтраалттай', function () {
    Event::fake();

    $webhook = FakeWebhook::invoicePaid()->withEventId(556);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();
    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    Event::assertDispatchedTimes(InvoicePaid::class, 2);
});

it('өөрийн route дээр byl-signature middleware ашиглаж болно', function () {
    Route::post('/my/webhook', fn () => response()->json(['ok' => true]))->middleware('byl-signature');

    $webhook = FakeWebhook::invoicePaid();

    $this->postJson('/my/webhook', $webhook->payload(), $webhook->headers())->assertOk();
    $this->postJson('/my/webhook', $webhook->payload())->assertStatus(401);
});

it('webhook route нь тохируулсан хаяг дээр бүртгэгдэнэ', function () {
    expect(route('byl.webhook'))->toBe(url('byl/webhook'));
});
