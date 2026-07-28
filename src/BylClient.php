<?php

namespace Byl\Laravel;

use Byl\Laravel\Endpoints\BillingPortal;
use Byl\Laravel\Endpoints\Checkouts;
use Byl\Laravel\Endpoints\Customers;
use Byl\Laravel\Endpoints\Invoices;
use Byl\Laravel\Endpoints\Subscriptions;
use Byl\Laravel\Exceptions\ApiException;
use Byl\Laravel\Exceptions\ConfigurationException;
use Byl\Laravel\Exceptions\ConnectionException;
use Illuminate\Http\Client\ConnectionException as HttpConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Нэг төсөлд холбогдсон API клиент.
 */
class BylClient
{
    /**
     * @param  array{times?: int, sleep?: int}  $retry
     */
    public function __construct(
        protected Factory $http,
        protected string $baseUrl = 'https://byl.mn',
        protected ?string $token = null,
        protected int|string|null $projectId = null,
        protected int $timeout = 15,
        protected array $retry = [],
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function invoices(): Invoices
    {
        return new Invoices($this);
    }

    public function checkouts(): Checkouts
    {
        return new Checkouts($this);
    }

    public function customers(): Customers
    {
        return new Customers($this);
    }

    public function subscriptions(): Subscriptions
    {
        return new Subscriptions($this);
    }

    public function billingPortal(): BillingPortal
    {
        return new BillingPortal($this);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, ['query' => array_filter($query, fn ($value) => $value !== null)]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload = []): array
    {
        return $this->send('post', $path, ['json' => $payload]);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->send('delete', $path);
    }

    /**
     * Хариунаас `data` талбарыг гаргаж авна.
     *
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    public function data(array $response): array
    {
        $data = Arr::get($response, 'data', []);

        return is_array($data) ? $data : [];
    }

    public function projectId(): int|string
    {
        if (blank($this->projectId)) {
            throw ConfigurationException::missingProjectId();
        }

        return $this->projectId;
    }

    public function baseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Тухайн төслийн API endpoint-ийн бүтэн хаяг.
     */
    public function url(string $path = ''): string
    {
        return sprintf('%s/api/v1/projects/%s/%s', $this->baseUrl, $this->projectId(), ltrim($path, '/'));
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    protected function send(string $method, string $path, array $options = []): array
    {
        $url = $this->url($path);

        try {
            $response = $this->request()->send(strtoupper($method), $url, $options);
        } catch (HttpConnectionException $exception) {
            throw new ConnectionException(
                sprintf('Byl-тэй холбогдож чадсангүй: %s', $exception->getMessage()),
                previous: $exception,
            );
        }

        if ($response->failed()) {
            throw ApiException::fromResponse($response, $method, $url);
        }

        $decoded = $response->json();

        return is_array($decoded) ? $decoded : [];
    }

    protected function request(): PendingRequest
    {
        $request = $this->http
            ->withToken($this->requireToken())
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout);

        $times = (int) ($this->retry['times'] ?? 0);

        if ($times > 1) {
            // Зөвхөн дахин хийхэд утга бүхий алдаанууд дээр дахина —
            // validation зэрэг 4xx хариу шууд exception болно.
            $request->retry(
                $times,
                (int) ($this->retry['sleep'] ?? 0),
                fn (Throwable $exception) => $exception instanceof HttpConnectionException
                    || ($exception instanceof RequestException && $this->isRetryableStatus($exception->response)),
                throw: false,
            );
        }

        return $request;
    }

    protected function isRetryableStatus(Response $response): bool
    {
        return $response->status() === 429 || $response->serverError();
    }

    protected function requireToken(): string
    {
        if (blank($this->token)) {
            throw ConfigurationException::missingToken();
        }

        return $this->token;
    }
}
