<?php

namespace Byl\Laravel\Data;

use Byl\Laravel\Enums\SubscriptionStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

final class Subscription extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?SubscriptionStatus $status,
        public readonly ?int $projectId,
        public readonly ?int $customerId,
        public readonly ?int $productId,
        public readonly ?int $priceId,
        public readonly ?string $lookupKey,
        public readonly ?Carbon $currentPeriodStart,
        public readonly ?Carbon $currentPeriodEnd,
        public readonly ?Carbon $trialEndsAt,
        public readonly ?Carbon $canceledAt,
        public readonly bool $isTest,
        public readonly ?Customer $customer,
        public readonly ?Price $price,
        public readonly ?Product $product,
        public readonly ?Carbon $createdAt,
        public readonly ?Carbon $updatedAt,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $customer = Arr::get($data, 'customer');
        $price = Arr::get($data, 'price');

        // Webhook payload-д бүтээгдэхүүн тусдаа, API-д үнийн дор ирдэг.
        $product = Arr::get($data, 'product') ?? Arr::get($data, 'price.product');

        // Lookup key нь үнийн объект дор ирдэг. Туршилт эхлүүлэх хүсэлт нь
        // `price` талбарт lookup key дамжуулдаг тул хариунд объектын оронд
        // тэр string эгшиж ирэх тохиолдлыг ч даана.
        $lookupKey = (is_array($price) ? Arr::get($price, 'lookup_key') : null)
            ?? Arr::get($data, 'lookup_key')
            ?? (is_string($price) && ! is_numeric($price) ? $price : null);

        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            status: self::enum(SubscriptionStatus::class, Arr::get($data, 'status')),
            projectId: Arr::get($data, 'project_id') !== null ? (int) Arr::get($data, 'project_id') : null,
            customerId: Arr::get($data, 'customer_id') !== null
                ? (int) Arr::get($data, 'customer_id')
                : (Arr::get($data, 'customer.id') !== null ? (int) Arr::get($data, 'customer.id') : null),
            productId: Arr::get($data, 'product_id') !== null
                ? (int) Arr::get($data, 'product_id')
                : (Arr::get($product ?? [], 'id') !== null ? (int) Arr::get($product ?? [], 'id') : null),
            priceId: Arr::get($data, 'price_id') !== null
                ? (int) Arr::get($data, 'price_id')
                : (Arr::get($data, 'price.id') !== null ? (int) Arr::get($data, 'price.id') : null),
            lookupKey: $lookupKey,
            currentPeriodStart: self::date(Arr::get($data, 'current_period_start')),
            currentPeriodEnd: self::date(Arr::get($data, 'current_period_end')),
            trialEndsAt: self::date(Arr::get($data, 'trial_ends_at')),
            canceledAt: self::date(Arr::get($data, 'canceled_at')),
            isTest: (bool) Arr::get($data, 'is_test', false),
            customer: is_array($customer) ? Customer::fromArray($customer) : null,
            price: is_array($price) ? Price::fromArray($price) : null,
            product: is_array($product) ? Product::fromArray($product) : null,
            createdAt: self::date(Arr::get($data, 'created_at')),
            updatedAt: self::date(Arr::get($data, 'updated_at')),
            raw: $data,
        );
    }

    /**
     * Хэрэглэгчид эрх нээлттэй байх ёстой эсэх. `canceled`-аас бусад бүх
     * төлөв (trialing, active, past_due) эрхтэйд тооцогдоно.
     */
    public function isEntitled(): bool
    {
        return $this->status?->isEntitled() ?? false;
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing;
    }

    public function isPastDue(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function isCanceled(): bool
    {
        return $this->status === SubscriptionStatus::Canceled;
    }

    /**
     * Цуцлалт хүссэн боловч мөчлөг дуусаагүй — эрх нь хүчинтэй хэвээр.
     */
    public function cancelRequested(): bool
    {
        return $this->canceledAt !== null && ! $this->isCanceled();
    }

    /**
     * Эрх дуусах хүртэлх хоног. Хугацаа хэтэрсэн бол 0.
     */
    public function daysUntilPeriodEnd(): ?int
    {
        if ($this->currentPeriodEnd === null) {
            return null;
        }

        return (int) max(0, Carbon::now()->diffInDays($this->currentPeriodEnd, false));
    }
}
