<?php

namespace Byl\Laravel\Data;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Billing portal-ийн түр хугацааны session. Үүсгэснээс хойш 30 минут
 * хүчинтэй тул хадгалж дахин ашиглах шаардлагагүй.
 */
final class PortalSession extends Data implements Responsable
{
    use RedirectsToUrl;

    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?string $url,
        public readonly ?Carbon $expiresAt,
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
            url: Arr::get($data, 'url'),
            expiresAt: self::date(Arr::get($data, 'expires_at')),
            raw: $data,
        );
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt->isPast();
    }

    /**
     * Хэрэглэгчийг billing portal руу чиглүүлнэ.
     */
    public function toResponse($request): RedirectResponse
    {
        return $this->redirectToUrl();
    }
}
