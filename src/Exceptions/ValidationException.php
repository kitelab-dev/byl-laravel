<?php

namespace Byl\Laravel\Exceptions;

use Illuminate\Support\Arr;

/**
 * HTTP 422 — дамжуулсан параметер Byl-ийн шаардлагыг хангаагүй.
 */
class ValidationException extends ApiException
{
    /**
     * Тухайн талбарын анхны алдааны мессеж.
     */
    public function errorFor(string $field): ?string
    {
        return Arr::first(Arr::wrap(Arr::get($this->errors, $field, [])));
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $messages) {
            if ($message = Arr::first(Arr::wrap($messages))) {
                return $message;
            }
        }

        return null;
    }
}
