<?php

namespace Byl\Laravel\Data;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;

/**
 * Checkout үүсгэх endpoint зөвхөн `id` ба `url` буцаадаг. Дэлгэрэнгүй
 * мэдээлэл хэрэгтэй бол `Byl::checkouts()->find($id)` гэж лавлана.
 */
final class CreatedCheckout extends Data implements Responsable
{
    use RedirectsToUrl;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $url,
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
            url: Arr::get($data, 'url'),
            raw: $data,
        );
    }

    /**
     * Хэрэглэгчийг Byl-ийн checkout хуудас руу чиглүүлнэ.
     */
    public function toResponse($request): RedirectResponse
    {
        return $this->redirectToUrl();
    }
}
