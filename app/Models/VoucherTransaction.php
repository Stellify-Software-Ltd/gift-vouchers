<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherTransaction extends Model
{
    public const TYPE_ISSUE = 'issue';

    public const TYPE_REDEEM = 'redeem';

    public const TYPE_VOID_ADJUSTMENT = 'void_adjustment';

    protected $fillable = [
        'voucher_id',
        'type',
        'amount_pence',
        'staff_name',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount_pence' => 'integer',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
