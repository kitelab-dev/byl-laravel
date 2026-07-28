<?php

namespace Byl\Laravel\Data;

use Illuminate\Support\Arr;

final class Product extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $clientReferenceId,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            name: Arr::get($data, 'name'),
            clientReferenceId: Arr::get($data, 'client_reference_id'),
            raw: $data,
        );
    }
}
