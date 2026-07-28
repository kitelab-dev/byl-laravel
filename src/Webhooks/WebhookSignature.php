<?php

namespace Byl\Laravel\Webhooks;

/**
 * Byl webhook бүрийг `Byl-Signature` header-т байрлах HMAC-SHA256 гарын
 * үсгээр илгээдэг. Гарын үсэг нь хүсэлтийн raw body-гоос тооцоологдоно.
 */
class WebhookSignature
{
    public const HEADER = 'Byl-Signature';

    public static function compute(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }

    /**
     * Timing attack-аас сэргийлж `hash_equals`-ээр харьцуулна.
     */
    public static function verify(string $payload, ?string $signature, string $secret): bool
    {
        if (blank($signature) || blank($secret)) {
            return false;
        }

        return hash_equals(self::compute($payload, $secret), $signature);
    }
}
