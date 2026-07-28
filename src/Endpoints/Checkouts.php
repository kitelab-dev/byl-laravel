<?php

namespace Byl\Laravel\Endpoints;

use Byl\Laravel\Data\Checkout;
use Byl\Laravel\Data\CreatedCheckout;
use Byl\Laravel\Support\CheckoutBuilder;

/**
 * Checkout — https://byl.mn/docs/api/checkouts
 */
class Checkouts extends Endpoint
{
    /**
     * Checkout үүсгэнэ. Хариунд `id` ба төлбөрийн хуудасны `url` ирнэ.
     *
     * @param  array<string, mixed>|CheckoutBuilder  $attributes
     */
    public function create(array|CheckoutBuilder $attributes): CreatedCheckout
    {
        $payload = $attributes instanceof CheckoutBuilder ? $attributes->toArray() : $attributes;

        return CreatedCheckout::fromArray(
            $this->client->data($this->client->post('checkouts', $payload))
        );
    }

    /**
     * Checkout-ийг уншихад ойлгомжтой байдлаар бүтээх fluent builder.
     *
     * ```php
     * Byl::checkouts()->builder()
     *     ->successUrl(route('purchase.success'))
     *     ->addPrice('starter_monthly')
     *     ->customer($customerId)
     *     ->create();
     * ```
     */
    public function builder(): CheckoutBuilder
    {
        return new CheckoutBuilder($this);
    }

    public function find(int|string $id): Checkout
    {
        return Checkout::fromArray(
            $this->client->data($this->client->get("checkouts/{$id}"))
        );
    }
}
