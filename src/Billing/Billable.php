<?php

namespace Byl\Laravel\Billing;

use Byl\Laravel\Billing\Concerns\ManagesBylBillingPortal;
use Byl\Laravel\Billing\Concerns\ManagesBylCustomer;
use Byl\Laravel\Billing\Concerns\ManagesBylSubscriptions;
use Byl\Laravel\Billing\Concerns\PerformsBylCheckouts;

/**
 * Byl-ийн төлбөр, захиалгыг модель дээрээ ашиглах trait.
 *
 * ```php
 * class User extends Authenticatable
 * {
 *     use Byl\Laravel\Billing\Billable;
 * }
 *
 * $user->newSubscription('starter_monthly')->checkout();
 * $user->subscribed();          // эрхтэй эсэх (локал шалгалт)
 * $user->redirectToBillingPortal();
 * ```
 *
 * Модель `byl_customer_id` багана шаардана — `byl-migrations` tag-аар
 * migration хуулж авна.
 */
trait Billable
{
    use ManagesBylBillingPortal;
    use ManagesBylCustomer;
    use ManagesBylSubscriptions;
    use PerformsBylCheckouts;
}
