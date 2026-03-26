<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $noteType === 'credit' ? 'Credit' : 'Debit' }} Note - {{ $noteNumber }}</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50;">{{ $noteType === 'credit' ? 'Credit' : 'Debit' }} Note Generated</h2>
        
        <p>Dear {{ $userName }},</p>
        
        <p>This is to inform you that a <strong>{{ $noteType === 'credit' ? 'Credit' : 'Debit' }} Note</strong> has been generated for your plan change request for application <strong>{{ $applicationId }}</strong>.</p>
        
        <div style="background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0;">
            <p style="margin: 0;"><strong>{{ $noteType === 'credit' ? 'Credit' : 'Debit' }} Note Number:</strong> {{ $noteNumber }}</p>
            <p style="margin: 5px 0 0 0;"><strong>Amount:</strong> ₹{{ number_format($amount, 2) }}</p>
        </div>
        
        <p>The {{ $noteType === 'credit' ? 'credit' : 'debit' }} note PDF is attached to this email for your records.</p>
        
        @if($noteType === 'credit')
        <p>This credit amount will be applied to your next invoice.</p>
        @else
        <p>This additional amount will be charged in your next invoice.</p>
        @endif
        
        <p>If you have any questions, please contact our support team.</p>
        
        <p style="margin-top: 30px;">
            Best regards,<br>
            <strong>NIXI Team</strong>
        </p>
    </div>
</body>
</html>

