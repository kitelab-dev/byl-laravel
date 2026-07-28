<?php

namespace Byl\Laravel\Data;

use ArrayIterator;
use Countable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Traversable;

/**
 * Хуудаслалттай хариу. Шууд `foreach`-оор давтаж болно.
 *
 * @template TItem
 *
 * @implements IteratorAggregate<int, TItem>
 */
final class Page extends Data implements Countable, IteratorAggregate
{
    /**
     * @param  Collection<int, TItem>  $items
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly Collection $items,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @template TMapped
     *
     * @param  array<string, mixed>  $response
     * @param  callable(array<string, mixed>): TMapped  $mapper
     * @return self<TMapped>
     */
    public static function fromResponse(array $response, callable $mapper): self
    {
        $items = collect(Arr::get($response, 'data', []))
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => $mapper($item))
            ->values();

        return new self(
            items: $items,
            currentPage: (int) Arr::get($response, 'meta.current_page', 1),
            lastPage: (int) Arr::get($response, 'meta.last_page', 1),
            perPage: (int) Arr::get($response, 'meta.per_page', $items->count()),
            total: (int) Arr::get($response, 'meta.total', $items->count()),
            raw: $response,
        );
    }

    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    /**
     * @return Collection<int, TItem>
     */
    public function items(): Collection
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function isNotEmpty(): bool
    {
        return $this->items->isNotEmpty();
    }

    /**
     * @return TItem|null
     */
    public function first(): mixed
    {
        return $this->items->first();
    }

    /**
     * Хуудасны item-үүд. API-ийн бүтэн хариу хэрэгтэй бол `raw()` ашиглана.
     *
     * @return array<int, TItem>
     */
    public function toArray(): array
    {
        return $this->items->all();
    }

    public function jsonSerialize(): mixed
    {
        return $this->items->all();
    }

    public function count(): int
    {
        return $this->items->count();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items->all());
    }
}
