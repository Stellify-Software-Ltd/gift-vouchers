@extends('layout')

@section('title', 'Liability report')

@section('content')
<h1>Liability report</h1>
<p class="sub">Unredeemed voucher value is money the business owes. This is the page your accountant will ask for.</p>

<div class="grid2">
    <div class="card">
        <p class="muted">Outstanding on live vouchers</p>
        <p class="big">&pound;{{ number_format($live_total_pence / 100, 2) }}</p>
        <p class="muted">{{ count($live) }} {{ count($live) === 1 ? 'voucher' : 'vouchers' }} still redeemable — this is your current liability.</p>
    </div>
    <div class="card">
        <p class="muted">Expired, never fully redeemed</p>
        <p class="big">&pound;{{ number_format($expired_total_pence / 100, 2) }}</p>
        <p class="muted">{{ count($expired) }} {{ count($expired) === 1 ? 'voucher' : 'vouchers' }} past expiry with value left. How to treat this is your accountant's call.</p>
    </div>
</div>

<div class="card">
    <h2>Live vouchers</h2>
    @if ($live->isEmpty())
        <p class="muted">Nothing outstanding.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Purchaser</th>
                <th>Issued</th>
                <th>Expires</th>
                <th class="num">Issued value</th>
                <th class="num">Outstanding</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($live as $voucher)
                <tr>
                    <td><a class="code" href="{{ route('vouchers.show', $voucher) }}">{{ $voucher->code }}</a></td>
                    <td>{{ $voucher->purchaser_name }}</td>
                    <td>{{ $voucher->created_at->format('j M Y') }}</td>
                    <td>{{ $voucher->expires_on->format('j M Y') }}</td>
                    <td class="num">&pound;{{ number_format($voucher->initial_value_pence / 100, 2) }}</td>
                    <td class="num">&pound;{{ number_format($voucher->balancePence() / 100, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="card">
    <h2>Expired with value left</h2>
    @if ($expired->isEmpty())
        <p class="muted">None.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Purchaser</th>
                <th>Issued</th>
                <th>Expired</th>
                <th class="num">Issued value</th>
                <th class="num">Unredeemed</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($expired as $voucher)
                <tr>
                    <td><a class="code" href="{{ route('vouchers.show', $voucher) }}">{{ $voucher->code }}</a></td>
                    <td>{{ $voucher->purchaser_name }}</td>
                    <td>{{ $voucher->created_at->format('j M Y') }}</td>
                    <td>{{ $voucher->expires_on->format('j M Y') }}</td>
                    <td class="num">&pound;{{ number_format($voucher->initial_value_pence / 100, 2) }}</td>
                    <td class="num">&pound;{{ number_format($voucher->balancePence() / 100, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
