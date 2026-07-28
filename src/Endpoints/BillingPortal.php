<?php

namespace Byl\Laravel\Endpoints;

use Byl\Laravel\Data\PortalSession;

/**
 * Billing portal — https://byl.mn/docs/api/billing-portal
 */
class BillingPortal extends Endpoint
{
    /**
     * Харилцагчид зориулсан түр хугацааны portal session үүсгэнэ (30 минут).
     * Хэрэглэгч portal-д орох бүрд шинээр үүсгэж чиглүүлнэ.
     */
    public function createSession(int|string $customerId): PortalSession
    {
        return PortalSession::fromArray($this->client->data(
            $this->client->post('billing-portal/sessions', ['customer_id' => $customerId])
        ));
    }
}
