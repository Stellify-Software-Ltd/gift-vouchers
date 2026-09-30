<?php

use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Act as a signed-in operator (the desk uses a session flag, set by the
 * operator-key sign-in).
 */
function operator(): TestCase
{
    return test()->withSession(['operator_authenticated' => true]);
}

/**
 * Issue a voucher fixture through the real service, so every voucher in the
 * tests has a proper issue ledger row.
 */
function issueVoucher(array $overrides = []): Voucher
{
    return app(VoucherService::class)->issue(array_merge([
        'value_pence' => 5000,
        'purchaser_name' => 'Pat Purchaser',
        'purchaser_email' => 'pat@example.com',
        'recipient_name' => 'Ren Recipient',
        'recipient_email' => 'ren@example.com',
        'expires_on' => now()->addYear()->format('Y-m-d'),
        'staff_name' => 'TT',
    ], $overrides));
}
