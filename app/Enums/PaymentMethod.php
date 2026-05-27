<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Cheque = 'cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::MobileMoney => 'Mobile Money (MoMo)',
            self::BankTransfer => 'Bank transfer',
            self::Card => 'Card / Online',
            self::Cheque => 'Cheque',
            self::Other => 'Other',
        };
    }

    /**
     * Short hint shown alongside the field — guides admins on what to capture in the reference field.
     */
    public function referenceHint(): string
    {
        return match ($this) {
            self::Cash => 'Receipt number (optional)',
            self::MobileMoney => 'Network + transaction ID, e.g. MTN MoMo 1234567890',
            self::BankTransfer => 'Bank name + reference / transaction ID',
            self::Card => 'Processor reference (Stripe, Paystack, etc.)',
            self::Cheque => 'Cheque number',
            self::Other => 'Any reference that helps identify the gift',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
            ->all();
    }
}
