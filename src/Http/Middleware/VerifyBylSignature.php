<?php

namespace Byl\Laravel\Http\Middleware;

use Byl\Laravel\Exceptions\ConfigurationException;
use Byl\Laravel\Exceptions\InvalidWebhookSignatureException;
use Byl\Laravel\Webhooks\WebhookSignature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `Byl-Signature` header-ийг хүсэлтийн raw body дээр шалгана. Байхгүй эсвэл
 * тохирохгүй бол 401 буцаана — Byl үүнийг амжилтгүйд тооцож дахин илгээнэ.
 */
class VerifyBylSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('byl.webhook.secret');

        if (blank($secret)) {
            throw ConfigurationException::missingWebhookSecret();
        }

        $verified = WebhookSignature::verify(
            $request->getContent(),
            $request->header(WebhookSignature::HEADER),
            (string) $secret,
        );

        if (! $verified) {
            throw new InvalidWebhookSignatureException;
        }

        return $next($request);
    }
}
