<?php

namespace Byl\Laravel\Http\Controllers;

use Byl\Laravel\Events\WebhookReceived;
use Byl\Laravel\Webhooks\WebhookEvent;
use Byl\Laravel\Webhooks\WebhookEventMap;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Byl-ээс ирсэн webhook-ийг Laravel event болгон хувиргана. Гарын үсгийн
 * шалгалт нь route-д залгагдсан `byl-signature` middleware дээр хийгдэнэ.
 */
class HandleWebhookController
{
    public function __construct(
        protected Dispatcher $events,
        protected Cache $cache,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        $event = WebhookEvent::fromArray(is_array($payload) ? $payload : []);

        if ($this->isDuplicate($event)) {
            return response()->json(['received' => true, 'duplicate' => true]);
        }

        $this->events->dispatch(new WebhookReceived($event));

        if ($typed = WebhookEventMap::make($event)) {
            $this->events->dispatch($typed);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Byl амжилтгүй хүсэлтээ дахин илгээдэг. Тохиргоогоор идэвхжүүлсэн үед
     * ижил event ID хоёр дахь удаа боловсруулагдахгүй.
     */
    protected function isDuplicate(WebhookEvent $event): bool
    {
        if ($event->id === null || ! config('byl.webhook.prevent_duplicate_events', false)) {
            return false;
        }

        $key = sprintf('byl:webhook:%s:%s', $event->projectId ?? '0', $event->id);

        return ! $this->cache->add($key, true, (int) config('byl.webhook.duplicate_ttl', 86400));
    }
}
