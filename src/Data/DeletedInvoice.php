<?php

namespace Byl\Laravel\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

final class DeletedInvoice extends Data
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?Carbon $deletedAt,
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
            deletedAt: self::date(Arr::get($data, 'deleted_at')),
            raw: $data,
        );
    }
}
