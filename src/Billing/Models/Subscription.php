<?php

namespace Byl\Laravel\Billing\Models;

use Byl\Laravel\Data\CreatedCheckout;
use Byl\Laravel\Data\Subscription as SubscriptionData;
use Byl\Laravel\Enums\SubscriptionStatus;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Support\CheckoutBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Byl-ийн захиалгын локал тусгал. Webhook ирэх бүрд шинэчлэгддэг тул
 * эрхийн шалгалтыг сүлжээнд гарахгүйгээр хийх боломж өгнө.
 *
 * @property int $id
 * @property int $byl_id
 * @property int|null $byl_customer_id
 * @property int|null $product_id
 * @property int|null $price_id
 * @property string|null $lookup_key
 * @property SubscriptionStatus $status
 * @property Carbon|null $current_period_start
 * @property Carbon|null $current_period_end
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $canceled_at
 * @property bool $is_test
 */
class Subscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'byl_id' => 'integer',
        'byl_customer_id' => 'integer',
        'product_id' => 'integer',
        'price_id' => 'integer',
        'status' => SubscriptionStatus::class,
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'trial_ends_at' => 'datetime',
        'canceled_at' => 'datetime',
        'is_test' => 'boolean',
    ];

    public function getTable(): string
    {
        return $this->table ?? config('byl.billable.subscriptions_table', 'byl_subscriptions');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function billable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * API-ийн хариунаас локал мөрийг үүсгэх/шинэчлэх атрибутууд.
     *
     * @return array<string, mixed>
     */
    public static function attributesFrom(SubscriptionData $subscription): array
    {
        return [
            'byl_customer_id' => $subscription->customerId,
            'product_id' => $subscription->productId,
            'price_id' => $subscription->priceId,
            'lookup_key' => $subscription->price?->lookupKey,
            'status' => $subscription->status?->value ?? SubscriptionStatus::Canceled->value,
            'current_period_start' => $subscription->currentPeriodStart,
            'current_period_end' => $subscription->currentPeriodEnd,
            'trial_ends_at' => $subscription->trialEndsAt,
            'canceled_at' => $subscription->canceledAt,
            'is_test' => $subscription->isTest,
        ];
    }

    /**
     * API-ийн хариугаар локал мөрийг шинэчилнэ.
     */
    public function syncFrom(SubscriptionData $subscription): static
    {
        $attributes = static::attributesFrom($subscription);

        // lookup_key нь зарим хариунд ирэхгүй — байгаа утгыг дарж болохгүй.
        if ($attributes['lookup_key'] === null) {
            unset($attributes['lookup_key']);
        }

        $this->fill($attributes)->save();

        return $this;
    }

    /**
     * Хэрэглэгчид эрх нээлттэй байх ёстой эсэх — `canceled`-аас бусад бүх төлөв.
     */
    public function valid(): bool
    {
        return $this->status->isEntitled();
    }

    public function active(): bool
    {
        return $this->status === SubscriptionStatus::Active;
    }

    public function onTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing;
    }

    public function pastDue(): bool
    {
        return $this->status === SubscriptionStatus::PastDue;
    }

    public function canceled(): bool
    {
        return $this->canceled_at !== null;
    }

    /**
     * Цуцлалт хүссэн боловч мөчлөг дуусаагүй — эрх хүчинтэй хэвээр.
     */
    public function onGracePeriod(): bool
    {
        return $this->canceled() && $this->valid();
    }

    /**
     * Мөчлөг дуусаж эрх хаагдсан.
     */
    public function ended(): bool
    {
        return $this->status === SubscriptionStatus::Canceled;
    }

    public function daysUntilPeriodEnd(): ?int
    {
        if ($this->current_period_end === null) {
            return null;
        }

        return (int) max(0, Carbon::now()->diffInDays($this->current_period_end, false));
    }

    /**
     * Lookup key эсвэл үнийн ID-тай тохирох эсэх.
     */
    public function hasPrice(int|string $price): bool
    {
        if (is_numeric($price)) {
            return $this->price_id !== null && $this->price_id === (int) $price;
        }

        return $this->lookup_key !== null && $this->lookup_key === $price;
    }

    /**
     * Захиалгыг сунгах checkout үүсгэнэ. `$cycles` нь хэдэн мөчлөгөөр
     * сунгахыг заана (сарын багц дээр 3 → 3 сар).
     *
     * @param  array<string, mixed>  $options
     */
    public function renewCheckout(int $cycles = 1, array $options = []): CreatedCheckout
    {
        return $this->checkoutBuilder($this->priceReference(), $cycles, $options)->create();
    }

    /**
     * Багц солих checkout үүсгэнэ. Одоогийн мөчлөгийн үлдсэн хоногийн үнэ
     * кредит болж хасагдах тул 24 цагийн дотор төлөгдөх ёстой.
     *
     * @param  array<string, mixed>  $options
     */
    public function swapCheckout(int|string $price, int $cycles = 1, array $options = []): CreatedCheckout
    {
        return $this->checkoutBuilder($price, $cycles, $options)->create();
    }

    /**
     * Мөчлөгийн төгсгөлд цуцална — эрх нь мөчлөг дуустал хүчинтэй.
     */
    public function cancel(): static
    {
        return $this->syncFrom(Byl::subscriptions()->cancel($this->byl_id));
    }

    /**
     * Мөчлөг дуусахаас өмнө цуцлалтыг буцаана.
     */
    public function resume(): static
    {
        return $this->syncFrom(Byl::subscriptions()->resume($this->byl_id));
    }

    /**
     * Byl дээрх хамгийн сүүлийн төлөвийг татаж авна.
     */
    public function asBylSubscription(): SubscriptionData
    {
        return Byl::subscriptions()->find($this->byl_id);
    }

    /**
     * Byl дээрх төлөвөөр локал мөрийг шинэчилнэ.
     */
    public function syncFromByl(): static
    {
        return $this->syncFrom($this->asBylSubscription());
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNot('status', SubscriptionStatus::Canceled->value);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOnTrial(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Trialing->value);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCanceled(Builder $query): Builder
    {
        return $query->whereNotNull('canceled_at');
    }

    /**
     * Lookup key эсвэл үнийн ID-гээр шүүнэ.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForPrice(Builder $query, int|string $price): Builder
    {
        return is_numeric($price)
            ? $query->where('price_id', (int) $price)
            : $query->where('lookup_key', $price);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    protected function checkoutBuilder(int|string $price, int $cycles, array $options): CheckoutBuilder
    {
        if ($this->byl_customer_id === null) {
            throw new LogicException('Захиалгад харилцагчийн ID байхгүй тул checkout үүсгэх боломжгүй.');
        }

        $builder = Byl::checkouts()->builder()
            ->customer($this->byl_customer_id)
            ->subscription($this->byl_id);

        is_numeric($price)
            ? $builder->addPriceId((int) $price, $cycles)
            : $builder->addPrice((string) $price, $cycles);

        foreach ($options as $key => $value) {
            $builder->with($key, $value);
        }

        return $builder;
    }

    protected function priceReference(): int|string
    {
        return $this->lookup_key ?? $this->price_id
            ?? throw new LogicException('Захиалгад үнийн мэдээлэл байхгүй тул сунгалт үүсгэх боломжгүй.');
    }
}
