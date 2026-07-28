<?php

use Byl\Laravel\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

/**
 * Byl API-ийн хариу шиг бүтэцтэй payload.
 *
 * @param  array<string, mixed>  $data
 * @return array<string, mixed>
 */
function bylResponse(array $data): array
{
    return ['data' => $data];
}
