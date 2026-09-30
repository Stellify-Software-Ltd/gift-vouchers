<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    protected $fillable = [
        'code',
        'initial_value_pence',
        'purchaser_name',
        'purchaser_email',
        'recipient_name',
        'recipient_email',
        'message',
        'expires_on',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'initial_value_pence' => 'integer',
            'expires_on' => 'immutable_date',
            'voided_at' => 'immutable_datetime',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class)->orderBy('id');
    }

    /**
     * The remaining balance is ALWAYS the sum of the ledger — never a stored
     * column. A stored balance that can drift is worse than no system.
     */
    public function balancePence(): int
    {
        if ($this->relationLoaded('transactions')) {
            return (int) $this->transactions->sum('amount_pence');
        }

        return (int) $this->transactions()->sum('amount_pence');
    }

    public function isVoid(): bool
    {
        return $this->voided_at !== null;
    }

    /**
     * A voucher is usable through its expiry date and expired from the day
     * after.
     */
    public function isExpired(): bool
    {
        return $this->expires_on->lessThan(CarbonImmutable::today());
    }

    /**
     * Status is derived from the ledger balance, the void marker and the
     * expiry date. It is never stored.
     */
    public function status(): string
    {
        if ($this->isVoid()) {
            return 'void';
        }

        $balance = $this->balancePence();

        if ($balance <= 0) {
            return 'redeemed';
        }

        if ($this->isExpired()) {
            return 'expired';
        }

        if ($balance < $this->initial_value_pence) {
            return 'partially redeemed';
        }

        return 'active';
    }

    /**
     * CSS class for the status tag in the desk views.
     */
    public function statusClass(): string
    {
        return match ($this->status()) {
            'void' => 'void',
            'redeemed' => 'redeemed',
            'expired' => 'expired',
            'partially redeemed' => 'partial',
            default => 'active',
        };
    }

    /**
     * Case-insensitive code match (codes are stored uppercase).
     */
    public function scopeWithCode(Builder $query, string $code): Builder
    {
        return $query->where('code', strtoupper(trim($code)));
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        $like = '%'.strtolower(trim($term)).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->whereRaw('lower(code) like ?', [$like])
                ->orWhereRaw('lower(purchaser_name) like ?', [$like])
                ->orWhereRaw('lower(purchaser_email) like ?', [$like])
                ->orWhereRaw('lower(recipient_name) like ?', [$like])
                ->orWhereRaw('lower(recipient_email) like ?', [$like]);
        });
    }
}
