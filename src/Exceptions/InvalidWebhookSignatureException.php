<?php

namespace Byl\Laravel\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * `Byl-Signature` header ирээгүй эсвэл payload-тай тохироогүй. HTTP 401
 * буцаадаг тул Byl хүсэлтээ амжилтгүйд тооцож дахин илгээнэ.
 */
class InvalidWebhookSignatureException extends HttpException implements BylException
{
    public function __construct(string $message = 'Byl webhook гарын үсэг тохирсонгүй.')
    {
        parent::__construct(401, $message);
    }
}
