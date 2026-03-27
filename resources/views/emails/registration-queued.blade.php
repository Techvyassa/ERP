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
        .muted {
            color: #6b7280;
            font-size: 14px;
        }
        a {
            color: #2563eb;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1 style="margin: 0; font-size: 24px;">Welcome to {{ config('app.name') }}!</h1>
        </div>
        <div class="content">
            <p>Hi {{ $firstName }},</p>
            <p>Thanks for registering <strong>{{ $organizationName }}</strong> with us. Your organization signup is successful and your ERP workspace setup has started.</p>

            <div class="panel">
                <p style="margin-top: 0;"><strong>Welcome message</strong></p>
                <p>Welcome to {{ config('app.name') }}, your ERP software for managing key business operations in one place.</p>
                <p><strong>ERP software details:</strong> your account will help your team manage organization setup, users, process workflows, and day-to-day operational records from a single system.</p>
                <p><strong>How it works:</strong> after registration, we create your dedicated tenant database, run migrations, prepare the workspace, and create your admin login.</p>
                <p><strong>What it is for:</strong> this ERP environment is intended to centralize your organization data and support structured operations with role-based access.</p>
                <p style="margin-bottom: 0;"><strong>Please wait until you receive the separate credentials email before trying to log in.</strong> This helps prevent login issues while tenant database creation and migration are still in progress.</p>
            </div>

            <p>Once provisioning finishes, we will send a separate credential email to <strong>{{ $email }}</strong> with your login email and password.</p>
            
            <p>In the meantime, you can bookmark your future login page:<br>
                <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
            </p>
            
            <p class="muted">Do not try to sign in before the credentials email arrives, especially before tenant DB creation completes.</p>
        </div>
    </div>
</body>
</html>
