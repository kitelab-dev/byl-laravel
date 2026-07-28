<?php

namespace Byl\Laravel\Billing\Listeners;

use Byl\Laravel\Events\WebhookReceived;
use Byl\Laravel\Facades\Byl;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;

/**
 * `subscription.*` webhook ирэх бүрд билэйбл модельийн локал захиалгыг
 * шинэчилнэ — ингэснээр эрхийн шалгалт сүлжээнд гарахгүй.
 */
class SyncBylSubscription
{
    public function __construct(protected Repository $config) {}

    public function handle(WebhookReceived $event): void
    {
        if ($event->event->object !== 'subscription') {
            return;
        }

        $subscription = $event->event->subscription();

        if ($subscription->id === null) {
            return;
        }

        $billable = $this->resolveBillable(
            $event->event->raw('data.object.customer.client_reference_id')
        );

        if ($billable === null) {
            return;
        }

        // Захиалга үүсэх үед харилцагчийн ID-г ч холбоно — checkout-ыг Byl
        // дээрээс (жш: удирдлагын панелаас) үүсгэсэн байсан ч холбогдоно.
        if ($subscription->customerId !== null && $billable->getAttribute('byl_customer_id') === null) {
            $billable->forceFill(['byl_customer_id' => $subscription->customerId])->save();
        }

        $billable->recordBylSubscription($subscription);
    }

    protected function resolveBillable(mixed $clientReferenceId): ?Model
    {
        if (blank($clientReferenceId)) {
            return null;
        }

        if ($resolver = Byl::billableResolver()) {
            return $resolver((string) $clientReferenceId);
        }

        /** @var class-string<Model>|null $model */
        $model = $this->config->get('byl.billable.model');

        if ($model === null || ! class_exists($model)) {
            return null;
        }

        $column = $this->config->get('byl.billable.client_reference_column');

        return $column
            ? $model::query()->where($column, $clientReferenceId)->first()
            : $model::query()->find($clientReferenceId);
    }
}
