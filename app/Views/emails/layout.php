<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject ?? 'Notification from Claret International School') ?></title>
    <style>
        body { margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.6; }
        .wrapper { width: 100%; table-layout: fixed; background-color: #f8fafc; padding: 30px 15px; }
        .main-card { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: linear-gradient(135deg, #6B1D2F 0%, #8A243D 50%, #A32E4A 100%); padding: 32px 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 10px 0 0 0; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; color: #ffffff; }
        .header p { margin: 4px 0 0 0; font-size: 13px; color: #fce7eb; font-weight: 500; letter-spacing: 0.5px; text-transform: uppercase; }
        .crest-badge { display: inline-block; width: 50px; height: 50px; background-color: rgba(255,255,255,0.15); border-radius: 12px; line-height: 50px; font-size: 24px; font-weight: bold; border: 1px solid rgba(255,255,255,0.3); }
        .content { padding: 36px 32px; }
        .footer { background-color: #f1f5f9; padding: 24px 30px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 4px 0; }
        .btn { display: inline-block; background-color: #7B3046; color: #ffffff !important; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 10px; text-decoration: none; margin-top: 16px; box-shadow: 0 2px 4px rgba(123, 48, 70, 0.2); }
        .info-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px 20px; margin: 20px 0; }
        .highlight-value { font-weight: 700; color: #0f172a; }
        @media only screen and (max-width: 600px) {
            .content { padding: 24px 20px; }
            .header { padding: 24px 20px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td align="center">
                    <div class="main-card">
                        <!-- Brand Header -->
                        <div class="header">
                            <div class="crest-badge">🎓</div>
                            <h1>Claret International School</h1>
                            <p>Excellence • Discipline • Leadership</p>
                        </div>

                        <!-- Email Body Slot -->
                        <div class="content">
                            <?= $content ?? '' ?>
                        </div>

                        <!-- Footer -->
                        <div class="footer">
                            <p><strong>Claret International School LMS Portal</strong></p>
                            <p>Plot 12/14 Claret Avenue, Off Institutional Road, Port Harcourt / Owerri</p>
                            <p>Phone: +234 (0) 803 000 1234 &bull; Email: <a href="mailto:info@claret.edu.ng" style="color:#7B3046;text-decoration:none;">info@claret.edu.ng</a></p>
                            <p style="margin-top:10px;font-size:11px;color:#94a3b8;">
                                This is an automated official transmission from the Claret School Information System. If you received this in error, please contact the administrative registrar.
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
