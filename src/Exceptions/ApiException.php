<?php

namespace Byl\Laravel\Exceptions;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use RuntimeException;

class ApiException extends RuntimeException implements BylException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(
        string $message,
        protected int $status = 0,
        protected array $errors = [],
        protected ?Response $response = null,
    ) {
        parent::__construct($message, $status);
    }

    /**
     * Хариуны статусаас хамааран тохирох exception-ийг үүсгэнэ.
     */
    public static function fromResponse(Response $response, string $method = '', string $url = ''): self
    {
        $status = $response->status();
        $payload = is_array($decoded = $response->json()) ? $decoded : [];

        $message = Arr::get($payload, 'message')
            ?: sprintf('Byl API %s %s хүсэлт HTTP %d статустай хариу буцаалаа.', strtoupper($method), $url, $status);

        $errors = Arr::wrap(Arr::get($payload, 'errors', []));

        $exception = match (true) {
            $status === 401 => AuthenticationException::class,
            $status === 403 => AuthorizationException::class,
            $status === 404 => NotFoundException::class,
            $status === 409 => ConflictException::class,
            $status === 422 => ValidationException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServerException::class,
            default => self::class,
        };

        return new $exception($message, $status, $errors, $response);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    public function response(): ?Response
    {
        return $this->response;
    }

    public function body(): ?string
    {
        return $this->response?->body();
    }
}
