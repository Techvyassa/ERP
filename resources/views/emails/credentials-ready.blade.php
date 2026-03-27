<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Login Credentials</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 600px;
            margin: 32px auto;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
        }
        .header {
            background: #193261;
            color: #ffffff;
            padding: 28px 24px;
        }
        .content {
            padding: 28px 24px;
        }
        .panel {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 18px;
            margin: 20px 0;
        }
        .credential-row {
            background: #ffffff;
            border: 1px solid #dbeafe;
            border-radius: 8px;
            padding: 14px;
            margin-bottom: 12px;
        }
        .credential-label {
            font-size: 12px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .credential-value {
            font-size: 16px;
            font-family: "Courier New", monospace;
            color: #111827;
            word-break: break-all;
        }
        .notice {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 14px 16px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background: #193261;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 8px;
            font-weight: 700;
        }
        .muted {
            color: #6b7280;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">Your ERP login credentials are ready</h1>
        </div>
        <div class="content">
            <p>Hi {{ $firstName }},</p>
            <p>Your workspace for <strong>{{ $organizationName }}</strong> has been created successfully. You can now sign in using the credentials below.</p>

            <div class="panel">
                <div class="credential-row">
                    <div class="credential-label">Login Email</div>
                    <div class="credential-value">{{ $email }}</div>
                </div>
                <div class="credential-row" style="margin-bottom: 0;">
                    <div class="credential-label">Temporary Password</div>
                    <div class="credential-value">{{ $tempPassword }}</div>
                </div>
            </div>

            <p><a href="{{ $loginUrl }}" class="button">Open Login Page</a></p>

            <div class="notice">
                <strong>Important:</strong> Please change your password after your first successful login.
            </div>

            <p class="muted">This email is sent only after tenant database creation and migration complete, so you can log in without setup-related access issues.</p>
        </div>
    </div>
</body>
</html>
