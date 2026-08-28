<?php

namespace Byl\Laravel\Data;

use Byl\Laravel\Enums\PaymentStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Нэг төлбөрийн оролдлого. Банкны шилжүүлгийн (`bank_transfer`) үед
 * `payment.awaiting_verification` ба `payment.verification_due` webhook-оор
 * ирнэ — тэр үед `reference` нь харилцагчийн шилжүүлгийн 6 оронтой лавлагаа,
 * бусад driver дээр `transaction_id` байна.
 */
final class Payment extends Data
{
    /**
     * @param  array<string, mixed>|null  $payable  Төлбөр хамаарах checkout/нэхэмжлэх.
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $projectId,
        public readonly ?PaymentStatus $status,
        public readonly ?string $driver,
        public readonly ?float $amount,
        public readonly ?string $description,
        public readonly ?string $reference,
        public readonly ?string $bankName,
        public readonly ?string $accountNumber,
        public readonly ?Carbon $claimedAt,
        public readonly ?Carbon $expiresAt,
        public readonly ?string $customerEmail,
        public readonly ?string $phoneNumber,
        public readonly bool $isTest,
        public readonly ?Carbon $createdAt,
        public readonly ?array $payable,
        array $raw = [],
    ) {
        parent::__construct($raw);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $payable = Arr::get($data, 'payable');

        return new self(
            id: Arr::get($data, 'id') !== null ? (int) Arr::get($data, 'id') : null,
            projectId: Arr::get($data, 'project_id') !== null ? (int) Arr::get($data, 'project_id') : null,
            status: self::enum(PaymentStatus::class, Arr::get($data, 'status')),
            driver: Arr::get($data, 'driver'),
            amount: self::number(Arr::get($data, 'amount')),
            description: Arr::get($data, 'description'),
            // Банкны шилжүүлэг дээр `reference`, бусад driver дээр `transaction_id`.
            reference: Arr::get($data, 'reference') ?? Arr::get($data, 'transaction_id'),
            bankName: Arr::get($data, 'bank_name'),
            accountNumber: Arr::get($data, 'account_number'),
            claimedAt: self::date(Arr::get($data, 'claimed_at')),
            expiresAt: self::date(Arr::get($data, 'expires_at')),
            customerEmail: Arr::get($data, 'customer_email'),
            phoneNumber: Arr::get($data, 'phone_number'),
            isTest: (bool) Arr::get($data, 'is_test', false),
            createdAt: self::date(Arr::get($data, 'created_at')),
            payable: is_array($payable) ? $payable : null,
            raw: $data,
        );
    }

    public function isPaid(): bool
    {
        return $this->status?->isPaid() ?? false;
    }

    /**
     * Merchant баталгаажуулахыг хүлээж буй төлбөр.
     */
    public function isPending(): bool
    {
        return $this->status?->isPending() ?? false;
    }

    public function isBankTransfer(): bool
    {
        return $this->driver === 'bank_transfer';
    }

    /**
     * `checkout` эсвэл `invoice`.
     */
    public function payableType(): ?string
    {
        return Arr::get($this->payable ?? [], 'type');
    }

    public function payableId(): int|string|null
    {
        return Arr::get($this->payable ?? [], 'id');
    }

    public function payableUrl(): ?string
    {
        return Arr::get($this->payable ?? [], 'url');
    }

    public function payableStatus(): ?string
    {
        return Arr::get($this->payable ?? [], 'status');
    }

    /**
     * Нэхэмжлэхийн дугаар — зөвхөн `invoice` төрөл дээр ирнэ.
     */
    public function payableNumber(): ?string
    {
        return Arr::get($this->payable ?? [], 'number');
    }

    public function isForCheckout(): bool
    {
        return $this->payableType() === 'checkout';
    }

    public function isForInvoice(): bool
    {
        return $this->payableType() === 'invoice';
    }
}
