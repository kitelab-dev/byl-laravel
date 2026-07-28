<?php

use Byl\Laravel\Exceptions\ServerException;
use Byl\Laravel\Exceptions\ValidationException;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\InvoiceFactory;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('byl.retry', ['times' => 3, 'sleep' => 0]);
});

it('серверийн алдаа дээр дахин хүсэлт илгээнэ', function () {
    Http::fakeSequence()
        ->push(['message' => 'Server error.'], 500)
        ->push(bylResponse(InvoiceFactory::make(['id' => 5708])), 200);

    expect(Byl::invoices()->find(5708)->id)->toBe(5708);

    Http::assertSentCount(2);
});

it('429 дээр дахин хүсэлт илгээнэ', function () {
    Http::fakeSequence()
        ->push(['message' => 'Too many requests.'], 429)
        ->push(bylResponse(InvoiceFactory::make(['id' => 1])), 200);

    Byl::invoices()->find(1);

    Http::assertSentCount(2);
});

it('холболтын алдаа дээр дахин хүсэлт илгээнэ', function () {
    $attempts = 0;

    Http::fake(function () use (&$attempts) {
        $attempts++;

        if ($attempts < 3) {
            throw new HttpConnectionException('cURL error 28: timeout');
        }

        return Http::response(bylResponse(InvoiceFactory::make(['id' => 1])));
    });

    Byl::invoices()->find(1);

    expect($attempts)->toBe(3);
});

it('validation алдаа дээр дахин хүсэлт илгээхгүй', function () {
    Http::fake(['byl.mn/*' => Http::response(['message' => 'Invalid.'], 422)]);

    expect(fn () => Byl::invoices()->create([]))->toThrow(ValidationException::class);

    Http::assertSentCount(1);
});

it('бүх дахин хүсэлт бүтэлгүйтвэл алдаа шиднэ', function () {
    Http::fake(['byl.mn/*' => Http::response(['message' => 'Server error.'], 503)]);

    expect(fn () => Byl::invoices()->find(1))->toThrow(ServerException::class);

    Http::assertSentCount(3);
});
