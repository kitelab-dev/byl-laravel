<?php

use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Data\Page;
use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Enums\InvoiceStatus;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Testing\SubscriptionFactory;

it('танигдаагүй enum утга дээр null болно', function () {
    $invoice = Invoice::fromArray(['id' => 1, 'status' => 'brand_new_status']);

    expect($invoice->status)->toBeNull()
        ->and($invoice->raw('status'))->toBe('brand_new_status')
        ->and($invoice->isPaid())->toBeFalse();
});

it('дутуу талбартай хариу дээр ч бүтэн объект үүсгэнэ', function () {
    $invoice = Invoice::fromArray(['id' => 5]);

    expect($invoice->id)->toBe(5)
        ->and($invoice->amount)->toBeNull()
        ->and($invoice->dueDate)->toBeNull()
        ->and($invoice->customer)->toBeNull()
        ->and($invoice->status)->toBeNull();
});

it('toArray нь API-ийн хариуг хэвээр буцаана', function () {
    $payload = ['id' => 1, 'status' => 'paid', 'amount' => 10, 'extra' => ['a' => 1]];

    expect(Invoice::fromArray($payload)->toArray())->toBe($payload)
        ->and(json_decode(json_encode(Invoice::fromArray($payload)), true))->toBe($payload);
});

it('мөнгөн дүнг тоо болгон хувиргана', function () {
    expect(Invoice::fromArray(['amount' => '1500.50', 'status' => 'paid'])->amount)->toBe(1500.5)
        ->and(Invoice::fromArray(['status' => 'paid'])->status)->toBe(InvoiceStatus::Paid);
});

it('захиалгын төлөв эрхтэй эсэхийг зөв тодорхойлно', function (string $status, bool $entitled) {
    expect(Subscription::fromArray(['status' => $status])->isEntitled())->toBe($entitled);
})->with([
    ['trialing', true],
    ['active', true],
    ['past_due', true],
    ['canceled', false],
]);

it('цуцлалт хүссэн боловч дуусаагүй захиалгыг ялгана', function () {
    $requested = Subscription::fromArray(SubscriptionFactory::make([
        'status' => 'active',
        'canceled_at' => '2026-07-20T00:00:00.000000Z',
    ]));

    $finished = Subscription::fromArray(SubscriptionFactory::make([
        'status' => 'canceled',
        'canceled_at' => '2026-07-20T00:00:00.000000Z',
    ]));

    expect($requested->cancelRequested())->toBeTrue()
        ->and($requested->isEntitled())->toBeTrue()
        ->and($finished->cancelRequested())->toBeFalse()
        ->and($finished->status)->toBe(SubscriptionStatus::Canceled);
});

it('Page нь хуудаслалтын мэдээллийг уншиж давтагдана', function () {
    $page = Page::fromResponse([
        'data' => [SubscriptionFactory::make(['id' => 1]), SubscriptionFactory::make(['id' => 2])],
        'meta' => ['current_page' => 1, 'last_page' => 3, 'per_page' => 2, 'total' => 5],
    ], fn (array $item) => Subscription::fromArray($item));

    expect($page->count())->toBe(2)
        ->and($page->total)->toBe(5)
        ->and($page->lastPage)->toBe(3)
        ->and($page->hasMorePages())->toBeTrue()
        ->and($page->isNotEmpty())->toBeTrue()
        ->and(collect($page)->pluck('id')->all())->toBe([1, 2]);
});

it('Page нь meta байхгүй хариу дээр ч ажиллана', function () {
    $page = Page::fromResponse(['data' => [['id' => 1]]], fn (array $item) => Subscription::fromArray($item));

    expect($page->currentPage)->toBe(1)
        ->and($page->lastPage)->toBe(1)
        ->and($page->total)->toBe(1)
        ->and($page->hasMorePages())->toBeFalse();
});
