<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification OTP</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; font-family: 'Nunito', 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif; background-color: #f5f5f5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 30px 20px;">
                <table role="presentation" style="max-width: 600px; width: 100%; background-color: #ffffff; border: 10px solid #2c3e50; border-radius: 4px; overflow: hidden;">
                    <!-- Simple Header -->
                    <tr>
                        <td style="padding: 25px 30px; border-bottom: 2px solid #2c3e50;">
                            <p style="margin: 0; color: #2c3e50; font-size: 22px; font-weight: 600; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">NIXI Application</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">Hello,</p>
                            <p style="margin: 0 0 25px 0; color: #555555; font-size: 15px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">Thank you for registering with NIXI Application. Please use the following One-Time Password (OTP) to verify your email address:</p>
                            
                            <p style="margin: 25px 0; color: #555555; font-size: 15px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                Your OTP is: <strong style="color: #2c3e50; font-size: 18px; background-color: #f0f0f0; padding: 4px 12px; border-radius: 4px; letter-spacing: 3px;">{{ $otp }}</strong>
                            </p>
                            
                            <p style="margin: 25px 0 15px 0; color: #555555; font-size: 14px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                <strong style="color: #2c3e50;">Important:</strong> This OTP is valid for <strong style="color: #2c3e50;">10 minutes</strong> only. Please do not share this OTP with anyone.
                            </p>
                            
                            <div style="background-color: #fff9e6; border-left: 3px solid #f39c12; padding: 15px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.5; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                    <strong>Security Notice:</strong> If you did not request this OTP, please ignore this email or contact our support team immediately.
                                </p>
                            </div>
                            
                            <p style="margin: 35px 0 0 0; color: #555555; font-size: 14px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                <span style="font-family: 'Brush Script MT', 'Lucida Handwriting', cursive; font-size: 20px; color: #2c3e50; font-weight: normal;">Regards,<br>NIXI Team</span>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 25px 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 8px 0; color: #6c757d; font-size: 12px; line-height: 1.5; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                This is an automated email. Please do not reply to this message.
                            </p>
                            <p style="margin: 0; color: #6c757d; font-size: 11px; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                © {{ date('Y') }} NIXI Application. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
