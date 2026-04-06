<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Requisition {{ $pr->pr_number }}</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
  .wrapper { max-width: 680px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #059669; color: #fff; padding: 28px 32px; }
  .header h1 { margin: 0; font-size: 22px; }
  .header p { margin: 4px 0 0; font-size: 13px; opacity: .85; }
  .body { padding: 28px 32px; }
  .greeting { font-size: 15px; margin-bottom: 16px; }
  .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
  .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px 14px; }
  .info-box .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: .5px; }
  .info-box .value { font-size: 14px; font-weight: 600; color: #1e293b; margin-top: 2px; }
  table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
  table.items th { background: #f1f5f9; text-align: left; padding: 9px 12px; font-size: 11px; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; }
  table.items td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; }
  .note { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 12px 14px; font-size: 13px; color: #065f46; margin-bottom: 24px; }
  .footer { background: #f8fafc; padding: 18px 32px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Purchase Requisition</h1>
    <p>{{ $pr->pr_number }} &nbsp;·&nbsp; {{ $orgName }}</p>
  </div>

  <div class="body">
    <p class="greeting">Dear {{ $contactName }},</p>
    <p style="font-size:14px;margin-bottom:20px;">
      We are pleased to share the following Purchase Requisition from <strong>{{ $orgName }}</strong>.
      Please review the requirements and provide your quotation at your earliest convenience.
    </p>

    @if(!empty($customMessage))
    <div class="note">
      <strong>Message:</strong><br>
      {{ $customMessage }}
    </div>
    @endif

    <div class="info-grid">
      <div class="info-box">
        <div class="label">PR Number</div>
        <div class="value">{{ $pr->pr_number }}</div>
      </div>
      <div class="info-box">
        <div class="label">PR Date</div>
        <div class="value">{{ $pr->pr_date ? \Carbon\Carbon::parse($pr->pr_date)->format('d M Y') : '—' }}</div>
      </div>
      <div class="info-box">
        <div class="label">Required Date</div>
        <div class="value">{{ $pr->required_date ? \Carbon\Carbon::parse($pr->required_date)->format('d M Y') : '—' }}</div>
      </div>
      <div class="info-box">
        <div class="label">Priority</div>
        <div class="value">{{ $pr->priority ?: '—' }}</div>
      </div>
    </div>

    <!-- Line Items -->
    <table class="items">
      <thead>
        <tr>
          <th>#</th>
          <th>Item Name</th>
          <th>Description</th>
          <th>Qty</th>
          <th>UOM</th>
        </tr>
      </thead>
      <tbody>
        @forelse($pr->lineItems as $i => $item)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>
            {{ $item->item_name }}
            @if($item->material && $item->material->material_code)
              <br><span style="font-size:11px;color:#94a3b8">{{ $item->material->material_code }}</span>
            @endif
          </td>
          <td style="font-size:12px;color:#64748b">{{ $item->description ?: '—' }}</td>
          <td>{{ $item->quantity }}</td>
          <td>{{ $item->uom ? $item->uom->uom_code : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:16px">No line items</td></tr>
        @endforelse
      </tbody>
    </table>

    @if(!empty($pr->justification))
    <div style="background:#f8fafc;border-left:3px solid #059669;padding:12px 14px;margin-bottom:20px;">
      <strong style="font-size:12px;color:#64748b;text-transform:uppercase">Justification:</strong><br>
      <span style="font-size:13px;color:#1e293b">{{ $pr->justification }}</span>
    </div>
    @endif

    <p style="font-size:13px;color:#64748b;">
      Please provide your best quotation for the above items. For any queries, feel free to contact our procurement team.
    </p>

    @if(!empty($viewUrl))
    <div style="text-align:center;margin:24px 0;">
      <a href="{{ $viewUrl }}"
         style="display:inline-block;background:#059669;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;">
        View PR
      </a>
    </div>
    @endif
  </div>

  <div class="footer">
    This is an automated email from {{ $orgName }} ERP system. Please do not reply directly to this email.
  </div>
</div>
</body>
</html>
