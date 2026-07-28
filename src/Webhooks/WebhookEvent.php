<?php

namespace Byl\Laravel\Webhooks;

use Byl\Laravel\Data\Checkout;
use Byl\Laravel\Data\Data;
use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Data\Subscription;
use Byl\Laravel\Enums\WebhookEventType;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Byl-ээс ирсэн webhook event.
 *
 * @implements Arrayable<string, mixed>
 */
class WebhookEvent implements Arrayable
{
    /**
     * @param  array<string, mixed>  $payload  Хүсэлтийн бүтэн payload.
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $projectId,
        public readonly string $type,
        public readonly ?string $object,
        public readonly array $payload,
        public readonly ?Carbon $createdAt = null,
        public readonly ?Carbon $updatedAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: Arr::get($payload, 'id') !== null ? (int) Arr::get($payload, 'id') : null,
            projectId: Arr::get($payload, 'project_id') !== null ? (int) Arr::get($payload, 'project_id') : null,
            type: (string) Arr::get($payload, 'type', ''),
            object: Arr::get($payload, 'object'),
            payload: $payload,
            createdAt: self::parseDate(Arr::get($payload, 'created_at')),
            updatedAt: self::parseDate(Arr::get($payload, 'updated_at')),
        );
    }

    /**
     * Танигдсан event төрөл. SDK-д ороогүй шинэ төрөл бол `null`.
     */
    public function type(): ?WebhookEventType
    {
        return WebhookEventType::tryFrom($this->type);
    }

    public function is(WebhookEventType|string $type): bool
    {
        return $this->type === ($type instanceof WebhookEventType ? $type->value : $type);
    }

    /**
     * Event-ийн гол объектын raw массив (`data.object`).
     *
     * @return array<string, mixed>
     */
    public function objectData(): array
    {
        $object = Arr::get($this->payload, 'data.object', []);

        return is_array($object) ? $object : [];
    }

    public function invoice(): Invoice
    {
        return Invoice::fromArray($this->objectData());
    }

    public function checkout(): Checkout
    {
        return Checkout::fromArray($this->objectData());
    }

    public function subscription(): Subscription
    {
        return Subscription::fromArray($this->objectData());
    }

    /**
     * `object` талбараас хамааран тохирох DTO-г буцаана.
     */
    public function resource(): ?Data
    {
        return match ($this->object) {
            'invoice' => $this->invoice(),
            'checkout' => $this->checkout(),
            'subscription' => $this->subscription(),
            default => null,
        };
    }

    public function raw(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->payload : Arr::get($this->payload, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    protected static function parseDate(mixed $value): ?Carbon
    {
        return blank($value) ? null : Carbon::parse($value);
    }
}
