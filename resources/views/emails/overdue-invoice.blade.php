<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Invoice Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">NIXI</h1>
        <p style="color: #f0f0f0; margin: 5px 0 0 0; font-size: 14px;">Empowering Netizens</p>
    </div>

    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="margin: 0 0 20px 0;">Dear {{ $recipientName }},</p>

        <p style="margin: 0 0 20px 0;">This is to inform you that the following invoice is <strong style="color: #dc3545;">overdue</strong>:</p>
        
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <ul style="margin: 0; padding-left: 20px;">
                <li><strong>Application ID:</strong> {{ $application->application_id }}</li>
                <li><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</li>
                <li><strong>Invoice Date:</strong> {{ $invoice->invoice_date->format('d M Y') }}</li>
                <li><strong>Due Date:</strong> {{ $invoice->due_date->format('d M Y') }}</li>
                <li><strong>Amount Due:</strong> &#8377;{{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</li>
                @php
                    $daysOverdue = now('Asia/Kolkata')->diffInDays($invoice->due_date, false);
                    $daysOverdue = abs($daysOverdue);
                @endphp
                <li><strong>Days Overdue:</strong> {{ $daysOverdue }} day(s)</li>
            </ul>
        </div>

        <p style="margin: 0 0 20px 0;">Please make the payment at your earliest convenience to avoid any service interruption. The invoice PDF is attached for your reference.</p>

        <p style="margin: 0 0 20px 0;">You can make the payment through:</p>
        <ul style="margin: 0 0 20px 0;">
            <li>Online payment gateway (PayU) - Login to your portal</li>
            <li>Advance amount (if available in your wallet)</li>
            <li>Bank transfer (contact accounts@nixi.in for details)</li>
        </ul>

        <p style="margin: 0 0 20px 0;">For any queries or assistance, please contact <a href="mailto:accounts@nixi.in" style="color: #667eea;">accounts@nixi.in</a>.</p>

        <p style="margin-top: 30px;">Thanks and Regards,<br>NIXI Team</p>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
        <p>&copy; {{ date('Y') }} National Internet Exchange of India. All rights reserved.</p>
    </div>
</body>
</html>
