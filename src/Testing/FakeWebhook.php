<?php

namespace Byl\Laravel\Testing;

use Byl\Laravel\Enums\WebhookEventType;
use Byl\Laravel\Webhooks\WebhookSignature;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * Byl-ээс ирсэн шиг гарын үсэгтэй webhook payload бүтээнэ.
 *
 * ```php
 * $webhook = FakeWebhook::invoicePaid(['id' => 71, 'amount' => 500]);
 *
 * $this->postJson(route('byl.webhook'), $webhook->payload(), $webhook->headers())
 *     ->assertOk();
 * ```
 */
class FakeWebhook
{
    /** @var array<string, mixed>|null */
    protected ?array $payload = null;

    /**
     * @param  array<string, mixed>  $object
     */
    public function __construct(
        protected WebhookEventType|string $type,
        protected string $objectType,
        protected array $object = [],
        protected ?int $id = null,
        protected ?int $projectId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $invoice
     */
    public static function invoicePaid(array $invoice = []): self
    {
        return new self(
            WebhookEventType::InvoicePaid,
            'invoice',
            InvoiceFactory::make($invoice + ['status' => 'paid']),
        );
    }

    /**
     * @param  array<string, mixed>  $checkout
     */
    public static function checkoutCompleted(array $checkout = []): self
    {
        return new self(
            WebhookEventType::CheckoutCompleted,
            'checkout',
            CheckoutFactory::completed($checkout),
        );
    }

    /**
     * @param  array<string, mixed>  $subscription
     */
    public static function subscription(WebhookEventType|string $type, array $subscription = []): self
    {
        return new self($type, 'subscription', SubscriptionFactory::make($subscription));
    }

    /**
     * Дурын төрлийн event.
     *
     * @param  array<string, mixed>  $object
     */
    public static function make(WebhookEventType|string $type, string $objectType, array $object = []): self
    {
        return new self($type, $objectType, $object);
    }

    public function withEventId(int $id): self
    {
        $this->id = $id;
        $this->payload = null;

        return $this;
    }

    public function withProjectId(int $projectId): self
    {
        $this->projectId = $projectId;
        $this->payload = null;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        // Гарын үсэг нь яг ижил payload дээр тооцоологдох ёстой тул огноо
        // зэрэг хувьсах утгуудыг нэг л удаа бүтээж хадгална.
        return $this->payload ??= [
            'id' => $this->id ?? 87,
            'project_id' => $this->projectId ?? 1,
            'type' => $this->type instanceof WebhookEventType ? $this->type->value : $this->type,
            'object' => $this->objectType,
            'data' => ['object' => $this->object],
            'created_at' => Carbon::now()->toJSON(),
            'updated_at' => Carbon::now()->toJSON(),
        ];
    }

    /**
     * Гарын үсэг нь raw body дээр тооцоологддог тул payload-ыг ижил
     * хэлбэрээр (json_encode) кодлон илгээх ёстой — `postJson` тэгж кодолдог.
     */
    public function json(): string
    {
        return (string) json_encode($this->payload());
    }

    public function signature(?string $secret = null): string
    {
        return WebhookSignature::compute($this->json(), $secret ?? $this->secret());
    }

    /**
     * @return array<string, string>
     */
    public function headers(?string $secret = null): array
    {
        return [WebhookSignature::HEADER => $this->signature($secret)];
    }

    protected function secret(): string
    {
        return (string) Config::get('byl.webhook.secret');
    }
}
