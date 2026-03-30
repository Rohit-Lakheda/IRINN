<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRINN Application Resubmission Required</title>
</head>
<body style="margin:0;padding:0;font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;background:#f5f5f5;">
    <table role="presentation" style="width:100%;border-collapse:collapse;background:#f5f5f5;">
        <tr>
            <td align="center" style="padding:30px 20px;">
                <table role="presentation" style="max-width:600px;width:100%;background:#fff;border:1px solid #e0e0e0;border-radius:8px;">
                    <tr>
                        <td style="padding:24px 28px;border-bottom:1px solid #eee;background:#2B2F6C;">
                            <p style="margin:0;color:#fff;font-size:20px;font-weight:600;">IRINN Application — Resubmission Required</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px;color:#333;font-size:16px;line-height:1.5;">
                                Hello {{ $application->user->fullname ?? 'Applicant' }},
                            </p>
                            <p style="margin:0 0 20px;color:#555;font-size:15px;line-height:1.6;">
                                Your IRINN application <strong>{{ $application->application_id }}</strong> requires updates. Please sign in to the portal, open <strong>My Applications</strong>, and use <strong>Edit application</strong> to review the message from our team and resubmit your details.
                            </p>
                            <div style="background:#f8f9fa;border-radius:6px;padding:18px;margin:20px 0;border:1px solid #e8e8e8;">
                                <p style="margin:0 0 8px;color:#1e2a4a;font-size:14px;font-weight:600;">Message from admin</p>
                                <p style="margin:0;color:#444;font-size:14px;line-height:1.6;white-space:pre-wrap;">{{ $resubmissionReason }}</p>
                            </div>
                            <p style="margin:0;color:#666;font-size:14px;line-height:1.5;">
                                No additional application fee is required for this resubmission.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
