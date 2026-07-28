<?php

namespace Byl\Laravel\Testing;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Byl API-г сүлжээнд гарахгүйгээр хуурамчаар боловсруулна. `Byl::fake()`
 * дуудсаны дараа бүх хүсэлт бодит бүтэцтэй хариу авч, тестэд шалгах
 * боломжтойгоор бүртгэгдэнэ.
 */
class BylFake
{
    /** @var array<int, array{method: string, path: string, project_id: string, url: string, payload: array<string, mixed>, request: Request}> */
    protected array $recorded = [];

    /** @var array<int, array{method: string, path: string, value: mixed, status: int}> */
    protected array $stubs = [];

    protected int $sequence = 0;

    public function __construct(protected Factory $http, protected Repository $config)
    {
        $this->ensureConfigured();
        $this->registerStub();
    }

    /**
     * Тодорхой endpoint-ийн хариуг өөрөө тодорхойлно.
     *
     * ```php
     * Byl::fake()->respond('POST', 'invoices', ['id' => 99, 'status' => 'open']);
     * Byl::fake()->respond('GET', 'customers/*', fn () => [...], 200);
     * ```
     *
     * @param  array<string, mixed>|Closure|PromiseInterface  $value  `data` талбарын агуулга
     */
    public function respond(string $method, string $path, array|Closure|PromiseInterface $value, int $status = 200): static
    {
        // Хамгийн сүүлд нэмэгдсэн stub давуу эрхтэй.
        array_unshift($this->stubs, [
            'method' => strtoupper($method),
            'path' => $path,
            'value' => $value,
            'status' => $status,
        ]);

        return $this;
    }

    /**
     * Хүсэлт бүтэлгүйтсэн байдлаар хариулна.
     *
     * @param  array<string, mixed>  $body
     */
    public function respondWithError(string $method, string $path, int $status, array $body = []): static
    {
        return $this->respond($method, $path, Factory::response(
            $body ?: ['message' => 'Byl API алдаа.'],
            $status,
        ));
    }

    /**
     * @return Collection<int, array{method: string, path: string, project_id: string, url: string, payload: array<string, mixed>, request: Request}>
     */
    public function recorded(?string $method = null, ?string $path = null, ?callable $callback = null): Collection
    {
        return collect($this->recorded)
            ->when($method !== null, fn (Collection $records) => $records->where('method', strtoupper((string) $method)))
            ->when($path !== null, fn (Collection $records) => $records->filter(
                fn (array $record) => Str::is((string) $path, $record['path'])
            ))
            ->when($callback !== null, fn (Collection $records) => $records->filter(
                fn (array $record) => $callback($record['payload'], $record['request'])
            ))
            ->values();
    }

