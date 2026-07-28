<?php

namespace Byl\Laravel\Billing\Concerns;

use Byl\Laravel\Billing\SubscriptionBuilder;
use Byl\Laravel\Data\Invoice;
use Byl\Laravel\Facades\Byl;
use Byl\Laravel\Support\CheckoutBuilder;

trait PerformsBylCheckouts
{
    /**
     * Шинэ захиалга эхлүүлэх. `$price` нь lookup key (жш: `starter_monthly`)
     * эсвэл үнийн ID байж болно.
     */
    public function newSubscription(int|string $price): SubscriptionBuilder
    {
        return new SubscriptionBuilder($this, $price);
    }

    /**
     * Нэг удаагийн худалдан авалтын checkout — харилцагч аль хэдийн
     * холбогдсон байна.
     *
     * ```php
     * $user->checkout()->addPriceData(15000, 'Гутал')->create();
     * ```
     */
    public function checkout(): CheckoutBuilder
    {
        return Byl::checkouts()->builder()->customer($this->bylCustomerIdOrCreate());
    }

    /**
     * Харилцагчид нэхэмжлэх үүсгэнэ.
     */
    public function invoiceFor(float|int $amount, ?string $description = null): Invoice
    {
        return Byl::invoices()->create(array_filter([
            'amount' => $amount,
            'description' => $description,
            'customer_id' => $this->bylCustomerIdOrCreate(),
        ], fn ($value) => $value !== null));
    }
}
