<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $organizationName }}</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0; }
        .email-wrapper { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #193261 0%, #2563eb 100%); color: white; padding: 40px 30px; text-align: center; }
        .header h1 { margin: 0 0 8px 0; font-size: 26px; font-weight: 800; }
        .header p { margin: 0; font-size: 15px; opacity: 0.9; }
        .content { padding: 40px 30px; }
        .greeting { font-size: 18px; color: #111827; margin-bottom: 20px; }
        .credentials-box { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #193261; border-radius: 12px; padding: 24px; margin: 24px 0; }
        .credentials-title { font-size: 15px; font-weight: 700; color: #193261; margin-bottom: 16px; text-align: center; }
        .credential-item { background-color: white; padding: 14px 16px; border-radius: 8px; margin-bottom: 10px; }
        .credential-item:last-child { margin-bottom: 0; }
        .credential-label { font-weight: 600; color: #6b7280; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
        .credential-value { font-size: 16px; color: #111827; font-family: 'Courier New', monospace; font-weight: 600; word-break: break-all; }
        .button-container { text-align: center; margin: 28px 0 16px; }
        .button { display: inline-block; background-color: #193261; color: white; padding: 14px 36px; text-decoration: none; border-radius: 8px; font-weight: 700; font-size: 15px; }
        .button-dept { display: inline-block; background-color: #059669; color: white; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; margin-top: 10px; }
        .warning-box { background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 18px; margin: 20px 0; border-radius: 8px; }
        .warning-box strong { color: #92400e; display: block; margin-bottom: 4px; }
        .warning-box p { margin: 0; color: #78350f; font-size: 13px; }
        .footer { background-color: #f9fafb; text-align: center; padding: 28px 30px; border-top: 1px solid #e5e7eb; }
        .footer-text { color: #6b7280; font-size: 12px; margin: 6px 0; }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <h1>Welcome to {{ $organizationName }}!</h1>
            <p>Your account has been created</p>
        </div>

        <div class="content">
            <div class="greeting">Hello <strong>{{ $firstName }}</strong>,</div>

            <p>Your user account has been set up on <strong>{{ $organizationName }}</strong>. Here are your login credentials:</p>

            <div class="credentials-box">
                <div class="credentials-title">🔐 Your Login Credentials</div>
                <div class="credential-item">
                    <div class="credential-label">Email Address</div>
                    <div class="credential-value">{{ $email }}</div>
                </div>
                <div class="credential-item">
                    <div class="credential-label">Temporary Password</div>
                    <div class="credential-value">{{ $tempPassword }}</div>
                </div>
            </div>

            <div class="warning-box">
                <strong>🔒 Important Security Notice</strong>
                <p>Please change your password immediately after your first login.</p>
            </div>

            <div class="button-container">
                <!-- <a href="{{ $loginUrl }}" class="button">Sign In to Your Account</a> -->
                <br>
                <a href="{{ $departmentUrl }}" class="button-dept">Go to Your Login Page</a>
            </div>
        </div>

        <div class="footer">
            <p class="footer-text">© {{ date('Y') }} {{ $organizationName }}. All rights reserved.</p>
            <p class="footer-text">This is an automated email. Please do not reply.</p>
        </div>
    </div>
</body>
</html>
