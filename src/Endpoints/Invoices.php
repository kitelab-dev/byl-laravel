<?php

namespace Byl\Laravel\Endpoints;

use Byl\Laravel\Data\DeletedInvoice;
use Byl\Laravel\Data\Invoice;

/**
 * Нэхэмжлэх — https://byl.mn/docs/api/invoices
 */
class Invoices extends Endpoint
{
    /**
     * Шинэ нэхэмжлэх үүсгэнэ.
     *
     * Дэмждэг талбарууд: `amount` (заавал), `description`, `auto_advance`,
     * `due_date`, `customer_id`.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Invoice
    {
        return Invoice::fromArray(
            $this->client->data($this->client->post('invoices', $attributes))
        );
    }

    /**
     * Дүн, тайлбараар шууд нэхэмжлэх үүсгэх хураангуй хэлбэр.
     */
    public function createFor(float|int $amount, ?string $description = null, bool $autoAdvance = true): Invoice
    {
        return $this->create(array_filter([
            'amount' => $amount,
            'description' => $description,
            'auto_advance' => $autoAdvance,
        ], fn ($value) => $value !== null));
    }

    public function find(int|string $id): Invoice
    {
        return Invoice::fromArray(
            $this->client->data($this->client->get("invoices/{$id}"))
        );
    }

    /**
     * Төлөгдөөгүй нэхэмжлэхийг хүчингүй болгоно — дараа нь төлбөр хүлээж авахгүй.
     */
    public function void(int|string $id): Invoice
    {
        return Invoice::fromArray(
            $this->client->data($this->client->post("invoices/{$id}/void"))
        );
    }

    public function delete(int|string $id): DeletedInvoice
    {
        return DeletedInvoice::fromArray(
            $this->client->data($this->client->delete("invoices/{$id}"))
        );
    }
}
