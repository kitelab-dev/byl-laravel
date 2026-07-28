<?php

namespace Byl\Laravel\Billing\Concerns;

use Byl\Laravel\Data\PortalSession;
use Byl\Laravel\Facades\Byl;
use Illuminate\Http\RedirectResponse;

trait ManagesBylBillingPortal
{
    /**
     * Хэрэглэгчид зориулсан billing portal session (30 минут хүчинтэй).
     */
    public function billingPortalSession(): PortalSession
    {
        return Byl::billingPortal()->createSession($this->bylCustomerIdOrCreate());
    }

    public function billingPortalUrl(): string
    {
        return (string) $this->billingPortalSession()->url;
    }

    /**
     * Хэрэглэгчийг захиалгын удирдлагын хуудас руу чиглүүлнэ.
     */
    public function redirectToBillingPortal(): RedirectResponse
    {
        return new RedirectResponse($this->billingPortalUrl());
    }
}
