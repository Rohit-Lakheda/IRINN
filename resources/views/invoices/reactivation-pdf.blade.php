<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 8px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .right { text-align: right; }
        .muted { color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <h1>National Internet Exchange of India (NIXI)</h1>
    <p class="muted">Tax Invoice — IRINN reactivation charges</p>

    <p><strong>Invoice No:</strong> {{ $invoice->invoice_number }}<br>
    <strong>Date:</strong> {{ $invoice->invoice_date }}<br>
    <strong>Due:</strong> {{ $invoice->due_date }}</p>

    <p><strong>Bill To</strong><br>
    {{ $user->fullname ?? '—' }}<br>
    {{ $user->email ?? '' }}</p>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach (($invoice->line_items ?? []) as $row)
                @if (is_array($row) && isset($row['description']))
                    <tr>
                        <td>{{ $row['description'] }}</td>
                        <td class="right">{{ number_format((float) ($row['amount'] ?? 0), 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <p class="right"><strong>Subtotal:</strong> ₹{{ number_format((float) ($invoice->amount ?? 0), 2) }}<br>
    <strong>GST:</strong> ₹{{ number_format((float) ($invoice->gst_amount ?? 0), 2) }}<br>
    <strong>Total:</strong> ₹{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</p>

    @if (! empty($invoice->einvoice_irn))
        <p class="muted"><strong>IRN:</strong> {{ $invoice->einvoice_irn }}<br>
        @if (! empty($invoice->einvoice_ack_no))
            <strong>Ack No:</strong> {{ $invoice->einvoice_ack_no }}
        @endif
        </p>
    @endif
</body>
</html>
