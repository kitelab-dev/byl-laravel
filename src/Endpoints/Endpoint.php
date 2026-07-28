<?php

namespace Byl\Laravel\Endpoints;

use Byl\Laravel\BylClient;

abstract class Endpoint
{
    public function __construct(protected BylClient $client) {}
}
