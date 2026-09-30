<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Gift voucher {{ $voucher->code }}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#0a0a0a;background:#fff;display:flex;justify-content:center;padding:40px 16px}
.voucher{max-width:520px;width:100%;border:2px solid #0a0a0a;border-radius:16px;padding:36px;text-align:center}
.biz{font-size:13px;text-transform:uppercase;letter-spacing:.12em;color:#737373;margin-bottom:18px}
h1{font-size:18px;font-weight:600;margin-bottom:22px}
.value{font-size:44px;font-weight:700;margin-bottom:14px}
.code{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:20px;letter-spacing:2px;border:1px dashed #a3a3a3;border-radius:10px;padding:12px 18px;display:inline-block;margin-bottom:22px}
.msg{font-style:italic;font-size:15px;margin-bottom:22px;color:#404040}
.meta{font-size:13px;color:#737373;line-height:1.7}
.actions{text-align:center;margin-top:20px}
.actions button{background:#0a0a0a;color:#fff;border:0;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer}
@media print{.actions{display:none}body{padding:0}}
</style>
</head>
<body>
<div>
    <div class="voucher">
        <p class="biz">{{ config('app.name') }}</p>
        <h1>Gift voucher{{ $voucher->recipient_name ? ' for '.$voucher->recipient_name : '' }}</h1>
        <p class="value">&pound;{{ number_format($voucher->initial_value_pence / 100, 2) }}</p>
        <p class="code">{{ $voucher->code }}</p>
        @if ($voucher->message)
            <p class="msg">&ldquo;{{ $voucher->message }}&rdquo;</p>
        @endif
        <p class="meta">
            From {{ $voucher->purchaser_name }}<br>
            Valid until {{ $voucher->expires_on->format('j F Y') }}<br>
            Show this code in the shop to spend it — it doesn't have to be spent all at once.
        </p>
    </div>
    <div class="actions"><button onclick="window.print()">Print</button></div>
</div>
</body>
</html>
