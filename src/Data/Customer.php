<?php

namespace Byl\Laravel\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class Customer extends Data
{
    /**
     * @param  Collection<int, Subscription>  $subscriptions  Зөвхөн эрхтэй захиалгууд.
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $clientReferenceId,
        public readonly Collection $subscriptions,
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
        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            name: Arr::get($data, 'name'),
            email: Arr::get($data, 'email'),
            phone: Arr::get($data, 'phone'),
            clientReferenceId: Arr::get($data, 'client_reference_id'),
            subscriptions: collect(Arr::get($data, 'subscriptions', []))
                ->filter(fn ($subscription) => is_array($subscription))
                ->map(fn (array $subscription) => Subscription::fromArray($subscription))
                ->values(),
            createdAt: self::date(Arr::get($data, 'created_at')),
            updatedAt: self::date(Arr::get($data, 'updated_at')),
            raw: $data,
        );
    }

    /**
     * Харилцагч ямар нэг эрхтэй захиалгатай эсэх.
     */
    public function isSubscribed(): bool
    {
        return $this->subscriptions->contains(fn (Subscription $subscription) => $subscription->isEntitled());
    }

    public function subscriptionForProduct(int $productId): ?Subscription
    {
        return $this->subscriptions->first(fn (Subscription $subscription) => $subscription->productId === $productId);
    }

    public function subscriptionForPrice(int $priceId): ?Subscription
    {
        return $this->subscriptions->first(fn (Subscription $subscription) => $subscription->priceId === $priceId);
    }

    /**
     * Lookup key-ээр эрхтэй захиалга хайх (жш: `starter_monthly`).
     */
    public function subscriptionForLookupKey(string $lookupKey): ?Subscription
    {
        return $this->subscriptions->first(
            fn (Subscription $subscription) => $subscription->price?->lookupKey === $lookupKey
        );
    }
}
