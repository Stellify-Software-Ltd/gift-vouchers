<?php

it('voids a voucher with a ledger row that zeroes the remaining balance', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);

    operator()->post("/vouchers/{$voucher->id}/redeem", [
        'amount' => '12.00',
        'staff_name' => 'JD',
    ])->assertRedirect();

    operator()->post("/vouchers/{$voucher->id}/void", [
        'reason' => 'Customer refunded',
        'staff_name' => 'JD',
    ])->assertRedirect("/vouchers/{$voucher->id}");

    $voucher->refresh()->load('transactions');

    expect($voucher->status())->toBe('void')
        ->and($voucher->balancePence())->toBe(0)
        ->and($voucher->void_reason)->toBe('Customer refunded')
        ->and($voucher->voided_at)->not->toBeNull();

    // The void is itself a ledger row for exactly the remaining £38.00 —
    // the ledger still sums to the truth.
    $adjustment = $voucher->transactions->last();
    expect($adjustment->type)->toBe('void_adjustment')
        ->and($adjustment->amount_pence)->toBe(-3800)
        ->and($adjustment->note)->toContain('Customer refunded');
});

it('blocks any further redemption after a void', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);

    operator()->post("/vouchers/{$voucher->id}/void", [
        'reason' => 'Issued in error',
        'staff_name' => 'JD',
    ])->assertRedirect();

    operator()->postJson("/vouchers/{$voucher->id}/redeem", [
        'amount' => '5.00',
        'staff_name' => 'MB',
    ])->assertStatus(422);

    expect($voucher->refresh()->balancePence())->toBe(0);
});

it('cannot void twice', function () {
    $voucher = issueVoucher();

    operator()->post("/vouchers/{$voucher->id}/void", [
        'reason' => 'First void',
        'staff_name' => 'JD',
    ])->assertRedirect();

    operator()->postJson("/vouchers/{$voucher->id}/void", [
        'reason' => 'Second void',
        'staff_name' => 'JD',
    ])->assertStatus(422);

    $voucher->refresh();
    expect($voucher->void_reason)->toBe('First void')
        ->and($voucher->transactions()->where('type', 'void_adjustment')->count())->toBe(1);
});

it('requires a reason and a staff name to void', function () {
    $voucher = issueVoucher();

    operator()->post("/vouchers/{$voucher->id}/void", [])
        ->assertSessionHasErrors(['reason', 'staff_name']);

    expect($voucher->refresh()->status())->toBe('active');
});
