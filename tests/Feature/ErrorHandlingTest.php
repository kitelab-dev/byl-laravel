<?php

use Byl\Laravel\Exceptions\ApiException;
use Byl\Laravel\Exceptions\AuthenticationException;
use Byl\Laravel\Exceptions\AuthorizationException;
use Byl\Laravel\Exceptions\BylException;
use Byl\Laravel\Exceptions\ConfigurationException;
use Byl\Laravel\Exceptions\ConnectionException;
use Byl\Laravel\Exceptions\NotFoundException;
use Byl\Laravel\Exceptions\RateLimitException;
use Byl\Laravel\Exceptions\ServerException;
use Byl\Laravel\Exceptions\ValidationException;
use Byl\Laravel\Facades\Byl;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Support\Facades\Http;

it('статус тус бүрийг тохирох exception болгоно', function (int $status, string $exception) {
    Http::fake(['byl.mn/*' => Http::response(['message' => 'Алдаа'], $status)]);

    expect(fn () => Byl::invoices()->find(1))->toThrow($exception);
})->with([
    [401, AuthenticationException::class],
    [403, AuthorizationException::class],
    [404, NotFoundException::class],
    [422, ValidationException::class],
    [429, RateLimitException::class],
    [500, ServerException::class],
    [418, ApiException::class],
]);

it('validation алдааны талбаруудыг уншина', function () {
    Http::fake(['byl.mn/*' => Http::response([
        'message' => 'The amount field is required.',
        'errors' => ['amount' => ['The amount field is required.']],
    ], 422)]);

    try {
        Byl::invoices()->create([]);
        $this->fail('ValidationException хүлээгдэж байсан.');
    } catch (ValidationException $exception) {
        expect($exception->status())->toBe(422)
            ->and($exception->errorFor('amount'))->toBe('The amount field is required.')
            ->and($exception->firstError())->toBe('The amount field is required.')
            ->and($exception->errors())->toHaveKey('amount')
            ->and($exception)->toBeInstanceOf(BylException::class);
    }
});

it('rate limit алдаанаас Retry-After уншина', function () {
    Http::fake(['byl.mn/*' => Http::response(['message' => 'Too many requests.'], 429, ['Retry-After' => '30'])]);

    try {
        Byl::invoices()->find(1);
        $this->fail('RateLimitException хүлээгдэж байсан.');
    } catch (RateLimitException $exception) {
        expect($exception->retryAfter())->toBe(30);
    }
});

it('холболтын алдааг SDK-ийн exception болгоно', function () {
    Http::fake(fn () => throw new HttpConnectionException('cURL error 28: timeout'));

    expect(fn () => Byl::invoices()->find(1))
        ->toThrow(ConnectionException::class, 'Byl-тэй холбогдож чадсангүй');
});

it('токен байхгүй бол тохиргооны алдаа шиднэ', function () {
    config()->set('byl.token', null);

    expect(fn () => Byl::invoices()->find(1))
        ->toThrow(ConfigurationException::class, 'BYL_TOKEN');
});

it('төслийн ID байхгүй бол тохиргооны алдаа шиднэ', function () {
    config()->set('byl.project_id', null);

    expect(fn () => Byl::invoices()->find(1))
        ->toThrow(ConfigurationException::class, 'BYL_PROJECT_ID');
});

it('JSON биш хариу дээр ч тодорхой мессежтэй алдаа шиднэ', function () {
    Http::fake(['byl.mn/*' => Http::response('<html>502 Bad Gateway</html>', 502)]);

    expect(fn () => Byl::invoices()->find(1))
        ->toThrow(ServerException::class, 'HTTP 502');
});
