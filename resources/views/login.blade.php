@extends('layout')

@section('title', 'Sign in')

@section('content')
<h1>Gift vouchers</h1>
<p class="sub">Sign in with the operator key to open the desk.</p>

<div class="card" style="max-width:420px">
    <form method="post" action="/login">
        @csrf
        <label class="lbl" for="key">Operator key</label>
        <input class="inp" type="password" id="key" name="key" required autofocus autocomplete="current-password">
        <button class="btn" type="submit" style="width:100%;margin-top:14px">Sign in</button>
    </form>
    <p class="muted" style="margin-top:10px">The operator key is the <span class="code">OPERATOR_KEY</span> value in the app's <span class="code">.env</span> file.</p>
</div>
@endsection
