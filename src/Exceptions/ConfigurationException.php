<?php

namespace Byl\Laravel\Exceptions;

use InvalidArgumentException;

/**
 * config/byl.php (эсвэл .env) дутуу тохируулагдсан.
 */
class ConfigurationException extends InvalidArgumentException implements BylException
{
    public static function missingToken(): self
    {
        return new self('Byl API токен тохируулаагүй байна. .env файлд BYL_TOKEN нэмнэ үү.');
    }

    public static function missingProjectId(): self
    {
        return new self('Byl төслийн ID тохируулаагүй байна. .env файлд BYL_PROJECT_ID нэмэх эсвэл Byl::project($id) гэж дуудна уу.');
    }

    public static function missingWebhookSecret(): self
    {
        return new self('Byl webhook secret тохируулаагүй байна. .env файлд BYL_WEBHOOK_SECRET нэмнэ үү.');
    }
}
