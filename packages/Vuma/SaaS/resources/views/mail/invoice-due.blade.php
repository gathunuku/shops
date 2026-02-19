<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Invoice Due – VumaShops</title></head>
<body style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px;color:#333">

    <h2 style="color:#e44;">Your VumaShops invoice is due</h2>

    <p>Hi {{ $tenant->name ?? 'Store Owner' }},</p>

    <p>Your subscription invoice is due for payment. Please complete payment to keep your store active.</p>

    <table style="width:100%;border-collapse:collapse;margin:20px 0">
        <tr>
            <td style="padding:8px;border:1px solid #ddd"><strong>Invoice #</strong></td>
            <td style="padding:8px;border:1px solid #ddd">{{ $invoice->id }}</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd"><strong>Amount Due</strong></td>
            <td style="padding:8px;border:1px solid #ddd">{{ $invoice->currency }} {{ $invoice->amount_formatted }}</td>
        </tr>
        <tr>
            <td style="padding:8px;border:1px solid #ddd"><strong>Due Date</strong></td>
            <td style="padding:8px;border:1px solid #ddd">{{ $invoice->due_at?->format('d M Y') ?? 'Immediately' }}</td>
        </tr>
    </table>

    <p>
        <a href="{{ url('/billing/plans') }}" style="background:#007bff;color:#fff;padding:12px 24px;text-decoration:none;border-radius:4px;display:inline-block">
            Pay Now
        </a>
    </p>

    <p style="color:#999;font-size:12px">
        If you have already paid, please disregard this email. Your store will remain active for
        {{ config('saas.grace_days', 7) }} days after the due date before being suspended.
    </p>

    <p style="color:#999;font-size:12px">— The VumaShops Team</p>
</body>
</html>
