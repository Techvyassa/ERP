<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Setup Update</title>
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
            background: #7f1d1d;
            color: #ffffff;
            padding: 28px 24px;
        }
        .content {
            padding: 28px 24px;
        }
        .panel {
            background: #fef2f2;
            border: 1px solid #fecaca;
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
            <h1 style="margin: 0; font-size: 24px;">Workspace setup needs attention</h1>
        </div>
        <div class="content">
            <p>Hi {{ $firstName }},</p>
            <p>We received your registration for <strong>{{ $organizationName }}</strong>, but we hit a problem while preparing your workspace.</p>

            <div class="panel">
                <p style="margin-top: 0;"><strong>What this means</strong></p>
                <p style="margin-bottom: 0;">Your organization record was created, but the workspace setup did not finish successfully, so login is not ready yet.</p>
            </div>

            <p>Please try again later or contact support@if($supportEmail) at <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@endif.</p>
            <p class="muted">Technical summary: {{ $errorMessage }}</p>
            <p class="muted">Organization URL: <a href="{{ $organizationUrl }}">{{ $organizationUrl }}</a></p>
        </div>
    </div>
</body>
</html>
