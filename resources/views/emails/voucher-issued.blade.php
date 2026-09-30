<!doctype html>
<html lang="en">
<body style="font-family:ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;color:#0a0a0a;background:#fafafa;padding:24px">
<div style="max-width:480px;margin:0 auto;background:#fff;border:1px solid #e5e5e5;border-radius:12px;padding:24px">
    <p style="font-size:13px;color:#737373;margin:0 0 4px">{{ config('app.name') }}</p>
    <h1 style="font-size:20px;margin:0 0 16px">A gift voucher for {{ $voucher->recipient_name ?: 'you' }}</h1>

    <p style="font-size:28px;font-weight:700;margin:0 0 4px">&pound;{{ number_format($voucher->initial_value_pence / 100, 2) }}</p>
    <p style="font-size:16px;letter-spacing:1px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;background:#f5f5f5;border-radius:8px;padding:10px 14px;display:inline-block;margin:0 0 16px">{{ $voucher->code }}</p>

    @if ($voucher->message)
        <p style="font-size:15px;font-style:italic;border-left:3px solid #e5e5e5;padding-left:12px;margin:0 0 16px">&ldquo;{{ $voucher->message }}&rdquo;</p>
    @endif

    <p style="font-size:14px;color:#737373;margin:0 0 4px">From {{ $voucher->purchaser_name }}.</p>
    <p style="font-size:14px;color:#737373;margin:0">Show this code in the shop to spend it. Valid until {{ $voucher->expires_on->format('j F Y') }}.</p>
</div>
</body>
</html>
