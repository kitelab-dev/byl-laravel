<?php

namespace Byl\Laravel\Events;

use Byl\Laravel\Webhooks\WebhookEvent;

/**
 * Гарын үсэг шалгагдсан webhook бүр дээр илгээгдэнэ — төрлөөс үл хамааран
 * бүх event-ийг нэг дор боловсруулах хэрэгтэй үед хэрэглэнэ.
 */
class WebhookReceived
{
    public function __construct(public readonly WebhookEvent $event) {}
}
