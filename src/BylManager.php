<?php

namespace Byl\Laravel;

use Byl\Laravel\Testing\BylFake;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Traits\ForwardsCalls;
use LogicException;

/**
 * Byl facade-ийн үндэс. Тохиргоонд заасан төслийн клиентийг үүсгэж,
 * дуудагдсан method-уудыг түүн рүү дамжуулна.
 *
 * @mixin BylClient
 */
class BylManager
{
    use ForwardsCalls;

    /** @var array<string, BylClient> */
    protected array $clients = [];

    protected ?BylFake $fake = null;

    /** @var (Closure(string): ?Model)|null */
    protected static ?Closure $billableResolver = null;

    public function __construct(protected Factory $http, protected Repository $config) {}

    /**
     * Webhook дахь `client_reference_id`-аас billable модельоо хэрхэн олохыг
     * тодорхойлно. Заагаагүй бол `byl.billable.model` тохиргоо хэрэглэгдэнэ.
     *
     * @param  (Closure(string): ?Model)|null  $resolver
     */
    public function resolveBillableUsing(?Closure $resolver): void
    {
        static::$billableResolver = $resolver;
    }

    /**
     * @return (Closure(string): ?Model)|null
     */
    public function billableResolver(): ?Closure
    {
        return static::$billableResolver;
    }

    /**
     * Тохиргоонд заасан төслийн клиент.
     */
    public function client(): BylClient
    {
        return $this->resolve($this->config->get('byl.project_id'), $this->config->get('byl.token'));
    }

    /**
     * Өөр төсөл дээр ажиллах клиент. Хэрэв тухайн төсөл өөр токентой бол
     * хоёр дахь параметрээр дамжуулна.
     */
    public function project(int|string $projectId, ?string $token = null): BylClient
    {
        return $this->resolve($projectId, $token ?? $this->config->get('byl.token'));
    }

    /**
     * Тохиргооны токеныг өөр токенээр солин ажиллах.
     */
    public function withToken(string $token, int|string|null $projectId = null): BylClient
    {
        return $this->resolve($projectId ?? $this->config->get('byl.project_id'), $token);
    }

    /**
     * API хүсэлтүүдийг хуурамчаар (сүлжээнд гарахгүйгээр) боловсруулна.
     * Тестэд л зориулагдсан.
     */
    public function fake(): BylFake
    {
        return $this->fake ??= new BylFake($this->http, $this->config);
    }

    public function isFaked(): bool
    {
        return $this->fake !== null;
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        if ($this->fake !== null && method_exists($this->fake, $method)) {
            return $this->fake->{$method}(...$parameters);
        }

        if (str_starts_with($method, 'assert')) {
            throw new LogicException(sprintf(
                'Byl::%s() зөвхөн Byl::fake() дуудсаны дараа ажиллана.', $method
            ));
        }

        return $this->forwardCallTo($this->client(), $method, $parameters);
    }

    protected function resolve(int|string|null $projectId, ?string $token): BylClient
    {
        $key = sprintf('%s|%s', $projectId ?? '-', $token === null ? '-' : md5($token));

        return $this->clients[$key] ??= new BylClient(
            http: $this->http,
            baseUrl: (string) $this->config->get('byl.base_url', 'https://byl.mn'),
            token: $token,
            projectId: $projectId,
            timeout: (int) $this->config->get('byl.timeout', 15),
            retry: (array) $this->config->get('byl.retry', []),
        );
    }
}
