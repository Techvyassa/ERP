<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 30px;
            border: 1px solid #e5e7eb;
        }
        .credentials {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #4F46E5;
        }
        .credential-item {
            margin: 10px 0;
        }
        .credential-label {
            font-weight: bold;
            color: #6b7280;
            font-size: 14px;
        }
        .credential-value {
            font-size: 16px;
            color: #111827;
            font-family: 'Courier New', monospace;
            background-color: #f3f4f6;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
        }
        .button {
            display: inline-block;
            background-color: #4F46E5;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #4338CA;
        }
        .warning {
            background-color: #FEF3C7;
            border-left: 4px solid #F59E0B;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .footer {
            text-align: center;
            color: #6b7280;
            font-size: 14px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ config('app.name') }}!</h1>
        <p>Your ERP system is ready to use</p>
    </div>

    <div class="content">
        <p>Hello <strong>{{ $firstName }}</strong>,</p>

        <p>Congratulations! Your organization <strong>{{ $organizationName }}</strong> has been successfully registered and provisioned.</p>

        <p>Your ERP system is now ready to use. Below are your login credentials:</p>

        <div class="credentials">
            <div class="credential-item">
                <div class="credential-label">Email Address:</div>
                <div class="credential-value">{{ $email }}</div>
            </div>

            <div class="credential-item">
                <div class="credential-label">Temporary Password:</div>
                <div class="credential-value">{{ $tempPassword }}</div>
            </div>
        </div>

        <div class="warning">
            <strong>⚠️ Important Security Notice:</strong><br>
            Please change your password immediately after your first login for security purposes.
        </div>

        <center>
            <a href="{{ $loginUrl }}" class="button">Login to Your Account</a>
        </center>

        <h3>What's Next?</h3>
        <ul>
            <li>Login using the credentials above</li>
            <li>Change your temporary password</li>
            <li>Complete your profile setup</li>
            <li>Add team members</li>
            <li>Start using the ERP system</li>
        </ul>

        <h3>Trial Period</h3>
        <p>Your account includes a <strong>14-day free trial</strong> with full access to all features. Explore the system and see how it can help your business!</p>

        <h3>Need Help?</h3>
        <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        <p>This is an automated email. Please do not reply to this message.</p>
    </div>
</body>
</html>
