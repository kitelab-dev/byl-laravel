<?php

namespace Byl\Laravel\Endpoints;

use Byl\Laravel\Data\Customer;
use Byl\Laravel\Exceptions\NotFoundException;

/**
 * Харилцагч — https://byl.mn/docs/api/customers
 */
class Customers extends Endpoint
{
    /**
     * Харилцагч үүсгэнэ. `client_reference_id` дамжуулсан үед upsert
     * байдлаар ажилладаг тул давхардал үүсгэхгүй — checkout үүсгэхийн
     * өмнө бүр удаа дуудаж болно.
     *
     * Дэмждэг талбарууд: `email` (заавал), `name`, `phone`, `client_reference_id`.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Customer
    {
        return Customer::fromArray(
            $this->client->data($this->client->post('customers', $attributes))
        );
    }

    /**
     * `create()`-ийн уншихад ойлгомжтой хувилбар — upsert семантикийг илэрхийлнэ.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(array $attributes): Customer
    {
        return $this->create($attributes);
    }

    /**
     * Хариунд харилцагчийн эрхтэй захиалгууд хамт ирнэ.
     */
    public function find(int|string $id): Customer
    {
        return Customer::fromArray(
            $this->client->data($this->client->get("customers/{$id}"))
        );
    }

    /**
     * Өөрийн системийн хэрэглэгчийн ID-гээр лавлана.
     *
     * @throws NotFoundException олдоогүй бол
     */
    public function findByClientReferenceId(string $clientReferenceId): Customer
    {
        return Customer::fromArray($this->client->data(
            $this->client->get('customers/by-client-reference-id/'.rawurlencode($clientReferenceId))
        ));
    }

    /**
     * Өөрийн системийн хэрэглэгчийн ID-гээр лавлаж, олдоогүй бол `null` буцаана.
     */
    public function findByClientReferenceIdOrNull(string $clientReferenceId): ?Customer
    {
        try {
            return $this->findByClientReferenceId($clientReferenceId);
        } catch (NotFoundException) {
            return null;
        }
    }
}
