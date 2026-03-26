<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IX Application Rejected</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body style="margin: 0; padding: 0; font-family: 'Nunito', 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif; background-color: #f5f5f5;">
    <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
        <tr>
            <td align="center" style="padding: 30px 20px;">
                <table role="presentation" style="max-width: 600px; width: 100%; background-color: #ffffff; border: 10px solid #e74c3c; border-radius: 4px; overflow: hidden;">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 25px 30px; border-bottom: 2px solid #e74c3c; background-color: #e74c3c;">
                            <p style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 600; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">IX Application Rejected</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">Hello {{ $application->user->fullname }},</p>
                            
                            <p style="margin: 0 0 25px 0; color: #555555; font-size: 15px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                We regret to inform you that your IX application has been rejected by <strong>{{ $rejectedBy }}</strong>.
                            </p>
                            
                            <!-- Application Details Box -->
                            <div style="background-color: #f8f9fa; border-radius: 6px; padding: 25px; margin: 30px 0; border: 1px solid #e0e0e0;">
                                <p style="margin: 0 0 20px 0; color: #2c3e50; font-size: 16px; font-weight: 600; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">Application Details</p>
                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px 0; color: #555555; font-size: 14px; width: 40%; font-family: 'Nunito', 'Trebuchet MS', sans-serif;"><strong>Application ID:</strong></td>
                                        <td style="padding: 8px 0; color: #2c3e50; font-size: 14px; font-weight: 600; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">{{ $application->application_id }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #555555; font-size: 14px; font-family: 'Nunito', 'Trebuchet MS', sans-serif;"><strong>Status:</strong></td>
                                        <td style="padding: 8px 0;">
                                            <span style="background-color: #e74c3c; color: #ffffff; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">Rejected</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; color: #555555; font-size: 14px; font-family: 'Nunito', 'Trebuchet MS', sans-serif;"><strong>Rejected By:</strong></td>
                                        <td style="padding: 8px 0; color: #2c3e50; font-size: 14px; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">{{ $rejectedBy }}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Rejection Reason Box -->
                            <div style="background-color: #f8d7da; border-left: 4px solid #e74c3c; padding: 20px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0 0 10px 0; color: #721c24; font-size: 14px; font-weight: 600; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">Rejection Reason:</p>
                                <p style="margin: 0; color: #721c24; font-size: 14px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">{{ $rejectionReason }}</p>
                            </div>
                            
                            <div style="background-color: #e8f4f8; border-left: 3px solid #3498db; padding: 15px; margin: 20px 0; border-radius: 4px;">
                                <p style="margin: 0; color: #0c5460; font-size: 13px; line-height: 1.5; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                    <strong>Note:</strong> If you have any questions or concerns about this decision, please contact our support team.
                                </p>
                            </div>
                            
                            <p style="margin: 35px 0 0 0; color: #555555; font-size: 14px; line-height: 1.6; font-family: 'Nunito', 'Trebuchet MS', sans-serif;">
                                <span style="font-family: 'Brush Script MT', 'Lucida Handwriting', cursive; font-size: 20px; color: #2c3e50; font-weight: normal;">Regards,<br>NIXI IX Team</span>
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

