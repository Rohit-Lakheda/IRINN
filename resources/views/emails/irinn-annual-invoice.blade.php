<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IRINN annual invoice</title>
</head>
<body style="margin:0;padding:24px;font-family:system-ui,-apple-system,sans-serif;line-height:1.5;color:#222;">
    <p>Hello {{ $userName }},</p>
    <p>Your <strong>IRINN annual resource invoice</strong> for financial year <strong>{{ $financialYearLabel }}</strong> has been generated.</p>
    <ul>
        <li><strong>Application ID:</strong> {{ $applicationId }}</li>
        <li><strong>Invoice number:</strong> {{ $invoiceNumber }}</li>
        <li><strong>Total (incl. GST):</strong> ₹{{ number_format($totalAmount, 2) }}</li>
    </ul>
    <p>The invoice PDF is attached. You can also open <strong>My invoices</strong> in the portal to pay online (PayU) or use advance wallet balance where applicable.</p>
    <p style="margin-top:28px;color:#555;font-size:14px;">Regards,<br>NIXI IRINN Billing</p>
</body>
</html>