    /**
     * Тодорхой төсөл рүү хүсэлт илгээгдсэн эсэхийг шалгана.
     */
    public function assertSentForProject(int|string $projectId, string $method, string $path): static
    {
        PHPUnit::assertTrue(
            $this->recorded($method, $path)
                ->contains(fn (array $record) => $record['project_id'] === (string) $projectId),
            sprintf('%s төсөл дээр [%s %s] хүсэлт илгээгдээгүй байна.', $projectId, strtoupper($method), $path),
        );

        return $this;
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertSent(string $method, string $path, ?callable $callback = null): static
    {
        PHPUnit::assertTrue(
            $this->recorded($method, $path, $callback)->isNotEmpty(),
            sprintf('Byl-д [%s %s] хүсэлт илгээгдээгүй байна.', strtoupper($method), $path),
        );

        return $this;
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertNotSent(string $method, string $path, ?callable $callback = null): static
    {
        PHPUnit::assertTrue(
            $this->recorded($method, $path, $callback)->isEmpty(),
            sprintf('Byl-д [%s %s] хүсэлт илгээгдсэн байна.', strtoupper($method), $path),
        );

        return $this;
    }

    public function assertNothingSent(): static
    {
        PHPUnit::assertSame(
            [],
            $this->recorded()->map(fn (array $record) => $record['method'].' '.$record['path'])->all(),
            'Byl-д хүсэлт илгээгдээгүй байх ёстой байсан.',
        );

        return $this;
    }

    public function assertSentCount(int $count): static
    {
        PHPUnit::assertCount($count, $this->recorded, 'Byl-д илгээгдсэн хүсэлтийн тоо таарсангүй.');

        return $this;
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertInvoiceCreated(?callable $callback = null): static
    {
        return $this->assertSent('POST', 'invoices', $callback);
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertCheckoutCreated(?callable $callback = null): static
    {
        return $this->assertSent('POST', 'checkouts', $callback);
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertCustomerCreated(?callable $callback = null): static
    {
        return $this->assertSent('POST', 'customers', $callback);
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertTrialStarted(?callable $callback = null): static
    {
        return $this->assertSent('POST', 'subscriptions', $callback);
    }

    public function assertSubscriptionCanceled(int|string $subscriptionId): static
    {
        return $this->assertSent('POST', "subscriptions/{$subscriptionId}/cancel");
    }

    public function assertSubscriptionResumed(int|string $subscriptionId): static
    {
        return $this->assertSent('POST', "subscriptions/{$subscriptionId}/resume");
    }

    /**
     * @param  (callable(array<string, mixed>, Request): bool)|null  $callback
     */
    public function assertPortalSessionCreated(?callable $callback = null): static
    {
        return $this->assertSent('POST', 'billing-portal/sessions', $callback);
    }

    /**
     * Тестийн орчинд токен/төслийн ID шаардахгүй байхаар бөглөнө.
     */
    protected function ensureConfigured(): void
    {
        if (blank($this->config->get('byl.token'))) {
            $this->config->set('byl.token', 'byl-test-token');
        }

        if (blank($this->config->get('byl.project_id'))) {
            $this->config->set('byl.project_id', 1);
        }

        if (blank($this->config->get('byl.webhook.secret'))) {
            $this->config->set('byl.webhook.secret', 'byl-test-webhook-secret');
        }

        // Хуурамч хариун дээр дахин хүсэлт хийх нь тестийг л удаашруулна.
        $this->config->set('byl.retry.times', 0);
    }

    protected function registerStub(): void
    {
        $base = Str::after(rtrim((string) $this->config->get('byl.base_url', 'https://byl.mn'), '/'), '://');

        $this->http->fake([
            $base.'/api/v1/projects/*' => fn (Request $request) => $this->handle($request),
        ]);
    }

    protected function handle(Request $request): PromiseInterface
    {
        $path = $this->pathFor($request);
        $method = strtoupper($request->method());
        $payload = $this->payloadFor($request);

        $this->recorded[] = [
            'method' => $method,
            'path' => $path,
            'project_id' => $this->projectIdFor($request),
            'url' => $request->url(),
            'payload' => $payload,
            'request' => $request,
        ];

        foreach ($this->stubs as $stub) {
            if ($stub['method'] !== $method || ! Str::is($stub['path'], $path)) {
                continue;
            }

            $value = $stub['value'] instanceof Closure ? ($stub['value'])($request, $path) : $stub['value'];

            if ($value instanceof PromiseInterface) {
                return $value;
            }

            return Factory::response(['data' => $value], $stub['status']);
        }

        return $this->defaultResponse($method, $path, $payload);
    }

    /**
     * Endpoint-ийн бодит бүтэцтэй өгөгдмөл хариу.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function defaultResponse(string $method, string $path, array $payload): PromiseInterface
    {
        $id = $this->idFrom($path);

        return match (true) {
            $method === 'POST' && $path === 'invoices' => $this->data(
                InvoiceFactory::make($payload + ['id' => $this->nextId()]), 201
            ),
            $method === 'GET' && Str::is('invoices/*', $path) => $this->data(
                InvoiceFactory::make(['id' => $id, 'status' => 'open'])
            ),
            $method === 'POST' && Str::is('invoices/*/void', $path) => $this->data(
                InvoiceFactory::make(['id' => $this->idFrom($path, 1), 'status' => 'void'])
            ),
            $method === 'DELETE' && Str::is('invoices/*', $path) => $this->data([
                'id' => $id,
                'deleted_at' => Carbon::now()->toJSON(),
            ]),

            $method === 'POST' && $path === 'checkouts' => $this->data(
                CheckoutFactory::created($this->nextId(), (string) $this->config->get('byl.base_url')), 201
            ),
            $method === 'GET' && Str::is('checkouts/*', $path) => $this->data(
                CheckoutFactory::make(['id' => $id], (string) $this->config->get('byl.base_url'))
            ),

            $method === 'POST' && $path === 'customers' => $this->data(
                CustomerFactory::make($payload + ['id' => $this->nextId()]), 201
            ),
            $method === 'GET' && Str::is('customers/by-client-reference-id/*', $path) => $this->data(
                CustomerFactory::make([
                    'id' => $this->nextId(),
                    'client_reference_id' => rawurldecode((string) Str::afterLast($path, '/')),
                ])
            ),
            $method === 'GET' && Str::is('customers/*', $path) => $this->data(
                CustomerFactory::make(['id' => $id])
            ),

            $method === 'GET' && $path === 'subscriptions' => Factory::response([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 0],
            ]),
            $method === 'POST' && $path === 'subscriptions' => $this->data(
                SubscriptionFactory::trialing($payload + ['id' => $this->nextId()]), 201
            ),
            $method === 'GET' && Str::is('subscriptions/*', $path) => $this->data(
                SubscriptionFactory::make(['id' => $id])
            ),
            $method === 'POST' && Str::is('subscriptions/*/cancel', $path) => $this->data(
                SubscriptionFactory::make([
                    'id' => $this->idFrom($path, 1),
                    'canceled_at' => Carbon::now()->toJSON(),
                ])
            ),
            $method === 'POST' && Str::is('subscriptions/*/resume', $path) => $this->data(
                SubscriptionFactory::make(['id' => $this->idFrom($path, 1), 'canceled_at' => null])
            ),

            $method === 'POST' && $path === 'billing-portal/sessions' => $this->data([
                'url' => rtrim((string) $this->config->get('byl.base_url'), '/').'/h/billing-portal/'.Str::random(64),
                'expires_at' => Carbon::now()->addMinutes(30)->toJSON(),
            ], 201),

            default => Factory::response([
                'message' => sprintf('BylFake: [%s %s] endpoint-д өгөгдмөл хариу тодорхойлогдоогүй.', $method, $path),
            ], 404),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function data(array $data, int $status = 200): PromiseInterface
    {
        return Factory::response(['data' => $data], $status);
    }

    /**
     * Хүсэлтийн хаягаас төслийн дараах хэсгийг (query-гүй) гаргаж авна.
     */
    protected function pathFor(Request $request): string
    {
        $path = (string) preg_replace(
            '#^.*/api/v1/projects/[^/]+/#',
            '',
            Str::before($request->url(), '?'),
        );

        return trim($path, '/');
    }

    /**
     * Хүсэлт хэдэн дугаартай төсөл рүү явсныг буцаана.
     */
    protected function projectIdFor(Request $request): string
    {
        preg_match('#/api/v1/projects/([^/]+)/#', $request->url(), $matches);

        return $matches[1] ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadFor(Request $request): array
    {
        $data = $request->data();

        return is_array($data) ? $data : [];
    }

    /**
     * Path-ын сегментээс ID гаргаж авна (сүүлээс $fromEnd-р сегмент).
     */
    protected function idFrom(string $path, int $fromEnd = 0): int|string|null
    {
        $segments = explode('/', $path);
        $segment = Arr::get(array_reverse($segments), $fromEnd);

        return is_numeric($segment) ? (int) $segment : $segment;
    }

    protected function nextId(): int
    {
        return ++$this->sequence + 1000;
    }
}
