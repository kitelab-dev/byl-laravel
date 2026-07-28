<?php

use Byl\Laravel\Webhooks\WebhookSignature;

it('Byl-ийн алгоритмтай ижил гарын үсэг үүсгэнэ', function () {
    $payload = '{"id":3,"type":"invoice.paid"}';

    expect(WebhookSignature::compute($payload, 'secret'))
        ->toBe(hash_hmac('sha256', $payload, 'secret'));
});

it('гарын үсгийг шалгана', function () {
    $payload = '{"id":3}';
    $signature = WebhookSignature::compute($payload, 'secret');

    expect(WebhookSignature::verify($payload, $signature, 'secret'))->toBeTrue()
        ->and(WebhookSignature::verify($payload, $signature, 'өөр-secret'))->toBeFalse()
        ->and(WebhookSignature::verify('{"id":4}', $signature, 'secret'))->toBeFalse()
        ->and(WebhookSignature::verify($payload, null, 'secret'))->toBeFalse()
        ->and(WebhookSignature::verify($payload, '', 'secret'))->toBeFalse()
        ->and(WebhookSignature::verify($payload, $signature, ''))->toBeFalse();
});
