@extends('layout')

@section('title', $voucher->code)

@section('content')
<div class="topbar">
    <div>
        <h1 class="code" style="font-size:22px">{{ $voucher->code }}</h1>
        <p class="sub"><span class="tag {{ $voucher->statusClass() }}">{{ $voucher->status() }}</span></p>
    </div>
    <div style="display:flex;gap:8px">
        <a class="btn ghost" href="{{ route('vouchers.print', $voucher) }}" target="_blank">Print</a>
        @if ($voucher->recipient_email || $voucher->purchaser_email)
            <form method="post" action="{{ route('vouchers.email', $voucher) }}">
                @csrf
                <button class="btn ghost" type="submit">Email it</button>
            </form>
        @endif
    </div>
</div>

<div class="card">
    <div class="grid2">
        <div>
            <p class="muted">Balance</p>
            <p class="big">&pound;{{ number_format($voucher->balancePence() / 100, 2) }}</p>
            <p class="muted">of &pound;{{ number_format($voucher->initial_value_pence / 100, 2) }} issued {{ $voucher->created_at->format('j M Y') }}</p>
        </div>
        <div>
            <p class="muted">Expires {{ $voucher->expires_on->format('j M Y') }}</p>
            <p style="font-size:14px;margin-top:6px">Purchaser: {{ $voucher->purchaser_name }}@if ($voucher->purchaser_email) &lt;{{ $voucher->purchaser_email }}&gt;@endif</p>
            @if ($voucher->recipient_name || $voucher->recipient_email)
                <p style="font-size:14px">Recipient: {{ $voucher->recipient_name ?: '—' }}@if ($voucher->recipient_email) &lt;{{ $voucher->recipient_email }}&gt;@endif</p>
            @endif
            @if ($voucher->message)
                <p class="muted" style="margin-top:6px;font-style:italic">&ldquo;{{ $voucher->message }}&rdquo;</p>
            @endif
            @if ($voucher->isVoid())
                <p style="font-size:14px;color:#b91c1c;margin-top:6px">Voided {{ $voucher->voided_at->format('j M Y H:i') }} — {{ $voucher->void_reason }}</p>
            @endif
        </div>
    </div>
</div>

@if (! $voucher->isVoid() && ! $voucher->isExpired() && $voucher->balancePence() > 0)
    <div class="card">
        <h2>Redeem</h2>
        <form method="post" action="{{ route('vouchers.redeem', $voucher) }}">
            @csrf
            <div class="grid2">
                <div>
                    <label class="lbl" for="amount">Amount spent (&pound;)</label>
                    <input class="inp" type="number" step="0.01" min="0.01" id="amount" name="amount" required>
                </div>
                <div>
                    <label class="lbl" for="staff_name">Taken by (your name or initials)</label>
                    <input class="inp" type="text" id="staff_name" name="staff_name" required>
                </div>
            </div>
            <label class="lbl" for="note">Note <span class="muted">(optional)</span></label>
            <input class="inp" type="text" id="note" name="note" maxlength="255">
            <button class="btn" type="submit" style="margin-top:14px">Deduct from voucher</button>
        </form>
    </div>
@endif

<div class="card">
    <h2>Ledger</h2>
    <p class="muted" style="margin-bottom:8px">Every movement on this voucher. The balance is the sum of this table — nothing else.</p>
    <table>
        <thead>
        <tr>
            <th>When</th>
            <th>Type</th>
            <th class="num">Amount</th>
            <th>Staff</th>
            <th>Note</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($voucher->transactions as $entry)
            <tr>
                <td>{{ $entry->created_at->format('j M Y H:i') }}</td>
                <td>{{ str_replace('_', ' ', $entry->type) }}</td>
                <td class="num">{{ $entry->amount_pence < 0 ? '−' : '' }}&pound;{{ number_format(abs($entry->amount_pence) / 100, 2) }}</td>
                <td>{{ $entry->staff_name }}</td>
                <td class="muted">{{ $entry->note ?: '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@if (! $voucher->isVoid())
    <div class="card">
        <h2>Void this voucher</h2>
        <p class="muted" style="margin-bottom:8px">Irreversible. Writes a ledger entry that zeroes the remaining balance and blocks all further redemption.</p>
        <form method="post" action="{{ route('vouchers.void', $voucher) }}" onsubmit="return confirm('Void {{ $voucher->code }}? This cannot be undone.');">
            @csrf
            <div class="grid2">
                <div>
                    <label class="lbl" for="reason">Reason</label>
                    <input class="inp" type="text" id="reason" name="reason" required>
                </div>
                <div>
                    <label class="lbl" for="void_staff_name">Voided by (your name or initials)</label>
                    <input class="inp" type="text" id="void_staff_name" name="staff_name" required>
                </div>
            </div>
            <button class="btn danger" type="submit" style="margin-top:14px">Void voucher</button>
        </form>
    </div>
@endif
@endsection
