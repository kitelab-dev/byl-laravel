<?php

namespace Byl\Laravel\Billing\Concerns;

use Byl\Laravel\Data\Customer;
use Byl\Laravel\Facades\Byl;
use LogicException;

trait ManagesBylCustomer
{
    /**
     * Byl дээрх харилцагчийн ID.
     */
    public function bylCustomerId(): ?int
    {
        $id = $this->getAttribute('byl_customer_id');

        return $id === null ? null : (int) $id;
    }

    public function hasBylCustomerId(): bool
    {
        return $this->bylCustomerId() !== null;
    }

    /**
     * Byl-д дамжуулах `client_reference_id`. Хоёр системийн хэрэглэгчийг
     * хооронд нь map хийхэд хэрэглэгддэг тул давтагдашгүй байх ёстой.
     */
    public function bylClientReferenceId(): string
    {
        $column = config('byl.billable.client_reference_column');

        return (string) ($column ? $this->getAttribute($column) : $this->getKey());
    }

    public function bylCustomerName(): ?string
    {
        return $this->getAttribute('name');
    }

    public function bylCustomerEmail(): ?string
    {
        return $this->getAttribute('email');
    }

    public function bylCustomerPhone(): ?string
    {
        return $this->getAttribute('phone');
    }

    /**
     * Byl дээр харилцагч үүсгэх (эсвэл байгааг шинэчлэх) ба ID-г хадгална.
     * `client_reference_id`-аар upsert болдог тул хэд ч удаа дуудаж болно.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createOrGetBylCustomer(array $attributes = []): Customer
    {
        $customer = Byl::customers()->upsert(array_filter([
            'email' => $this->bylCustomerEmail(),
            'name' => $this->bylCustomerName(),
            'phone' => $this->bylCustomerPhone(),
            'client_reference_id' => $this->bylClientReferenceId(),
            ...$attributes,
        ], fn ($value) => $value !== null));

        if ($customer->id !== null && $customer->id !== $this->bylCustomerId()) {
            $this->forceFill(['byl_customer_id' => $customer->id])->save();
        }

        return $customer;
    }

    /**
     * Byl дээрх харилцагчийн мэдээллийг (эрхтэй захиалгуудын хамт) авна.
     */
    public function asBylCustomer(): Customer
    {
        return $this->hasBylCustomerId()
            ? Byl::customers()->find($this->bylCustomerId())
            : Byl::customers()->findByClientReferenceId($this->bylClientReferenceId());
    }

    /**
     * Нэр, и-мэйл, утсаа Byl дээрх харилцагч руу тохируулна.
     */
    public function syncBylCustomerDetails(): Customer
    {
        return $this->createOrGetBylCustomer();
    }

    /**
     * Checkout, нэхэмжлэх зэрэгт хэрэглэх харилцагчийн ID — байхгүй бол үүсгэнэ.
     */
    public function bylCustomerIdOrCreate(): int
    {
        if ($this->hasBylCustomerId()) {
            return $this->bylCustomerId();
        }

        $customer = $this->createOrGetBylCustomer();

        return $customer->id ?? throw new LogicException('Byl харилцагч үүсгэхэд ID буцаж ирсэнгүй.');
    }
}
