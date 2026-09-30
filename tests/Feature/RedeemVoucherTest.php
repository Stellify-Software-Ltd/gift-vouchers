<?php

use App\Services\VoucherService;
use Illuminate\Validation\ValidationException;

it('deducts a partial redemption and leaves the remainder on the voucher', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);

    operator()->post("/vouchers/{$voucher->id}/redeem", [
        'amount' => '17.50',
        'staff_name' => 'MB',
        'note' => 'Lunch',
    ])->assertRedirect("/vouchers/{$voucher->id}");

    $voucher->refresh()->load('transactions');

    expect($voucher->balancePence())->toBe(3250)
        ->and($voucher->status())->toBe('partially redeemed');

    $redeem = $voucher->transactions->last();
    expect($redeem->type)->toBe('redeem')
        ->and($redeem->amount_pence)->toBe(-1750)
        ->and($redeem->staff_name)->toBe('MB')
        ->and($redeem->note)->toBe('Lunch');

    // Spending the rest flips the derived status to redeemed.
    operator()->post("/vouchers/{$voucher->id}/redeem", [
        'amount' => '32.50',
        'staff_name' => 'MB',
    ])->assertRedirect();

    $voucher->refresh()->load('transactions');
    expect($voucher->balancePence())->toBe(0)
        ->and($voucher->status())->toBe('redeemed');
});

it('rejects over-redemption server-side with a 422', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);

    operator()->postJson("/vouchers/{$voucher->id}/redeem", [
        'amount' => '50.01',
        'staff_name' => 'MB',
    ])->assertStatus(422)->assertJsonValidationErrors('amount');

    // The ledger is untouched.
    expect($voucher->refresh()->balancePence())->toBe(5000)
        ->and($voucher->transactions()->count())->toBe(1);
});

it('rejects redemption against an expired voucher', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);
    $voucher->update(['expires_on' => now()->subDay()->format('Y-m-d')]);

    operator()->postJson("/vouchers/{$voucher->id}/redeem", [
        'amount' => '10.00',
        'staff_name' => 'MB',
    ])->assertStatus(422)->assertJsonValidationErrors('amount');

    expect($voucher->refresh()->balancePence())->toBe(5000)
        ->and($voucher->status())->toBe('expired');
});

it('still honours a voucher on its expiry date itself', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);
    $voucher->update(['expires_on' => now()->format('Y-m-d')]);

    operator()->post("/vouchers/{$voucher->id}/redeem", [
        'amount' => '10.00',
        'staff_name' => 'MB',
    ])->assertRedirect();

    expect($voucher->refresh()->balancePence())->toBe(4000);
});

it('rejects redemption against a voided voucher', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);
    app(VoucherService::class)->void($voucher, 'Refunded in full', 'JD');

    operator()->postJson("/vouchers/{$voucher->id}/redeem", [
        'amount' => '10.00',
        'staff_name' => 'MB',
    ])->assertStatus(422)->assertJsonValidationErrors('amount');

    expect($voucher->refresh()->balancePence())->toBe(0);
});

it('rejects zero and negative amounts', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);

    operator()->postJson("/vouchers/{$voucher->id}/redeem", [
        'amount' => '0',
        'staff_name' => 'MB',
    ])->assertStatus(422);

    operator()->postJson("/vouchers/{$voucher->id}/redeem", [
        'amount' => '-5.00',
        'staff_name' => 'MB',
    ])->assertStatus(422);

    expect($voucher->refresh()->balancePence())->toBe(5000);
});

it('prevents a double-spend: the balance is recomputed under lock from the ledger, never trusted from the screen', function () {
    $voucher = issueVoucher(['value_pence' => 5000]);
    $service = app(VoucherService::class);

    // Two tills both looked at the screen and saw £50 available. Each tries
    // to take £30. The service locks the voucher row and re-reads the ledger
    // inside the transaction, so the second attempt sees £20, not £50.
    $staleCopyA = App\Models\Voucher::find($voucher->id);
    $staleCopyB = App\Models\Voucher::find($voucher->id);

    $service->redeem($staleCopyA, 3000, 'Till 1');

    expect(fn () => $service->redeem($staleCopyB, 3000, 'Till 2'))
        ->toThrow(ValidationException::class);

    // Exactly one redemption landed; the ledger never went negative.
    $voucher->refresh()->load('transactions');
    expect($voucher->balancePence())->toBe(2000)
        ->and($voucher->transactions()->where('type', 'redeem')->count())->toBe(1);
});

it('looks a code up case-insensitively from the redeem screen', function () {
    $voucher = issueVoucher();

    operator()->get('/redeem?code='.strtolower($voucher->code))
        ->assertRedirect("/vouchers/{$voucher->id}");

    operator()->get('/redeem?code='.$voucher->code)
        ->assertRedirect("/vouchers/{$voucher->id}");

    operator()->get('/redeem?code=GV-ZZZZ-ZZZZ-ZZZZ')
        ->assertOk()
        ->assertSee('No voucher with the code');
});
