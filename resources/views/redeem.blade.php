@extends('layout')

@section('title', 'Redeem a voucher')

@section('content')
<h1>Redeem a voucher</h1>
<p class="sub">Type in the code from the printed voucher or email. Case doesn't matter.</p>

<div class="card" style="max-width:480px">
    <form method="get" action="{{ route('redeem') }}">
        <label class="lbl" for="code">Voucher code</label>
        <div style="display:flex;gap:8px">
            <input class="inp code" type="text" id="code" name="code" value="{{ $code }}" placeholder="GV-XXXX-XXXX-XXXX" required autofocus>
            <button class="btn" type="submit">Look up</button>
        </div>
    </form>
    @if ($notFound)
        <div class="err">No voucher with the code "{{ $code }}". Check it against the printout — the letters O and I and the digits 0 and 1 are never used.</div>
    @endif
</div>
@endsection
