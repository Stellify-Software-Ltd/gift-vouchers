@extends('layout')

@section('title', 'Voucher register')

@section('content')
<div class="topbar">
    <div>
        <h1>Voucher register</h1>
        <p class="sub">Everything issued, with the status worked out from the ledger.</p>
    </div>
    <a class="btn" href="{{ route('vouchers.create') }}">Issue a voucher</a>
</div>

<div class="card">
    <form method="get" action="{{ route('vouchers.index') }}">
        <label class="lbl" for="q">Search by code, purchaser or recipient</label>
        <div style="display:flex;gap:8px">
            <input class="inp" type="text" id="q" name="q" value="{{ $search }}" placeholder="GV-…, a name or an email">
            <button class="btn ghost" type="submit">Search</button>
        </div>
    </form>
</div>

<div class="card">
    @if ($vouchers->isEmpty())
        <p class="muted">@if ($search !== '') Nothing matches "{{ $search }}". @else No vouchers issued yet. @endif</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Purchaser</th>
                <th>Recipient</th>
                <th class="num">Value</th>
                <th class="num">Balance</th>
                <th>Expires</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($vouchers as $voucher)
                <tr>
                    <td><a class="code" href="{{ route('vouchers.show', $voucher) }}">{{ $voucher->code }}</a></td>
                    <td>{{ $voucher->purchaser_name }}</td>
                    <td>{{ $voucher->recipient_name ?: '—' }}</td>
                    <td class="num">&pound;{{ number_format($voucher->initial_value_pence / 100, 2) }}</td>
                    <td class="num">&pound;{{ number_format($voucher->balancePence() / 100, 2) }}</td>
                    <td>{{ $voucher->expires_on->format('j M Y') }}</td>
                    <td><span class="tag {{ $voucher->statusClass() }}">{{ $voucher->status() }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div style="margin-top:12px">{{ $vouchers->links() }}</div>
    @endif
</div>
@endsection
