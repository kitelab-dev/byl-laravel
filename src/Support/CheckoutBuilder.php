<?php

namespace Byl\Laravel\Support;

use Byl\Laravel\Data\CreatedCheckout;
use Byl\Laravel\Endpoints\Checkouts;
use Illuminate\Contracts\Support\Arrayable;
use LogicException;

/**
 * Checkout-ийн payload-ыг уншихад ойлгомжтой байдлаар бүтээнэ.
 *
 * @implements Arrayable<string, mixed>
 */
class CheckoutBuilder implements Arrayable
{
    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<int, array<string, mixed>> */
    protected array $items = [];

    /** @var array<int, array{amount: float|int, description: string}> */
    protected array $discounts = [];

    public function __construct(protected ?Checkouts $checkouts = null) {}

    public function successUrl(string $url): static
    {
        $this->attributes['success_url'] = $url;

        return $this;
    }

    public function cancelUrl(string $url): static
    {
        $this->attributes['cancel_url'] = $url;

        return $this;
    }

    /**
     * Lookup key-ээр бүтээгдэхүүн нэмнэ (жш: `starter_monthly`).
     *
     * @param  array{enabled?: bool, min?: int, max?: int}  $adjustableQuantity
     */
    public function addPrice(string $lookupKey, int $quantity = 1, array $adjustableQuantity = []): static
    {
        return $this->addItem(['price' => $lookupKey, 'quantity' => $quantity], $adjustableQuantity);
    }

    /**
     * Byl дээр бүртгэлтэй үнийн ID-гээр бүтээгдэхүүн нэмнэ. Бүтээгдэхүүний
     * хямдралтай хөнгөлөлтийн код ашиглах бол бүх item ингэж нэмэгдэх ёстой.
     *
     * @param  array{enabled?: bool, min?: int, max?: int}  $adjustableQuantity
     */
    public function addPriceId(int|string $priceId, int $quantity = 1, array $adjustableQuantity = []): static
    {
        return $this->addItem(['price_id' => $priceId, 'quantity' => $quantity], $adjustableQuantity);
    }

    /**
     * Byl дээр бүртгэлгүй, шууд дамжуулсан үнээр бүтээгдэхүүн нэмнэ.
     *
     * @param  array{enabled?: bool, min?: int, max?: int}  $adjustableQuantity
     */
    public function addPriceData(
        float|int $unitAmount,
        string $name,
        int $quantity = 1,
        ?string $clientReferenceId = null,
        array $adjustableQuantity = [],
    ): static {
        return $this->addItem([
            'price_data' => [
                'unit_amount' => $unitAmount,
                'product_data' => array_filter([
                    'name' => $name,
                    'client_reference_id' => $clientReferenceId,
                ], fn ($value) => $value !== null),
            ],
            'quantity' => $quantity,
        ], $adjustableQuantity);
    }

    /**
     * Бэлэн item массивыг шууд нэмэх.
     *
     * @param  array<string, mixed>  $item
     * @param  array{enabled?: bool, min?: int, max?: int}  $adjustableQuantity
     */
    public function addItem(array $item, array $adjustableQuantity = []): static
    {
        if ($adjustableQuantity !== []) {
            $item['adjustable_quantity'] = $adjustableQuantity + ['enabled' => true];
        }

        $this->items[] = $item;

        return $this;
    }

    /**
     * Захиалгын нийт дүнгээс хөнгөлөх хямдрал нэмнэ.
     */
    public function discount(float|int $amount, string $description): static
    {
        $this->discounts[] = ['amount' => $amount, 'description' => $description];

        return $this;
    }

    /**
     * Checkout хуудсанд хөнгөлөлтийн код оруулах талбар гаргана.
     */
    public function allowPromotionCodes(bool $allow = true): static
    {
        $this->attributes['allow_promotion_codes'] = $allow;

        return $this;
    }

    public function collectPhoneNumber(bool $collect = true): static
    {
        $this->attributes['phone_number_collection'] = $collect;

        return $this;
    }

    public function collectDeliveryAddress(bool $collect = true): static
    {
        $this->attributes['delivery_address_collection'] = $collect;

        return $this;
    }

    /**
     * Харилцагчийг холбоно. Recurring үнэтэй checkout-д заавал шаардлагатай.
     */
    public function customer(int|string $customerId): static
    {
        $this->attributes['customer_id'] = $customerId;

        return $this;
    }

    public function customerEmail(string $email): static
    {
        $this->attributes['customer_email'] = $email;

        return $this;
    }

    /**
     * Өөрийн систем дэх дахин давтагдашгүй дугаар (48 тэмдэгт хүртэл).
     */
    public function clientReferenceId(string $clientReferenceId): static
    {
        $this->attributes['client_reference_id'] = $clientReferenceId;

        return $this;
    }

    /**
     * Захиалгыг сунгах эсвэл багц солих checkout болгоно.
     */
    public function subscription(int|string $subscriptionId): static
    {
        $this->attributes['subscription_id'] = $subscriptionId;

        return $this;
    }

    /**
     * Жагсаалтад ороогүй нэмэлт талбар дамжуулах.
     */
    public function with(string $key, mixed $value): static
    {
        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            ...$this->attributes,
            'items' => $this->items,
            'discounts' => $this->discounts,
        ], fn ($value) => $value !== [] && $value !== null);
    }

    public function create(): CreatedCheckout
    {
        if ($this->checkouts === null) {
            throw new LogicException('CheckoutBuilder-г Byl::checkouts()->builder() гэж үүсгэсэн үед л create() дуудаж болно.');
        }

        return $this->checkouts->create($this);
    }
}
