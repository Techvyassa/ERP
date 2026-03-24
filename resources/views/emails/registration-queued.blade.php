<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Received</title>
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
            <p>Thanks for joining us! We've received your registration for <strong>{{ $organizationName }}</strong>.</p>

            <div class="panel">
                <p style="margin-top: 0;"><strong>What's happening now?</strong></p>
                <p style="margin-bottom: 0;">We are currently preparing your private workspace and setting everything up for you. This usually takes just a few minutes.</p>
            </div>

            <p>As soon as everything is ready, we'll send a follow-up email to <strong>{{ $email }}</strong> with your login details.</p>
            
            <p>In the meantime, you can bookmark your future login page:<br>
                <a href="{{ $organizationUrl }}">{{ $organizationUrl }}</a>
            </p>
            
            <p>We're excited to have you on board!</p>
        </div>
    </div>
</body>
</html>
