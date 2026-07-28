<?php

use Byl\Laravel\Facades\Byl;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/billing-portal/sessions' => Http::response(bylResponse([
            'url' => 'https://byl.mn/h/billing-portal/Yi7smBuk',
            'expires_at' => now()->addMinutes(30)->toJSON(),
        ]), 201),
    ]);
});

it('portal session үүсгэнэ', function () {
    $session = Byl::billingPortal()->createSession(12);

    expect($session->url)->toBe('https://byl.mn/h/billing-portal/Yi7smBuk')
        ->and($session->isExpired())->toBeFalse();

    Http::assertSent(fn (Request $request) => $request['customer_id'] === 12);
});

it('session объектыг буцаахад portal руу redirect хийнэ', function () {
    Route::get('/billing', fn () => Byl::billingPortal()->createSession(12));

    $this->get('/billing')->assertRedirect('https://byl.mn/h/billing-portal/Yi7smBuk');
});
