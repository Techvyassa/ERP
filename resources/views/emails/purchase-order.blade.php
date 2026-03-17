<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Purchase Order {{ $po->po_number }}</title>
<style>
  body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; color: #333; }
  .wrapper { max-width: 680px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
  .header { background: #1e40af; color: #fff; padding: 28px 32px; }
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
  .totals { text-align: right; font-size: 14px; margin-bottom: 24px; }
  .totals .grand { font-size: 17px; font-weight: 700; color: #1e40af; margin-top: 6px; }
  .note { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 12px 14px; font-size: 13px; color: #92400e; margin-bottom: 24px; }
  .footer { background: #f8fafc; padding: 18px 32px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; }
</style>
</head>
<body>
<div class="wrapper">
  <div class="header">
    <h1>Purchase Order</h1>
    <p>{{ $po->po_number }} &nbsp;·&nbsp; {{ $orgName }}</p>
  </div>

  <div class="body">
    <p class="greeting">Dear {{ $contactName }},</p>
    <p style="font-size:14px;margin-bottom:20px;">
      Please find below the details of the Purchase Order issued by <strong>{{ $orgName }}</strong>.
      Kindly acknowledge receipt and confirm the expected delivery date.
    </p>

    <div class="info-grid">
      <div class="info-box">
        <div class="label">PO Number</div>
        <div class="value">{{ $po->po_number }}</div>
      </div>
      <div class="info-box">
        <div class="label">PO Date</div>
        <div class="value">{{ $po->po_date ? \Carbon\Carbon::parse($po->po_date)->format('d M Y') : '—' }}</div>
      </div>
      <div class="info-box">
        <div class="label">Expected Delivery</div>
        <div class="value">{{ $po->expected_delivery ? \Carbon\Carbon::parse($po->expected_delivery)->format('d M Y') : '—' }}</div>
      </div>
      <div class="info-box">
        <div class="label">Payment Terms</div>
        <div class="value">{{ $po->payment_terms ?: '—' }}</div>
      </div>
    </div>

    <!-- Line Items -->
    <table class="items">
      <thead>
        <tr>
          <th>#</th>
          <th>Material</th>
          <th>Qty</th>
          <th>Unit Price</th>
          <th style="text-align:right">Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($po->lineItems as $i => $item)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>
            {{ $item->material ? $item->material->material_name : '—' }}
            @if($item->material && $item->material->material_code)
              <br><span style="font-size:11px;color:#94a3b8">{{ $item->material->material_code }}</span>
            @endif
          </td>
          <td>{{ $item->ordered_qty }} {{ $item->uom ? $item->uom->uom_code : '' }}</td>
          <td>₹{{ number_format($item->unit_price, 2) }}</td>
          <td style="text-align:right">₹{{ number_format($item->line_total, 2) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:16px">No line items</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="totals">
      @if($po->discount_amount > 0)
        <div>Discount: −₹{{ number_format($po->discount_amount, 2) }}</div>
      @endif
      @if($po->tax_amount > 0)
        <div>Tax: ₹{{ number_format($po->tax_amount, 2) }}</div>
      @endif
      @if($po->freight_charges > 0)
        <div>Freight: ₹{{ number_format($po->freight_charges, 2) }}</div>
      @endif
      <div class="grand">Grand Total: ₹{{ number_format($po->grand_total, 2) }}</div>
    </div>

    {{-- Remarks hidden --}}

    <p style="font-size:13px;color:#64748b;">
      Please confirm this order by replying to this email. For any queries, contact your procurement team.
    </p>

    @if(!empty($viewUrl))
    <div style="text-align:center;margin:24px 0;">
      <a href="{{ $viewUrl }}"
         style="display:inline-block;background:#1e40af;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:14px;font-weight:600;">
        View PO
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
