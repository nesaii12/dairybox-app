<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DairyBox Verification Code</title>
</head>
<body style="margin:0;padding:0;background-color:#f8fafc;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f8fafc;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:520px;background-color:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
                    <tr>
                        <td style="background-color:#0284c7;padding:24px 28px;text-align:center;">
                            <div style="font-size:22px;font-weight:800;letter-spacing:-0.02em;color:#ffffff;">
                                DairyBox
                            </div>
                            <div style="font-size:12px;color:#e0f2fe;margin-top:4px;">
                                Account Email Verification
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 12px;font-size:15px;font-weight:600;color:#0f172a;">
                                Hello {{ $customerName }},
                            </p>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.6;color:#475569;">
                                Thank you for creating a DairyBox account. Enter the 6-digit verification code below to confirm your email address and finish signing up:
                            </p>

                            <div style="background-color:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:18px 16px;text-align:center;margin:0 0 22px;">
                                <div style="font-size:32px;font-weight:800;letter-spacing:0.32em;color:#0369a1;font-family:Consolas,'Courier New',monospace;padding-left:0.32em;">
                                    {{ $otp }}
                                </div>
                                <div style="font-size:12px;color:#0369a1;margin-top:8px;font-weight:600;">
                                    Expires in {{ $expiresMinutes }} minutes
                                </div>
                            </div>

                            <p style="margin:0;font-size:13px;line-height:1.5;color:#64748b;">
                                For your security, never share this code with anyone. If you did not request to create a DairyBox account, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 28px;text-align:center;font-size:12px;color:#94a3b8;">
                            &copy; {{ date('Y') }} DairyBox. Farm-fresh dairy, delivered with care.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
