<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIXI Peering charges Invoice</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <h1 style="color: #ffffff; margin: 0; font-size: 24px;">NIXI</h1>
        <p style="color: #f0f0f0; margin: 5px 0 0 0; font-size: 14px;">Empowering Netizens</p>
    </div>
    
    <div style="background: #ffffff; padding: 30px; border: 1px solid #e0e0e0; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="margin: 0 0 20px 0;"><strong>Subject: NIXI Peering charges Invoice of {{ $ispName ?: $userName }}</strong></p>
        
        <p style="margin: 0 0 20px 0;">Greetings from NIXI,</p>
        
        <p style="margin: 0 0 20px 0;">Dear {{ $authorizedPersonName ?: $userName }},</p>
        
        <p style="margin: 0 0 20px 0;">Please find attached, the peering charges Invoice from {{ $billingStartDate ? \Carbon\Carbon::parse($billingStartDate)->format('d/m/Y') : 'DD/MM/YYYY' }} to {{ $billingEndDate ? \Carbon\Carbon::parse($billingEndDate)->format('d/m/Y') : 'DD/MM/YYYY' }}</p>
        
        <p style="margin: 0 0 20px 0;">For any assistance related to invoices & payments, you may please get in touch with our account team through our online portal or mail on <a href="mailto:accounts@nixi.in" style="color: #667eea;">accounts@nixi.in</a> || <a href="mailto:shashank@nixi.in" style="color: #667eea;">shashank@nixi.in</a>.</p>
        
        <p style="margin: 20px 0 10px 0;"><strong>How to pay</strong></p>
        <p style="margin: 0 0 5px 0;">----------</p>
        <p style="margin: 0 0 20px 0;">The payment methods we accept are detailed on the invoice. For NEFT processing, please include the invoice number and your account name in the remittance advice.</p>
        <p style="margin: 0 0 20px 0;">Exchange now supports online debit/credit card payments. If you would like to pay online, please go to:</p>
        <p style="margin: 0 0 20px 0;"><a href="https://interlinxpartnering.com/nixi/public/user/invoices" style="color: #667eea;">https://interlinxpartnering.com/nixi/public/user/invoices</a> and login here with your credentials.</p>
        
        <p style="margin: 20px 0 20px 0;"><strong>NOTE: NO PART PAYMENTS WILL BE ACCEPTED. AFTER DUE DATE LATE PAYMENT CHARGES WILL BE APPLICABLE.</strong></p>
        
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

