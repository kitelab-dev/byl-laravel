<?php

use Byl\Laravel\Billing\Models\Subscription;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\FakeWebhook;
use Byl\Laravel\Testing\SubscriptionFactory;
use Byl\Laravel\Tests\Fixtures\User;
use Illuminate\Support\Carbon;

function postSubscriptionWebhook(object $test, WebhookEventType $type, array $subscription): void
{
    $webhook = FakeWebhook::subscription($type, $subscription);

    $test->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();
}

it('subscription.created webhook локал захиалга үүсгэнэ', function () {
    $user = $this->createUser();

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'status' => 'active',
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    $user->refresh();

    expect($user->byl_customer_id)->toBe(12)      // харилцагчийн ID ч холбогдоно
        ->and($user->subscribed())->toBeTrue()
        ->and($user->bylSubscription()->byl_id)->toBe(4)
        ->and($user->bylSubscription()->lookup_key)->toBe('starter_monthly')
        ->and($user->bylSubscription()->product_id)->toBe(7);
});

it('дараагийн webhook ижил захиалгыг шинэчилнэ', function () {
    $user = $this->createUser();

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'status' => 'active',
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCanceled, [
        'id' => 4,
        'status' => 'canceled',
        'canceled_at' => Carbon::now()->toJSON(),
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    expect($user->bylSubscriptions()->count())->toBe(1)
        ->and($user->fresh()->subscribed())->toBeFalse()
        ->and($user->bylSubscriptions()->first()->status)->toBe(SubscriptionStatus::Canceled);
});

it('renewal webhook мөчлөгийн хугацааг шинэчилнэ', function () {
    $user = $this->createUser();

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'status' => 'active',
        'current_period_end' => '2026-08-23T15:59:59.000000Z',
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionRenewed, [
        'id' => 4,
        'status' => 'active',
        'current_period_end' => '2026-09-23T15:59:59.000000Z',
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    expect($user->bylSubscriptions()->first()->current_period_end?->toDateString())->toBe('2026-09-23');
});

it('танигдахгүй client_reference_id-г алгасана', function () {
    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'customer' => ['id' => 12, 'client_reference_id' => 'no-such-user'],
    ]);

    expect(Subscription::count())->toBe(0);
});

it('billable модель тохируулаагүй бол sync хийхгүй', function () {
    config()->set('byl.billable.model', null);

    $user = $this->createUser();

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    expect($user->bylSubscriptions()->count())->toBe(0);
});

it('өөрийн resolver-ээр billable-ыг олж болно', function () {
    config()->set('byl.billable.model', null);

    $user = $this->createUser(['email' => 'resolver@example.mn']);

    Byl::resolveBillableUsing(fn (string $reference) => User::where('email', $reference)->first());

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'status' => 'active',
        'customer' => ['id' => 12, 'client_reference_id' => 'resolver@example.mn'],
    ]);

    expect($user->fresh()->subscribed())->toBeTrue();
});

it('subscription биш webhook-д хөндөхгүй', function () {
    $user = $this->createUser();

    $webhook = FakeWebhook::invoicePaid(['id' => 71]);

    $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())->assertOk();

    expect($user->bylSubscriptions()->count())->toBe(0);
});

it('lookup key тохируулаагүй үнэ дээр lookup_key хоосон үлдэнэ', function () {
    $user = $this->createUser();

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, SubscriptionFactory::withoutLookupKey([
        'id' => 4,
        'status' => 'active',
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]));

    // Эрхийн шалгалт үнийн ID / бүтээгдэхүүнээр ажилласаар байх ёстой.
    expect($user->bylSubscription()->lookup_key)->toBeNull()
        ->and($user->subscribed())->toBeTrue()
        ->and($user->subscribedToPrice(3))->toBeTrue()
        ->and($user->subscribedToProduct(7))->toBeTrue();
});

it('үнэ солигдоход хуучин lookup key үлдэхгүй', function () {
    $user = $this->createUser();

    postSubscriptionWebhook($this, WebhookEventType::SubscriptionCreated, [
        'id' => 4,
        'status' => 'active',
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]);

    expect($user->bylSubscription()->lookup_key)->toBe('starter_monthly');

    // Lookup key тохируулаагүй өөр үнэ рүү солигдлоо.
    postSubscriptionWebhook($this, WebhookEventType::SubscriptionUpdated, SubscriptionFactory::withoutLookupKey([
        'id' => 4,
        'status' => 'active',
        'price' => ['id' => 9, 'type' => 'recurring', 'unit_amount' => 50000],
        'customer' => ['id' => 12, 'client_reference_id' => (string) $user->id],
    ]));

    $subscription = $user->bylSubscription();

    expect($subscription->price_id)->toBe(9)
        ->and($subscription->lookup_key)->toBeNull()
        ->and($user->subscribed('starter_monthly'))->toBeFalse();
});
