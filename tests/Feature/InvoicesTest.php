<?php

use Byl\Laravel\Enums\InvoiceStatus;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Testing\InvoiceFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('нэхэмжлэх үүсгэнэ', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/invoices' => Http::response(bylResponse(InvoiceFactory::make([
            'id' => 5708,
            'amount' => 500,
            'description' => 'Test invoice',
        ])), 201),
    ]);

    $invoice = Byl::invoices()->create([
        'amount' => 500,
        'description' => 'Test invoice',
        'auto_advance' => true,
    ]);

    expect($invoice->id)->toBe(5708)
        ->and($invoice->amount)->toBe(500.0)
        ->and($invoice->description)->toBe('Test invoice')
        ->and($invoice->status)->toBe(InvoiceStatus::Open)
        ->and($invoice->url)->toContain('/h/invoice/5708/')
        ->and($invoice->dueDate)->not->toBeNull();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && $request->url() === 'https://byl.mn/api/v1/projects/1/invoices'
        && $request->hasHeader('Authorization', 'Bearer test-token')
        && $request['amount'] === 500);
});

it('createFor нь хураангуй хэлбэрээр нэхэмжлэх үүсгэнэ', function () {
    Http::fake(['byl.mn/*' => Http::response(bylResponse(InvoiceFactory::make()), 201)]);

    Byl::invoices()->createFor(1500, 'Дугуй');

    Http::assertSent(fn (Request $request) => $request['amount'] === 1500
        && $request['description'] === 'Дугуй'
        && $request['auto_advance'] === true);
});

it('нэхэмжлэх лавлана', function () {
    Http::fake([
        'byl.mn/api/v1/projects/1/invoices/5708' => Http::response(bylResponse(InvoiceFactory::make([
            'id' => 5708,
            'status' => 'paid',
        ]))),
    ]);

    $invoice = Byl::invoices()->find(5708);

    expect($invoice->isPaid())->toBeTrue()
        ->and($invoice->number)->toBe('TEST-5708');
});

it('нэхэмжлэх хүчингүй болгоно', function () {
    Http::fake([
        'byl.mn/*/invoices/5708/void' => Http::response(bylResponse(InvoiceFactory::make([
            'id' => 5708,
            'status' => 'void',
        ]))),
    ]);

    expect(Byl::invoices()->void(5708)->isVoid())->toBeTrue();

    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/invoices/5708/void'));
});

it('нэхэмжлэх устгана', function () {
    Http::fake([
        'byl.mn/*/invoices/2' => Http::response(bylResponse([
            'id' => 2,
            'deleted_at' => '2025-09-07T16:30:35.000000Z',
        ])),
    ]);

    $deleted = Byl::invoices()->delete(2);

    expect($deleted->id)->toBe(2)
        ->and($deleted->deletedAt?->toDateString())->toBe('2025-09-07');

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE');
});

it('raw() нь SDK-д ороогүй талбарыг уншина', function () {
    Http::fake(['byl.mn/*' => Http::response(bylResponse(
        InvoiceFactory::make(['id' => 1]) + ['brand_new_field' => 'value']
    ))]);

    expect(Byl::invoices()->find(1)->raw('brand_new_field'))->toBe('value');
});
