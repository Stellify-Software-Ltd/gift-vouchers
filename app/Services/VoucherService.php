<?php

namespace App\Services;

use App\Models\Voucher;
use App\Models\VoucherTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoucherService
{
    /**
     * Unambiguous alphabet: no 0/O or 1/I, so a code read over the phone or
     * from a crumpled printout can't be mistyped into someone else's voucher.
     */
    public const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Issue a voucher: the business was already paid elsewhere (card machine,
     * cash, their own site) — this records the stored value it now owes.
     *
     * @param array{
     *     value_pence: int,
     *     purchaser_name: string,
     *     purchaser_email?: ?string,
     *     recipient_name?: ?string,
     *     recipient_email?: ?string,
     *     message?: ?string,
     *     expires_on: string,
     *     staff_name: string,
     * } $data
     */
    public function issue(array $data): Voucher
    {
        return DB::transaction(function () use ($data) {
            $voucher = Voucher::create([
                'code' => $this->generateUniqueCode(),
                'initial_value_pence' => $data['value_pence'],
                'purchaser_name' => $data['purchaser_name'],
                'purchaser_email' => $data['purchaser_email'] ?? null,
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_email' => $data['recipient_email'] ?? null,
                'message' => $data['message'] ?? null,
                'expires_on' => $data['expires_on'],
            ]);

            $voucher->transactions()->create([
                'type' => VoucherTransaction::TYPE_ISSUE,
                'amount_pence' => $data['value_pence'],
                'staff_name' => $data['staff_name'],
                'note' => 'Voucher issued',
            ]);

            return $voucher;
        });
    }

    /**
     * Deduct an amount spent in the shop. The voucher row is locked and the
     * balance recomputed from the ledger inside the transaction, so two tills
     * redeeming the same voucher at once cannot both succeed — the second
     * sees the ledger the first one wrote.
     */
    public function redeem(Voucher $voucher, int $amountPence, string $staffName, ?string $note = null): VoucherTransaction
    {
        if ($amountPence <= 0) {
            throw ValidationException::withMessages(['amount' => 'The amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($voucher, $amountPence, $staffName, $note) {
            $locked = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($locked->isVoid()) {
                throw ValidationException::withMessages(['amount' => 'This voucher has been voided and cannot be redeemed.']);
            }

            if ($locked->isExpired()) {
                throw ValidationException::withMessages(['amount' => 'This voucher expired on '.$locked->expires_on->format('j M Y').' and cannot be redeemed.']);
            }

            $balance = $locked->balancePence();

            if ($amountPence > $balance) {
                throw ValidationException::withMessages([
                    'amount' => sprintf('Only £%s is left on this voucher — it cannot cover £%s.', number_format($balance / 100, 2), number_format($amountPence / 100, 2)),
                ]);
            }

            return $locked->transactions()->create([
                'type' => VoucherTransaction::TYPE_REDEEM,
                'amount_pence' => -$amountPence,
                'staff_name' => $staffName,
                'note' => $note,
            ]);
        });
    }

    /**
     * Void a voucher: irreversible. Recorded as a ledger row that zeroes the
     * remaining balance, so the ledger still sums to the truth.
     */
    public function void(Voucher $voucher, string $reason, string $staffName): Voucher
    {
        return DB::transaction(function () use ($voucher, $reason, $staffName) {
            $locked = Voucher::whereKey($voucher->id)->lockForUpdate()->firstOrFail();

            if ($locked->isVoid()) {
                throw ValidationException::withMessages(['reason' => 'This voucher is already void.']);
            }

            $locked->transactions()->create([
                'type' => VoucherTransaction::TYPE_VOID_ADJUSTMENT,
                'amount_pence' => -$locked->balancePence(),
                'staff_name' => $staffName,
                'note' => 'Voided: '.$reason,
            ]);

            $locked->update([
                'voided_at' => now(),
                'void_reason' => $reason,
            ]);

            return $locked;
        });
    }

    /**
     * The liability report: unredeemed value on live vouchers is money the
     * business OWES. Expired-unredeemed value is shown separately — what to
     * do with it is the accountant's call, not this system's.
     *
     * @return array{
     *     live_total_pence: int,
     *     expired_total_pence: int,
     *     live: \Illuminate\Support\Collection<int, Voucher>,
     *     expired: \Illuminate\Support\Collection<int, Voucher>,
     * }
     */
    public function liability(): array
    {
        $outstanding = Voucher::query()
            ->whereNull('voided_at')
            ->with('transactions')
            ->orderBy('expires_on')
            ->get()
            ->filter(fn (Voucher $voucher) => $voucher->balancePence() > 0);

        [$expired, $live] = $outstanding->partition(fn (Voucher $voucher) => $voucher->isExpired());

        return [
            'live_total_pence' => (int) $live->sum(fn (Voucher $voucher) => $voucher->balancePence()),
            'expired_total_pence' => (int) $expired->sum(fn (Voucher $voucher) => $voucher->balancePence()),
            'live' => $live->values(),
            'expired' => $expired->values(),
        ];
    }

    /**
     * Codes carry money, so they are random (random_int), non-sequential and
     * checked for uniqueness before use. 12 characters from a 32-letter
     * alphabet is 60 bits — unguessable in practice.
     */
    public function generateUniqueCode(): string
    {
        do {
            $code = $this->randomCode();
        } while (Voucher::where('code', $code)->exists());

        return $code;
    }

    private function randomCode(): string
    {
        $groups = [];

        for ($g = 0; $g < 3; $g++) {
            $chunk = '';

            for ($i = 0; $i < 4; $i++) {
                $chunk .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $groups[] = $chunk;
        }

        return 'GV-'.implode('-', $groups);
    }
}
