<?php

namespace Byl\Laravel\Exceptions;

use Throwable;

/**
 * SDK-ийн бүх exception энэ interface-ийг хэрэгжүүлнэ, тиймээс Byl-тэй
 * холбоотой бүх алдааг нэг catch-аар барьж болно.
 */
interface BylException extends Throwable {}
