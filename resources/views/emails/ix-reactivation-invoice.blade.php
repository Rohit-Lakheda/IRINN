<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIXI Reactivation Charges Invoice</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">NIXI</h1>
        <p style="color: #f0f0f0; margin: 5px 0 0 0; font-size: 14px;">Empowering Netizens</p>
    </div>

    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="margin: 0 0 20px 0;"><strong>Subject: NIXI Reactivation Charges Invoice</strong></p>

        <p style="margin: 0 0 20px 0;">Greetings from NIXI,</p>

        <p style="margin: 0 0 20px 0;">Dear {{ $userName }},</p>

        <p style="margin: 0 0 20px 0;">Your reactivation request for IX application <strong>{{ $applicationId }}</strong> has been approved. Please find attached the reactivation invoice.</p>

        <p style="margin: 0 0 20px 0;">
            <strong>Invoice Number:</strong> {{ $invoiceNumber }}<br>
            <strong>Total Amount:</strong> ₹{{ number_format($totalAmount, 2) }}
        </p>

        <p style="margin: 0 0 20px 0;">
            To pay online, please login and go to:
            <a href="{{ url(route('user.invoices.index', [], false)) }}" style="color: #667eea;">{{ url(route('user.invoices.index', [], false)) }}</a>
        </p>

        <p style="margin-top: 30px;">
            Thanks and Regards.
        </p>
    </div>

    <div style="text-align: center; margin-top: 20px; color: #666; font-size: 12px;">
        <p>This is an automated email. Please do not reply to this message.</p>
        <p>&copy; {{ date('Y') }} National Internet Exchange of India. All rights reserved.</p>
    </div>
</body>
</html>

