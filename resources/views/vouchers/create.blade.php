@extends('layout')

@section('title', 'Issue a voucher')

@section('content')
<h1>Issue a voucher</h1>
<p class="sub">Take payment on your card machine, in cash or on your own site first — then record the voucher here.</p>

<div class="card" style="max-width:560px">
    <form method="post" action="{{ route('vouchers.store') }}">
        @csrf

        <label class="lbl" for="value">Value (&pound;)</label>
        <input class="inp" type="number" step="0.01" min="0.01" id="value" name="value" value="{{ old('value') }}" required>

        <div class="grid2">
            <div>
                <label class="lbl" for="purchaser_name">Purchaser name</label>
                <input class="inp" type="text" id="purchaser_name" name="purchaser_name" value="{{ old('purchaser_name') }}" required>
            </div>
            <div>
                <label class="lbl" for="purchaser_email">Purchaser email <span class="muted">(optional)</span></label>
                <input class="inp" type="email" id="purchaser_email" name="purchaser_email" value="{{ old('purchaser_email') }}">
            </div>
            <div>
                <label class="lbl" for="recipient_name">Recipient name <span class="muted">(optional)</span></label>
                <input class="inp" type="text" id="recipient_name" name="recipient_name" value="{{ old('recipient_name') }}">
            </div>
            <div>
                <label class="lbl" for="recipient_email">Recipient email <span class="muted">(optional)</span></label>
                <input class="inp" type="email" id="recipient_email" name="recipient_email" value="{{ old('recipient_email') }}">
            </div>
        </div>

        <label class="lbl" for="message">Personal message <span class="muted">(optional, printed on the voucher)</span></label>
        <input class="inp" type="text" id="message" name="message" maxlength="500" value="{{ old('message') }}">

        <div class="grid2">
            <div>
                <label class="lbl" for="expires_on">Expiry date</label>
                <input class="inp" type="date" id="expires_on" name="expires_on" value="{{ old('expires_on', now()->addYear()->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label class="lbl" for="staff_name">Issued by (your name or initials)</label>
                <input class="inp" type="text" id="staff_name" name="staff_name" value="{{ old('staff_name') }}" required>
            </div>
        </div>

        <button class="btn" type="submit" style="width:100%;margin-top:16px">Issue voucher</button>
    </form>
</div>
@endsection
