<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;

/**
 * Тестэд зориулсан нэхэмжлэхийн бодит бүтэцтэй payload.
 */
class InvoiceFactory
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function make(array $attributes = []): array
    {
        $id = $attributes['id'] ?? 5708;

        return array_merge([
            'id' => $id,
            'status' => ($attributes['auto_advance'] ?? true) ? 'open' : 'draft',
            'amount' => 10,
            'description' => null,
            'customer_id' => null,
            'number' => 'TEST-'.str_pad((string) $id, 4, '0', STR_PAD_LEFT),
            'project_id' => 1,
            'url' => 'https://byl.mn/h/invoice/'.$id.'/XN3GbRBxTslkMCeDj10CJtqlHiPfcmZ8',
            'due_date' => Carbon::now()->addDay()->toJSON(),
            'created_at' => Carbon::now()->toJSON(),
            'updated_at' => Carbon::now()->toJSON(),
        ], array_intersect_key($attributes, array_flip([
            'id', 'status', 'amount', 'description', 'customer_id', 'number',
            'project_id', 'url', 'due_date', 'created_at', 'updated_at',
        ])));
    }
}
