<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
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
                            <p style="margin: 0 0 25px 0; color: #555555; font-size: 15px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">We received a request to reset your password for your NIXI Application account. Click the button below to reset your password:</p>
                            
                            <!-- Reset Button -->
                            <table role="presentation" style="width: 100%; margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $resetUrl }}" style="background-color: #2c3e50; color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600; font-size: 15px; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                            Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 15px 0; color: #555555; font-size: 14px; line-height: 1.6; text-align: center; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                Or copy and paste this link into your browser:
                            </p>
                            
                            <div style="background-color: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px; margin: 20px 0; word-break: break-all;">
                                <p style="margin: 0; color: #2c3e50; font-size: 12px; line-height: 1.5; font-family: 'Courier New', monospace;">{{ $resetUrl }}</p>
                            </div>
                            
                            <div style="background-color: #fff9e6; border-left: 3px solid #f39c12; padding: 15px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #856404; font-size: 13px; line-height: 1.5; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                    <strong>Important:</strong> This password reset link will expire in <strong>60 minutes</strong>. If you did not request a password reset, please ignore this email and your password will remain unchanged.
                                </p>
                            </div>
                            
                            <div style="background-color: #e8f4f8; border-left: 3px solid #3498db; padding: 15px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #0c5460; font-size: 13px; line-height: 1.5; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                    <strong>Security Tip:</strong> For your security, never share your password reset link with anyone. NIXI Application staff will never ask for your password or reset link.
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
