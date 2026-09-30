<?php

use App\Services\VoucherService;

it('sums live vouchers only and shows expired-unredeemed value separately', function () {
    $service = app(VoucherService::class);

    // Live: £50 untouched + £100 with £35.50 spent = £114.50 owed.
    issueVoucher(['value_pence' => 5000, 'purchaser_name' => 'Live Lena']);
    $partial = issueVoucher(['value_pence' => 10000, 'purchaser_name' => 'Partial Pete']);
    $service->redeem($partial, 3550, 'JD');

    // Fully redeemed: no liability, appears in neither total.
    $spent = issueVoucher(['value_pence' => 3000, 'purchaser_name' => 'Spent Sam']);
    $service->redeem($spent, 3000, 'JD');

    // Void: no liability.
    $void = issueVoucher(['value_pence' => 4000, 'purchaser_name' => 'Void Vera']);
    $service->void($void, 'Refunded', 'JD');

    // Expired with £55 left: reported separately, not in the live total.
    $expired = issueVoucher(['value_pence' => 7500, 'purchaser_name' => 'Expired Ed']);
    $service->redeem($expired, 2000, 'JD');
    $expired->update(['expires_on' => now()->subDays(3)->format('Y-m-d')]);

    $report = $service->liability();

    expect($report['live_total_pence'])->toBe(11450)
        ->and($report['expired_total_pence'])->toBe(5500)
        ->and($report['live']->pluck('purchaser_name')->all())->toBe(['Live Lena', 'Partial Pete'])
        ->and($report['expired']->pluck('purchaser_name')->all())->toBe(['Expired Ed']);

    // And the page shows the same numbers, with a per-voucher breakdown.
    operator()->get('/liability')
        ->assertOk()
        ->assertSee('114.50')
        ->assertSee('55.00')
        ->assertSee('Live Lena')
        ->assertSee('Partial Pete')
        ->assertSee('Expired Ed')
        ->assertDontSee('Void Vera')
        ->assertDontSee('Spent Sam');
});

it('reports zeroes cleanly when nothing is outstanding', function () {
    operator()->get('/liability')
        ->assertOk()
        ->assertSee('0.00')
        ->assertSee('Nothing outstanding');
});
