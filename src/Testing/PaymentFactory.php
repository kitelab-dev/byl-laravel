<?php

namespace Byl\Laravel\Testing;

use Illuminate\Support\Carbon;

/**
 * Тестэд зориулсан төлбөрийн payload. Бүтэц нь Byl-ийн
 * `payment.awaiting_verification` / `payment.verification_due` webhook-ийн
 * `data.object`-той ижил — дүн нь string, огноо нь ISO хэлбэртэй, төлбөр
 * хамаарах checkout/нэхэмжлэх нь `payable` объектоор ирдэг.
 */
class PaymentFactory
{
    /**
     * Анхдагчаар банкны шилжүүлгийн хүлээгдэж буй (`pending`) төлбөр.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function make(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        return array_merge([
            'id' => 4021,
            'project_id' => 1,
            'status' => 'pending',
            'driver' => 'bank_transfer',
            'amount' => '150000.000000000000',
            'description' => 'Захиалга #1024',
            'reference' => '482913',
            'bank_name' => 'Хаан банк',
            'account_number' => '5001234567',
            'claimed_at' => null,
            'expires_at' => Carbon::now()->addDays(3)->toJSON(),
            'customer_email' => null,
            'phone_number' => null,
            'is_test' => false,
            'created_at' => Carbon::now()->toJSON(),
            'payable' => [
                'type' => 'checkout',
                'id' => 13338,
                'url' => rtrim($baseUrl, '/').'/h/checkout/13338/Yi7smBuk',
                'status' => 'pending',
            ],
        ], $attributes);
    }

    /**
     * Харилцагч шилжүүлэг хийснээ мэдэгдсэн — merchant баталгаажуулахыг хүлээж байна.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function awaitingVerification(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        return self::make(array_merge([
            'status' => 'pending',
            'claimed_at' => Carbon::now()->toJSON(),
            'customer_email' => 'customer@example.mn',
        ], $attributes), $baseUrl);
    }

    /**
     * Merchant баталгаажуулсан — төлбөр төлөгдсөн.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function paid(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        return self::awaitingVerification(array_merge([
            'status' => 'paid',
            'payable' => [
                'type' => 'checkout',
                'id' => 13338,
                'url' => rtrim($baseUrl, '/').'/h/checkout/13338/Yi7smBuk',
                'status' => 'complete',
            ],
        ], $attributes), $baseUrl);
    }

    /**
     * Нэхэмжлэхэд хамаарах төлбөр — `payable` дээр дугаар нэмэгдэнэ.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function forInvoice(array $attributes = [], string $baseUrl = 'https://byl.mn'): array
    {
        return self::make(array_merge([
            'payable' => [
                'type' => 'invoice',
                'id' => 71,
                'url' => rtrim($baseUrl, '/').'/h/invoice/71',
                'status' => 'open',
                'number' => 'INV-0071',
            ],
        ], $attributes), $baseUrl);
    }
}
