<?php

namespace Database\Seeders;

use App\Services\VoucherService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    /**
     * Realistic demo data covering every derived status. All dates are
     * relative to today, so the seed never goes stale.
     */
    public function run(): void
    {
        $vouchers = app(VoucherService::class);

        // Active, untouched.
        $vouchers->issue([
            'value_pence' => 5000,
            'purchaser_name' => 'Sarah Whitfield',
            'purchaser_email' => 'sarah.whitfield@example.com',
            'recipient_name' => 'Emma Whitfield',
            'recipient_email' => 'emma.w@example.com',
            'message' => 'Happy birthday Emma! Treat yourself.',
            'expires_on' => Carbon::now()->addMonths(11)->format('Y-m-d'),
            'staff_name' => 'JD',
        ]);

        $vouchers->issue([
            'value_pence' => 2500,
            'purchaser_name' => 'Tom Barker',
            'purchaser_email' => 'tom.barker@example.com',
            'expires_on' => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'staff_name' => 'JD',
        ]);

        // Partially redeemed.
        $partial = $vouchers->issue([
            'value_pence' => 10000,
            'purchaser_name' => 'Priya Nair',
            'purchaser_email' => 'priya.nair@example.com',
            'recipient_name' => 'Dev Nair',
            'message' => 'Congratulations on the new job!',
            'expires_on' => Carbon::now()->addMonths(9)->format('Y-m-d'),
            'staff_name' => 'MB',
        ]);
        $vouchers->redeem($partial, 3550, 'MB', 'Lunch for two');

        // Fully redeemed across two visits.
        $redeemed = $vouchers->issue([
            'value_pence' => 3000,
            'purchaser_name' => 'Ellen Craig',
            'expires_on' => Carbon::now()->addMonths(4)->format('Y-m-d'),
            'staff_name' => 'JD',
        ]);
        $vouchers->redeem($redeemed, 1800, 'JD');
        $vouchers->redeem($redeemed, 1200, 'MB', 'Used up the remainder');

        // Expired with value left — shows up separately on the liability report.
        $expired = $vouchers->issue([
            'value_pence' => 7500,
            'purchaser_name' => 'Graham Hollis',
            'purchaser_email' => 'g.hollis@example.com',
            'recipient_name' => 'Kate Hollis',
            'expires_on' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'staff_name' => 'JD',
        ]);
        $vouchers->redeem($expired, 2000, 'JD');
        $expired->update(['expires_on' => Carbon::now()->subDays(21)->format('Y-m-d')]);

        // Voided — issued in error.
        $void = $vouchers->issue([
            'value_pence' => 4000,
            'purchaser_name' => 'Marc Devlin',
            'expires_on' => Carbon::now()->addYear()->format('Y-m-d'),
            'staff_name' => 'MB',
        ]);
        $vouchers->void($void, 'Issued at the wrong value — replaced', 'MB');
    }
}
