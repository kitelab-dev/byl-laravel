<?php

use Byl\Laravel\Data\Checkout;
use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Data\Payment;
use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Enums\PaymentStatus;
use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Events\PaymentAwaitingVerification;
use Byl\Laravel\Events\PaymentVerificationDue;
use Byl\Laravel\Testing\FakeWebhook;
use Byl\Laravel\Testing\PaymentFactory;
use Byl\Laravel\Webhooks\WebhookEvent;
use Byl\Laravel\Webhooks\WebhookEventMap;

it('payload-аас event-ийн мэдээллийг уншина', function () {
    $event = WebhookEvent::fromArray(FakeWebhook::invoicePaid(['id' => 71])->payload());

    expect($event->type)->toBe('invoice.paid')
        ->and($event->type())->toBe(WebhookEventType::InvoicePaid)
        ->and($event->is(WebhookEventType::InvoicePaid))->toBeTrue()
        ->and($event->is('checkout.completed'))->toBeFalse()
        ->and($event->object)->toBe('invoice')
        ->and($event->projectId)->toBe(1)
        ->and($event->createdAt)->not->toBeNull()
        ->and($event->objectData()['id'])->toBe(71)
        ->and($event->raw('data.object.id'))->toBe(71);
});

it('object талбараас хамааран тохирох DTO буцаана', function (string $object, string $class) {
    $webhook = match ($object) {
        'invoice' => FakeWebhook::invoicePaid(),
        'checkout' => FakeWebhook::checkoutCompleted(),
        'subscription' => FakeWebhook::subscription(WebhookEventType::SubscriptionRenewed),
    };

    expect(WebhookEvent::fromArray($webhook->payload())->resource())->toBeInstanceOf($class);
})->with([
    ['invoice', Invoice::class],
    ['checkout', Checkout::class],
    ['subscription', Subscription::class],
]);

it('хоосон payload дээр ч алдаа гаргахгүй', function () {
    $event = WebhookEvent::fromArray([]);

    expect($event->type)->toBe('')
        ->and($event->type())->toBeNull()
        ->and($event->id)->toBeNull()
        ->and($event->objectData())->toBe([])
        ->and($event->resource())->toBeNull();
});

it('webhook дахь subscription-ийн бүтээгдэхүүнийг уншина', function () {
    $event = WebhookEvent::fromArray([
        'id' => 87,
        'project_id' => 1,
        'type' => 'subscription.renewed',
        'object' => 'subscription',
        'data' => [
            'object' => [
                'id' => 4,
                'status' => 'active',
                'customer' => ['id' => 12, 'client_reference_id' => 'user_842'],
                'product' => ['id' => 7, 'name' => 'Starter багц'],
                'price' => ['id' => 3, 'type' => 'recurring', 'unit_amount' => 30000, 'recurring_interval' => 'month'],
                'current_period_end' => '2026-08-23T15:59:59.000000Z',
            ],
        ],
    ]);

    $subscription = $event->subscription();

    expect($subscription->customerId)->toBe(12)
        ->and($subscription->productId)->toBe(7)
        ->and($subscription->priceId)->toBe(3)
        ->and($subscription->product?->name)->toBe('Starter багц')
        ->and($subscription->customer?->clientReferenceId)->toBe('user_842')
        ->and($subscription->isEntitled())->toBeTrue();
});

it('банкны шилжүүлгийн event-үүдийг Payment DTO болгон уншина', function (string $type) {
    $webhook = FakeWebhook::make($type, 'payment', PaymentFactory::awaitingVerification(['id' => 4021]));

    $event = WebhookEvent::fromArray($webhook->payload());

    expect($event->type)->toBe($type)
        ->and($event->type())->toBe(WebhookEventType::from($type))
        ->and($event->object)->toBe('payment')
        ->and($event->resource())->toBeInstanceOf(Payment::class)
        ->and($event->payment()->id)->toBe(4021)
        ->and($event->payment()->status)->toBe(PaymentStatus::Pending)
        ->and($event->payment()->reference)->toBe('482913')
        ->and($event->payment()->isBankTransfer())->toBeTrue()
        ->and($event->payment()->claimedAt)->not->toBeNull();
})->with([
    'payment.awaiting_verification',
    'payment.verification_due',
]);

it('банкны шилжүүлгийн event-үүдийг тохирох Laravel event класстай холбоно', function (WebhookEventType $type, string $class) {
    $webhook = FakeWebhook::make($type, 'payment', PaymentFactory::awaitingVerification());

    $laravelEvent = WebhookEventMap::make(WebhookEvent::fromArray($webhook->payload()));

    expect($laravelEvent)->toBeInstanceOf($class)
        ->and($laravelEvent->payment)->toBeInstanceOf(Payment::class)
        ->and($laravelEvent->payment->isPending())->toBeTrue()
        ->and($laravelEvent->event->type())->toBe($type);
})->with([
    [WebhookEventType::PaymentAwaitingVerification, PaymentAwaitingVerification::class],
    [WebhookEventType::PaymentVerificationDue, PaymentVerificationDue::class],
]);
