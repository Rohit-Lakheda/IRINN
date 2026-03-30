<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Credit note {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 8px 0; }
        .muted { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>National Internet Exchange of India (NIXI)</h1>
    <p class="muted">Credit note — IRINN</p>

    <p><strong>Original invoice:</strong> {{ $invoice->invoice_number }}<br>
    @if (! empty($invoice->credit_note_ack_no))
        <strong>Ack No:</strong> {{ $invoice->credit_note_ack_no }}<br>
    @endif
    @if (! empty($invoice->credit_note_irn))
        <strong>IRN:</strong> {{ $invoice->credit_note_irn }}
    @endif
    </p>

    <p><strong>Bill To</strong><br>
    {{ $user->fullname ?? '—' }}<br>
    {{ $user->email ?? '' }}</p>

    <p>This document is a credit note issued against the invoice listed above.</p>
</body>
</html>
