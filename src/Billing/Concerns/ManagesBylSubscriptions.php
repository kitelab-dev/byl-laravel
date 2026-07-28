<?php

namespace Byl\Laravel\Billing\Concerns;

use Byl\Laravel\Billing\Models\Subscription;
use Byl\Laravel\Data\Subscription as SubscriptionData;
use Byl\Laravel\Facades\Byl;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait ManagesBylSubscriptions
{
    /**
     * Локал байрлах бүх захиалга (шинэ нь эхэлж).
     *
     * @return MorphMany<Subscription, $this>
     */
    public function bylSubscriptions(): MorphMany
    {
        return $this->morphMany($this->bylSubscriptionModel(), 'billable')->latest('id');
    }

    /**
     * Локал захиалгын модель. Өөрийн модель хэрэглэх бол дарж бичнэ.
     *
     * @return class-string<Subscription>
     */
    public function bylSubscriptionModel(): string
    {
        return config('byl.billable.subscription_model') ?? Subscription::class;
    }

    /**
     * Эрхтэй захиалга. `$price` дамжуулбал тухайн lookup key / үнийн
     * ID-тай захиалгыг хайна.
     */
    public function bylSubscription(int|string|null $price = null): ?Subscription
    {
        return $this->bylSubscriptions()
            ->valid()
            ->when($price !== null, fn ($query) => $query->forPrice($price))
            ->first();
    }

    /**
     * Хэрэглэгч эрхтэй эсэх (trialing, active, past_due).
     */
    public function subscribed(int|string|null $price = null): bool
    {
        return $this->bylSubscription($price) !== null;
    }

    public function subscribedToPrice(int|string $price): bool
    {
        return $this->subscribed($price);
    }

    public function subscribedToProduct(int $productId): bool
    {
        return $this->bylSubscriptions()->valid()->forProduct($productId)->exists();
    }

    /**
     * Туршилтын хугацаанд байгаа эсэх.
     */
    public function onTrial(int|string|null $price = null): bool
    {
        return $this->bylSubscription($price)?->onTrial() ?? false;
    }

    /**
     * Цуцлалт хүссэн боловч мөчлөг дуусаагүй — эрх хүчинтэй хэвээр.
     */
    public function onGracePeriod(int|string|null $price = null): bool
    {
        return $this->bylSubscription($price)?->onGracePeriod() ?? false;
    }

    /**
     * Хугацаа хэтэрсэн (grace period тохируулсан бүтээгдэхүүн дээр).
     */
    public function pastDue(int|string|null $price = null): bool
    {
        return $this->bylSubscription($price)?->pastDue() ?? false;
    }

    /**
     * Захиалга бүрэн цуцлагдсан (эрх хаагдсан) эсэх.
     */
    public function subscriptionEnded(int|string|null $price = null): bool
    {
        return $this->subscribed($price) === false
            && $this->bylSubscriptions()
                ->when($price !== null, fn ($query) => $query->forPrice($price))
                ->exists();
    }

    /**
     * Byl дээрх захиалгуудыг татаж локал хүснэгтийг шинэчилнэ. Webhook
     * алдсан, эсвэл өмнөх өгөгдлийг нөхөх (backfill) үед хэрэглэнэ.
     *
     * @return Collection<int, Subscription>
     */
    public function syncBylSubscriptions(): Collection
    {
        if (! $this->hasBylCustomerId()) {
            return new Collection;
        }

        $synced = new Collection;

        foreach (Byl::subscriptions()->all(['customer_id' => $this->bylCustomerId()]) as $subscription) {
            $synced->push($this->recordBylSubscription($subscription));
        }

        return $synced;
    }

    /**
     * API-ийн хариунаас локал захиалгыг үүсгэх/шинэчлэх.
     */
    public function recordBylSubscription(SubscriptionData $subscription): Subscription
    {
        /** @var Subscription $model */
        $model = $this->bylSubscriptions()->firstOrNew(['byl_id' => $subscription->id]);

        $model->billable()->associate($this);

        return $model->syncFrom($subscription);
    }
}
