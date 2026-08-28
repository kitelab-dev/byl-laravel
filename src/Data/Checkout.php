<?php

namespace Byl\Laravel\Data;

use Byl\Laravel\Enums\CheckoutMode;
use Byl\Laravel\Enums\CheckoutStatus;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class Checkout extends Data implements Responsable
{
    use RedirectsToUrl;

    /**
     * @param  Collection<int, CheckoutItem>  $items
     * @param  Collection<int, CouponCode>  $couponCodes
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $url,
        public readonly ?string $clientReferenceId,
        public readonly ?CheckoutMode $mode,
        public readonly ?CheckoutStatus $status,
        public readonly ?Carbon $expiresAt,
        public readonly ?float $amountSubtotal,
        public readonly ?float $amountTotal,
        public readonly ?int $customerId,
        public readonly ?string $customerEmail,
        public readonly ?string $phoneNumber,
        public readonly bool $isGuest,
        public readonly bool $allowPromotionCodes,
        public readonly ?string $paymentMethod,
        public readonly ?string $successUrl,
        public readonly ?string $cancelUrl,
        public readonly Collection $items,
        public readonly Collection $couponCodes,
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
            url: Arr::get($data, 'url'),
            clientReferenceId: Arr::get($data, 'client_reference_id'),
            mode: self::enum(CheckoutMode::class, Arr::get($data, 'mode')),
            status: self::enum(CheckoutStatus::class, Arr::get($data, 'status')),
            expiresAt: self::date(Arr::get($data, 'expires_at')),
            amountSubtotal: self::number(Arr::get($data, 'amount_subtotal')),
            amountTotal: self::number(Arr::get($data, 'amount_total')),
            customerId: Arr::get($data, 'customer_id') !== null
                ? (int) Arr::get($data, 'customer_id')
                : (Arr::get($data, 'customer.id') !== null ? (int) Arr::get($data, 'customer.id') : null),
            customerEmail: Arr::get($data, 'customer_email') ?? Arr::get($data, 'customer.email'),
            phoneNumber: Arr::get($data, 'phone_number'),
            isGuest: (bool) Arr::get($data, 'is_guest', false),
            allowPromotionCodes: (bool) Arr::get($data, 'allow_promotion_codes', false),
            paymentMethod: Arr::get($data, 'payment_method'),
            successUrl: Arr::get($data, 'success_url'),
            cancelUrl: Arr::get($data, 'cancel_url'),
            items: collect(Arr::get($data, 'items', []))
                ->filter(fn ($item) => is_array($item))
                ->map(fn (array $item) => CheckoutItem::fromArray($item))
                ->values(),
            couponCodes: collect(Arr::get($data, 'coupon_codes', []))
                ->filter(fn ($coupon) => is_array($coupon))
                ->map(fn (array $coupon) => CouponCode::fromArray($coupon))
                ->values(),
            createdAt: self::date(Arr::get($data, 'created_at')),
            updatedAt: self::date(Arr::get($data, 'updated_at')),
            raw: $data,
        );
    }

    public function isComplete(): bool
    {
        return $this->status?->isComplete() ?? false;
    }

    /**
     * Банкны шилжүүлгийн баталгаажуулалт хүлээж буй checkout — төлбөр
     * баталгаажаагүй тул эрх/бараа олгож болохгүй.
     */
    public function isPending(): bool
    {
        return $this->status?->isPending() ?? false;
    }

    public function isExpired(): bool
    {
        return $this->status === CheckoutStatus::Expired;
    }

    /**
     * Хэрэглэгчийн хэрэглэсэн хөнгөлөлтийн кодуудын нийт дүн.
     */
    public function discountTotal(): float
    {
        return (float) $this->couponCodes->sum(fn (CouponCode $coupon) => $coupon->discountAmount ?? 0.0);
    }

    /**
     * Хэрэглэгчийг Byl-ийн checkout хуудас руу чиглүүлнэ.
     */
    public function toResponse($request): RedirectResponse
    {
        return $this->redirectToUrl();
    }
}
