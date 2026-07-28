<?php

namespace Byl\Laravel\Exceptions;

/**
 * HTTP 429 — хүсэлтийн хязгаарт хүрсэн.
 */
class RateLimitException extends ApiException
{
    /**
     * Хэдэн секундын дараа дахин хүсэлт илгээхийг зөвшөөрөх (Retry-After).
     */
    public function retryAfter(): ?int
    {
        $value = $this->response?->header('Retry-After');

        return is_numeric($value) ? (int) $value : null;
    }
}
