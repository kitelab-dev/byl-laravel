<?php

namespace Byl\Laravel\Data;

use Byl\Laravel\Enums\InvoiceStatus;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

final class Invoice extends Data implements Responsable
{
    use RedirectsToUrl;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?InvoiceStatus $status,
        public readonly ?float $amount,
        public readonly ?string $description,
        public readonly ?int $customerId,
        public readonly ?string $number,
        public readonly ?int $projectId,
        public readonly ?string $url,
        public readonly ?Carbon $dueDate,
        public readonly ?Customer $customer,
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
        $customer = Arr::get($data, 'customer');

        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            status: self::enum(InvoiceStatus::class, Arr::get($data, 'status')),
            amount: self::number(Arr::get($data, 'amount')),
            description: Arr::get($data, 'description'),
            customerId: Arr::get($data, 'customer_id') !== null ? (int) Arr::get($data, 'customer_id') : null,
            number: Arr::get($data, 'number'),
            projectId: Arr::get($data, 'project_id') !== null ? (int) Arr::get($data, 'project_id') : null,
            url: Arr::get($data, 'url'),
            dueDate: self::date(Arr::get($data, 'due_date')),
            customer: is_array($customer) ? Customer::fromArray($customer) : null,
            createdAt: self::date(Arr::get($data, 'created_at')),
            updatedAt: self::date(Arr::get($data, 'updated_at')),
            raw: $data,
        );
    }

    public function isPaid(): bool
    {
        return $this->status?->isPaid() ?? false;
    }

    public function isVoid(): bool
    {
        return $this->status === InvoiceStatus::Void;
    }

    /**
     * Хэрэглэгчийг нэхэмжлэхийн төлбөрийн хуудас руу чиглүүлнэ.
     */
    public function toResponse($request): RedirectResponse
    {
        return $this->redirectToUrl();
    }
}
