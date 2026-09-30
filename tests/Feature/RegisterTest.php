<?php

use App\Services\VoucherService;

it('lists every voucher with its derived status', function () {
    $service = app(VoucherService::class);

    $active = issueVoucher(['purchaser_name' => 'Active Anna']);
    $partial = issueVoucher(['purchaser_name' => 'Partial Pete', 'value_pence' => 10000]);
    $service->redeem($partial, 2500, 'JD');
    $void = issueVoucher(['purchaser_name' => 'Void Vera']);
    $service->void($void, 'Mistake', 'JD');
    $expired = issueVoucher(['purchaser_name' => 'Expired Ed']);
    $expired->update(['expires_on' => now()->subWeek()->format('Y-m-d')]);

    operator()->get('/vouchers')
        ->assertOk()
        ->assertSeeInOrder(['Expired Ed', 'Void Vera', 'Partial Pete', 'Active Anna'])
        ->assertSee('active')
        ->assertSee('partially redeemed')
        ->assertSee('void')
        ->assertSee('expired')
        ->assertSee($active->code);
});

it('searches the register by code, case-insensitively', function () {
    $wanted = issueVoucher(['purchaser_name' => 'Wanted Wendy']);
    issueVoucher(['purchaser_name' => 'Other Ollie']);

    operator()->get('/vouchers?q='.strtolower($wanted->code))
        ->assertOk()
        ->assertSee('Wanted Wendy')
        ->assertDontSee('Other Ollie');
});

it('searches by purchaser and recipient name or email', function () {
    issueVoucher([
        'purchaser_name' => 'Sarah Whitfield',
        'purchaser_email' => 'sarah@example.com',
        'recipient_name' => 'Emma Gifted',
        'recipient_email' => 'emma.gifted@example.com',
    ]);
    issueVoucher(['purchaser_name' => 'Unrelated Uri', 'purchaser_email' => 'uri@example.com', 'recipient_name' => null, 'recipient_email' => null]);

    // Purchaser name.
    operator()->get('/vouchers?q=whitfield')->assertOk()->assertSee('Sarah Whitfield')->assertDontSee('Unrelated Uri');

    // Purchaser email.
    operator()->get('/vouchers?q=sarah@example.com')->assertOk()->assertSee('Sarah Whitfield')->assertDontSee('Unrelated Uri');

    // Recipient name.
    operator()->get('/vouchers?q=Emma')->assertOk()->assertSee('Emma Gifted')->assertDontSee('Unrelated Uri');

    // Recipient email.
    operator()->get('/vouchers?q=emma.gifted')->assertOk()->assertSee('Emma Gifted')->assertDontSee('Unrelated Uri');

    // No match says so.
    operator()->get('/vouchers?q=zzzznope')->assertOk()->assertSee('Nothing matches');
});
