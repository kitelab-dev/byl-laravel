<?php

use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\InvoiceFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('өөр төслийн клиент үүсгэнэ', function () {
    Http::fake(['byl.mn/*' => Http::response(bylResponse(InvoiceFactory::make()), 201)]);

    Byl::project(7)->invoices()->createFor(1000);

    Http::assertSent(fn (Request $request) => str_starts_with(
        $request->url(),
        'https://byl.mn/api/v1/projects/7/invoices'
    ));
});

it('төсөл тус бүрт өөр токен дамжуулж болно', function () {
    Http::fake(['byl.mn/*' => Http::response(bylResponse(InvoiceFactory::make()), 201)]);

    Byl::project(7, 'other-token')->invoices()->createFor(1000);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer other-token'));
});

it('withToken нь тохиргооны токеныг солино', function () {
    Http::fake(['byl.mn/*' => Http::response(bylResponse(InvoiceFactory::make()), 201)]);

    Byl::withToken('temp-token')->invoices()->createFor(1000);

    Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer temp-token')
        && str_contains($request->url(), '/projects/1/'));
});

it('base_url тохиргоог хэрэглэнэ', function () {
    config()->set('byl.base_url', 'https://byl.test');

    Http::fake(['byl.test/*' => Http::response(bylResponse(InvoiceFactory::make()), 201)]);

    Byl::invoices()->createFor(1000);

    Http::assertSent(fn (Request $request) => str_starts_with($request->url(), 'https://byl.test/api/v1/'));
});

it('facade нь тохиргооны клиент рүү дуудлагыг дамжуулна', function () {
    expect(Byl::url('invoices'))->toBe('https://byl.mn/api/v1/projects/1/invoices')
        ->and(Byl::projectId())->toBe(1);
});

it('fake-гүйгээр assert дуудвал тодорхой алдаа шиднэ', function () {
    expect(fn () => Byl::assertInvoiceCreated())
        ->toThrow(LogicException::class, 'Byl::fake()');
});
