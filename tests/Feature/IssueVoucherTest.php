<?php

use App\Mail\VoucherIssuedMail;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Support\Facades\Mail;

it('issues a voucher with an issue ledger row and a well-formed code', function () {
    operator()->post('/vouchers', [
        'value' => '50.00',
        'purchaser_name' => 'Sarah Whitfield',
        'purchaser_email' => 'sarah@example.com',
        'recipient_name' => 'Emma Whitfield',
        'recipient_email' => 'emma@example.com',
        'message' => 'Happy birthday!',
        'expires_on' => now()->addYear()->format('Y-m-d'),
        'staff_name' => 'JD',
    ])->assertRedirect();

    $voucher = Voucher::sole();

    // Value is stored in integer pence, never floats.
    expect($voucher->initial_value_pence)->toBe(5000)
        ->and($voucher->balancePence())->toBe(5000)
        ->and($voucher->status())->toBe('active');

    // Exactly one ledger row: the issue.
    expect($voucher->transactions)->toHaveCount(1);
    $issue = $voucher->transactions->first();
    expect($issue->type)->toBe('issue')
        ->and($issue->amount_pence)->toBe(5000)
        ->and($issue->staff_name)->toBe('JD');

    // Codes carry money: GV-XXXX-XXXX-XXXX over an unambiguous alphabet
    // (no 0/O or 1/I to misread from a printout).
    expect($voucher->code)->toMatch('/^GV-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/');
});

it('generates non-sequential, unique codes', function () {
    $service = app(VoucherService::class);
    $codes = collect(range(1, 100))->map(fn () => $service->generateUniqueCode());

    expect($codes->unique())->toHaveCount(100);

    foreach ($codes as $code) {
        expect($code)->toMatch('/^GV-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/');
    }
});

it('rejects an issue with no value, a past expiry or no staff name', function () {
    operator()->post('/vouchers', [
        'purchaser_name' => 'Nameless Nora',
        'expires_on' => now()->addYear()->format('Y-m-d'),
        'staff_name' => 'JD',
    ])->assertSessionHasErrors('value');

    operator()->post('/vouchers', [
        'value' => '25.00',
        'purchaser_name' => 'Backdated Bill',
        'expires_on' => now()->subDay()->format('Y-m-d'),
        'staff_name' => 'JD',
    ])->assertSessionHasErrors('expires_on');

    operator()->post('/vouchers', [
        'value' => '25.00',
        'purchaser_name' => 'Anonymous Andy',
        'expires_on' => now()->addYear()->format('Y-m-d'),
    ])->assertSessionHasErrors('staff_name');

    expect(Voucher::count())->toBe(0);
});

it('shows a print view and emails the voucher to the recipient', function () {
    Mail::fake();

    $voucher = issueVoucher(['message' => 'With love']);

    operator()->get("/vouchers/{$voucher->id}/print")
        ->assertOk()
        ->assertSee($voucher->code)
        ->assertSee('With love')
        ->assertSee('50.00');

    operator()->post("/vouchers/{$voucher->id}/email")->assertRedirect();

    Mail::assertSent(VoucherIssuedMail::class, fn (VoucherIssuedMail $mail) => $mail->hasTo('ren@example.com') && $mail->voucher->is($voucher));
});

it('falls back to the purchaser email and refuses when there is no email at all', function () {
    Mail::fake();

    $noRecipient = issueVoucher(['recipient_email' => null]);
    operator()->post("/vouchers/{$noRecipient->id}/email")->assertRedirect();
    Mail::assertSent(VoucherIssuedMail::class, fn (VoucherIssuedMail $mail) => $mail->hasTo('pat@example.com'));

    $noEmails = issueVoucher(['recipient_email' => null, 'purchaser_email' => null]);
    operator()->post("/vouchers/{$noEmails->id}/email")->assertSessionHasErrors('email');
    Mail::assertSentCount(1);
});
