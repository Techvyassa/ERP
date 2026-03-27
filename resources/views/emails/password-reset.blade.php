<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password - Zap ERP</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0; }
        .email-wrapper { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #193261 0%, #2563eb 100%); color: white; padding: 40px 30px; text-align: center; }
        .header h1 { margin: 0 0 8px 0; font-size: 26px; font-weight: 800; }
        .header p { margin: 0; font-size: 15px; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #111827; margin-bottom: 20px; }
        .button-container { text-align: center; margin: 32px 0; }
        .button { display: inline-block; background-color: #193261; color: white; padding: 14px 36px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; }
        .warning-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 18px; margin: 20px 0; border-radius: 8px; }
        .warning-box strong { color: #92400e; display: block; margin-bottom: 4px; }
        .warning-box p { margin: 0; color: #78350f; font-size: 13px; }
        .link-fallback { word-break: break-all; color: #193261; font-size: 13px; }
        .footer { background-color: #f9fafb; text-align: center; padding: 28px 30px; border-top: 1px solid #e5e7eb; }
        .footer-text { color: #6b7280; font-size: 12px; margin: 6px 0; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>Reset Your Password</h1>
            <p>Zap ERP — Password Recovery</p>
        </div>

        <div class="content">
            <div class="greeting">Hello <strong>{{ $firstName }}</strong>,</div>

            <p>We received a request to reset the password for your Zap ERP account. Click the button below to set a new password.</p>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Reset Password</a>
            </div>

            <div class="warning-box">
                <strong>⏱ This link expires in {{ $expiresIn }}</strong>
                <p>If you didn't request a password reset, you can safely ignore this email. Your password will not change.</p>
            </div>

            <p style="font-size: 13px; color: #6b7280;">If the button above doesn't work, copy and paste this link into your browser:</p>
            <p class="link-fallback">{{ $resetUrl }}</p>
        </div>

        <div class="footer">
            <p class="footer-text">🔒 Enterprise Grade Security • © {{ date('Y') }} Zap ERP Systems</p>
            <p class="footer-text">This is an automated message, please do not reply.</p>
        </div>
    </div>
</body>
</html>
