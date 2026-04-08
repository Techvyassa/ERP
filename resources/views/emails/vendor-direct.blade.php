<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Message from {{ $orgName }}</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
  .wrapper { max-width: 680px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #1e40af; color: #fff; padding: 24px 32px; }
  .header h1 { margin: 0; font-size: 20px; }
  .body { padding: 32px; }
  .greeting { font-size: 16px; font-weight: 600; margin-bottom: 20px; color: #1e293b; }
  .message-content { font-size: 14px; line-height: 1.6; color: #334155; white-space: pre-line; }
  .footer { background: #f8fafc; padding: 24px 32px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
  .footer strong { color: #64748b; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>{{ $orgName }}</h1>
  </div>

  <div class="body">
    <div class="greeting">Dear {{ $vendorName }},</div>
    
    <div class="message-content">
      {!! nl2br(e($messageBody)) !!}
    </div>

    <div style="margin-top: 32px; font-size: 14px; color: #64748b;">
      Best Regards,<br>
      <strong>The {{ $orgName }} Team</strong>
    </div>
  </div>

  <div class="footer">
    This email was sent to you from the {{ $orgName }} ERP system. 
    Property of {{ $orgName }}.
  </div>
</div>
</body>
</html>
