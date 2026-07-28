<?php

namespace Byl\Laravel\Endpoints;

use Byl\Laravel\Data\Page;
use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Enums\SubscriptionStatus;
use Illuminate\Support\LazyCollection;

/**
 * Захиалга — https://byl.mn/docs/api/subscriptions
 */
class Subscriptions extends Endpoint
{
    /**
     * Захиалгын жагсаалт (хуудсаар). Filter: `customer_id`, `price_id`, `status`.
     *
     * @param  array<string, mixed>  $filters
     * @return Page<Subscription>
     */
    public function list(array $filters = [], int $page = 1): Page
    {
        $response = $this->client->get('subscriptions', $this->normalizeFilters($filters) + ['page' => $page]);

        return Page::fromResponse($response, fn (array $item) => Subscription::fromArray($item));
    }

    /**
     * Бүх хуудсыг шаардлагатай үед л татаж, нэг урсгалаар давтана.
     *
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, Subscription>
     */
    public function all(array $filters = []): LazyCollection
    {
        return LazyCollection::make(function () use ($filters) {
            $page = 1;

            do {
                $result = $this->list($filters, $page);

                yield from $result->items;

                $page++;
            } while ($result->hasMorePages());
        });
    }

    public function find(int|string $id): Subscription
    {
        return Subscription::fromArray(
            $this->client->data($this->client->get("subscriptions/{$id}"))
        );
    }

    /**
     * Харилцагчийн захиалгууд.
     *
     * @return Page<Subscription>
     */
    public function forCustomer(int|string $customerId, SubscriptionStatus|string|null $status = null): Page
    {
        return $this->list(array_filter([
            'customer_id' => $customerId,
            'status' => $status,
        ], fn ($value) => $value !== null));
    }

    /**
     * Төлбөргүй туршилтын захиалга эхлүүлнэ (1–365 хоног). Нэг харилцагч
     * нэг бүтээгдэхүүн дээр нэг л удаа туршилт авч болно.
     */
    public function startTrial(int|string $customerId, int|string $priceId, int $trialDays): Subscription
    {
        return Subscription::fromArray($this->client->data($this->client->post('subscriptions', [
            'customer_id' => $customerId,
            'price_id' => $priceId,
            'trial_days' => $trialDays,
        ])));
    }

    /**
     * Мөчлөгийн төгсгөлд цуцлана — эрх нь мөчлөг дуустал хүчинтэй хэвээр.
     */
    public function cancel(int|string $id): Subscription
    {
        return Subscription::fromArray(
            $this->client->data($this->client->post("subscriptions/{$id}/cancel"))
        );
    }

    /**
     * Мөчлөг дуусахаас өмнө цуцлалтыг буцаана.
     */
    public function resume(int|string $id): Subscription
    {
        return Subscription::fromArray(
            $this->client->data($this->client->post("subscriptions/{$id}/resume"))
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function normalizeFilters(array $filters): array
    {
        if (($filters['status'] ?? null) instanceof SubscriptionStatus) {
            $filters['status'] = $filters['status']->value;
        }

        return array_filter($filters, fn ($value) => $value !== null);
    }
}
