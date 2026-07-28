<?php

use Byl\Laravel\Enums\SubscriptionStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth', 'byl.subscribed'])->get('/members', fn () => 'ok');
    Route::middleware(['web', 'auth', 'byl.subscribed:growth_monthly'])->get('/pro', fn () => 'pro');
});

function giveSubscription(object $user, string $lookupKey = 'starter_monthly'): void
{
    $user->bylSubscriptions()->create([
        'byl_id' => random_int(1, 9999),
        'byl_customer_id' => 12,
        'product_id' => 7,
        'price_id' => 3,
        'lookup_key' => $lookupKey,
        'status' => SubscriptionStatus::Active->value,
        'current_period_end' => Carbon::now()->addMonth(),
    ]);
}

it('эрхтэй хэрэглэгчийг оруулна', function () {
    $user = $this->createUser();
    giveSubscription($user);

    $this->actingAs($user)->get('/members')->assertOk()->assertSee('ok');
});

it('эрхгүй хэрэглэгчид 403 буцаана', function () {
    $this->actingAs($this->createUser())->get('/members')->assertForbidden();
});

it('тодорхой багц шаардаж болно', function () {
    $user = $this->createUser();
    giveSubscription($user, 'starter_monthly');

    $this->actingAs($user)->get('/pro')->assertForbidden();

    giveSubscription($user, 'growth_monthly');

    $this->actingAs($user)->get('/pro')->assertOk();
});

it('redirect_to тохируулсан бол чиглүүлнэ', function () {
    config()->set('byl.billable.redirect_to', '/billing');

    $this->actingAs($this->createUser())->get('/members')->assertRedirect('/billing');
});

it('JSON хүсэлтэд 403 JSON буцаана', function () {
    config()->set('byl.billable.redirect_to', '/billing');

    $this->actingAs($this->createUser())
        ->getJson('/members')
        ->assertStatus(403)
        ->assertJsonStructure(['message']);
});
