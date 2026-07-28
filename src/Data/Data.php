<?php

namespace Byl\Laravel\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use JsonSerializable;

/**
 * @implements Arrayable<string, mixed>
 */
abstract class Data implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, mixed>  $raw  API-с ирсэн хариу бүтэн хэлбэрээрээ.
     */
    public function __construct(protected readonly array $raw = []) {}

    /**
     * API-ийн хариунаас шууд уншина. SDK-д ороогүй шинэ талбарыг ингэж авна.
     */
    public function raw(?string $key = null, mixed $default = null): mixed
    {
        return $key === null ? $this->raw : Arr::get($this->raw, $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    public function jsonSerialize(): mixed
    {
        return $this->raw;
    }

    protected static function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }

    protected static function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return T|null
     */
    protected static function enum(string $enum, mixed $value): ?object
    {
        if (! is_string($value) && ! is_int($value)) {
            return $value instanceof $enum ? $value : null;
        }

        return $enum::tryFrom($value);
    }
}
