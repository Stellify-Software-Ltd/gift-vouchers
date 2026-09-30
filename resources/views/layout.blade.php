<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Gift vouchers')</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#fafafa;color:#0a0a0a;line-height:1.5}
.wrap{max-width:760px;margin:0 auto;padding:20px 16px 60px}
h1{font-size:22px;font-weight:700;margin:18px 0 4px}
h2{font-size:16px;font-weight:700;margin:0 0 8px}
.sub{color:#737373;font-size:14px;margin-bottom:18px}
.card{background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:16px;margin-bottom:14px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
.lbl{display:block;font-size:13px;font-weight:600;margin:10px 0 4px}
.inp,.sel{width:100%;border:1px solid #d4d4d4;border-radius:8px;padding:10px 12px;font-size:15px;background:#fff}
.inp:focus,.sel:focus{outline:2px solid #0a0a0a;outline-offset:1px}
.btn{display:inline-block;background:#0a0a0a;color:#fff;border:0;border-radius:8px;padding:11px 16px;font-size:15px;font-weight:600;cursor:pointer;text-decoration:none}
.btn.ghost{background:#fff;color:#0a0a0a;border:1px solid #d4d4d4}
.btn.danger{background:#dc2626}
.muted{color:#737373;font-size:13px}
.err{background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#b91c1c;padding:10px 12px;font-size:14px;margin:10px 0}
.notice{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;color:#166534;padding:10px 12px;font-size:14px;margin:10px 0}
.nav{display:flex;gap:14px;align-items:center;font-size:14px;padding:12px 0;border-bottom:1px solid #e5e5e5;flex-wrap:wrap}
.nav a{color:#0a0a0a;text-decoration:none;font-weight:600}
.nav a:hover{text-decoration:underline}
.nav form{margin-left:auto}
.nav .out{border:0;background:none;color:#737373;font-size:13px;cursor:pointer}
table{width:100%;border-collapse:collapse;font-size:14px}
th{text-align:left;font-size:12px;text-transform:uppercase;letter-spacing:.03em;color:#737373;padding:8px 8px;border-bottom:1px solid #e5e5e5}
td{padding:9px 8px;border-bottom:1px solid #f0f0f0;vertical-align:top}
td.num,th.num{text-align:right;font-variant-numeric:tabular-nums}
.code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px;white-space:nowrap}
.tag{font-size:11px;border-radius:999px;padding:2px 8px;white-space:nowrap;border:1px solid}
.tag.active{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
.tag.partial{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.tag.redeemed{background:#f5f5f5;border-color:#e5e5e5;color:#525252}
.tag.expired{background:#fef3c7;border-color:#fde68a;color:#92400e}
.tag.void{background:#fef2f2;border-color:#fecaca;color:#b91c1c}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:0 10px}
@media (max-width:560px){.grid2{grid-template-columns:1fr}}
.big{font-size:26px;font-weight:700}
.topbar{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
</style>
</head>
<body>
<div class="wrap">
    @if (session('operator_authenticated'))
        <nav class="nav">
            <a href="{{ route('vouchers.index') }}">Register</a>
            <a href="{{ route('vouchers.create') }}">Issue</a>
            <a href="{{ route('redeem') }}">Redeem</a>
            <a href="{{ route('liability') }}">Liability</a>
            <form method="post" action="{{ route('logout') }}">
                @csrf
                <button class="out" type="submit">Sign out</button>
            </form>
        </nav>
    @endif

    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="err">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</div>
</body>
</html>
