<?php

namespace Byl\Laravel\Billing;

use Byl\Laravel\Billing\Models\Subscription;
use Byl\Laravel\Data\CreatedCheckout;
use Byl\Laravel\Facades\Byl;
use Illuminate\Database\Eloquent\Model;

/**
 * Шинэ захиалга эхлүүлэх урсгал. Byl дээр төлбөр автоматаар суутгагддаггүй
 * тул захиалга нь checkout төлөгдөх (эсвэл туршилт эхлэх) үед үүснэ.
 */
class SubscriptionBuilder
{
    protected int $cycles = 1;

    /** @var array<string, mixed> */
    protected array $options = [];

    /**
     * @param  Model  $billable  Billable trait хэрэглэсэн модель
     */
    public function __construct(
        protected Model $billable,
        protected int|string $price,
    ) {}

    /**
     * Хэдэн мөчлөгийн төлбөрийг нэг дор авах (сарын багц дээр 3 → 3 сар).
     */
    public function cycles(int $cycles): static
    {
        $this->cycles = $cycles;

        return $this;
    }

    public function successUrl(string $url): static
    {
        return $this->with('success_url', $url);
    }

    public function cancelUrl(string $url): static
    {
        return $this->with('cancel_url', $url);
    }

    public function allowPromotionCodes(bool $allow = true): static
    {
        return $this->with('allow_promotion_codes', $allow);
    }

    public function collectPhoneNumber(bool $collect = true): static
    {
        return $this->with('phone_number_collection', $collect);
    }

    public function collectDeliveryAddress(bool $collect = true): static
    {
        return $this->with('delivery_address_collection', $collect);
    }

    public function clientReferenceId(string $clientReferenceId): static
    {
        return $this->with('client_reference_id', $clientReferenceId);
    }

    public function with(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Захиалгыг эхлүүлэх checkout үүсгэнэ — хэрэглэгчийг буцах `url` руу
     * чиглүүлнэ. Төлөгдмөгц Byl subscription үүсгэж webhook илгээнэ.
     *
     * @param  array<string, mixed>  $options
     */
    public function checkout(array $options = []): CreatedCheckout
    {
        $builder = Byl::checkouts()->builder()
            ->customer($this->billable->bylCustomerIdOrCreate());

        is_numeric($this->price)
            ? $builder->addPriceId((int) $this->price, $this->cycles)
            : $builder->addPrice((string) $this->price, $this->cycles);

        foreach ([...$this->options, ...$options] as $key => $value) {
            $builder->with($key, $value);
        }

        return $builder->create();
    }

    /**
     * Төлбөргүй туршилтын захиалгыг шууд эхлүүлнэ (1–365 хоног).
     *
     * `checkout()`-той адил үнийн ID эсвэл lookup key хоёуланг хүлээж авна.
     * Нэг харилцагч нэг бүтээгдэхүүн дээр нэг л удаа туршилт авч болно.
     */
    public function startTrial(int $days): Subscription
    {
        $subscription = Byl::subscriptions()->startTrial(
            $this->billable->bylCustomerIdOrCreate(),
            is_numeric($this->price) ? (int) $this->price : (string) $this->price,
            $days,
        );

        return $this->billable->recordBylSubscription($subscription);
    }
}
