<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #193261 0%, #2563eb 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .logo {
            width: 48px;
            height: 48px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 800;
        }
        .header p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            color: #111827;
            margin-bottom: 20px;
        }
        .credentials-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #193261;
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .credentials-title {
            font-size: 16px;
            font-weight: 700;
            color: #193261;
            margin-bottom: 16px;
            text-align: center;
        }
        .credential-item {
            background-color: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .credential-item:last-child {
            margin-bottom: 0;
        }
        .credential-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .credential-value {
            font-size: 18px;
            color: #111827;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            word-break: break-all;
        }
        .button-container {
            text-align: center;
            margin: 32px 0;
        }
        .button {
            display: inline-block;
            background-color: #193261;
            color: white;
            padding: 16px 40px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(25, 50, 97, 0.3);
        }
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 16px 20px;
            margin: 24px 0;
            border-radius: 8px;
        }
        .warning-box strong {
            color: #92400e;
            display: block;
            margin-bottom: 4px;
        }
        .warning-box p {
            margin: 0;
            color: #78350f;
            font-size: 14px;
        }
        .section {
            margin: 32px 0;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 12px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .feature-list li {
            padding: 12px 0 12px 32px;
            position: relative;
            color: #4b5563;
            font-size: 15px;
        }
        .feature-list li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 18px;
        }
        .trial-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .footer {
            background-color: #f9fafb;
            text-align: center;
            padding: 32px 30px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-text {
            color: #6b7280;
            font-size: 13px;
            margin: 8px 0;
        }
        .footer-links {
            margin-top: 16px;
        }
        .footer-link {
            color: #193261;
            text-decoration: none;
            margin: 0 12px;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="header">
            <div class="logo">⚡</div>
            <h1>Welcome to Zap ERP!</h1>
            <p>Your manufacturing workspace is ready</p>
        </div>

        <div class="content">
            <div class="greeting">
                Hello <strong>{{ $firstName }}</strong>,
            </div>

            <p>Congratulations! Your organization <strong>{{ $organizationName }}</strong> has been successfully set up and is ready to use.</p>

            <p>We've created your account and provisioned your dedicated workspace. Here are your login credentials:</p>

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
                <p>Please change your password immediately after your first login to keep your account secure.</p>
            </div>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Sign In to Your Account</a>
            </div>

            <div class="section">
                <div class="section-title">🚀 Getting Started</div>
                <ul class="feature-list">
                    <li>Sign in using the credentials above</li>
                    <li>Change your temporary password</li>
                    <li>Complete your organization profile</li>
                    <li>Invite your team members</li>
                    <li>Configure your first warehouse and materials</li>
                    <li>Start managing your production workflow</li>
                </ul>
            </div>

            <div class="section">
                <div class="trial-badge">✨ 14-Day Free Trial</div>
                <p>Your account includes full access to all features for 14 days. Explore the platform, test all modules, and see how Zap ERP can transform your manufacturing operations!</p>
            </div>

            <div class="section">
                <div class="section-title">💬 Need Help?</div>
                <p>Our support team is here to help you get started. If you have any questions or need assistance, don't hesitate to reach out.</p>
            </div>
        </div>

        <div class="footer">
            <p class="footer-text">🔒 Enterprise Grade Security • SOC2 Compliant</p>
            <p class="footer-text">© {{ date('Y') }} Zap ERP Systems. All rights reserved.</p>
            <div class="footer-links">
                <a href="#" class="footer-link">Help Center</a>
                <a href="#" class="footer-link">Documentation</a>
                <a href="#" class="footer-link">Contact Support</a>
            </div>
            <p class="footer-text" style="margin-top: 16px; font-size: 12px;">
                This is an automated email. Please do not reply to this message.
            </p>
        </div>
    </div>
</body>
</html>
