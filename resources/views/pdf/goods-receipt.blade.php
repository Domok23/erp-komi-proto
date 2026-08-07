<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Goods Receipt {{ $goodsReceipt->gr_number }}</title>
    <style>
        @page { margin: 1.5cm; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; font-weight: bold; color: #495057; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header { margin-bottom: 30px; border-bottom: 2px solid #dee2e6; padding-bottom: 10px; }
        .company-name { font-size: 18pt; font-weight: bold; color: #212529; margin: 0; }
        .company-info { font-size: 9pt; color: #6c757d; margin-top: 5px; }
        .title-bar { text-align: center; margin: 20px 0; }
        .title { font-size: 16pt; font-weight: bold; letter-spacing: 1px; margin: 0; color: #212529; }
        .metadata-container { margin-bottom: 20px; }
        .left-col { float: left; width: 50%; }
        .right-col { float: right; width: 45%; }
        .clear { clear: both; }
        .section-title { font-size: 12pt; font-weight: bold; color: #212529; border-bottom: 1px solid #dee2e6; padding-bottom: 4px; margin-top: 25px; margin-bottom: 10px; }
        .signature-block { margin-top: 50px; }
        .signature-col-right { width: 45%; text-align: center; float: right; }
        .signature-col-left { width: 45%; text-align: center; float: left; }
        .signature-img { max-width: 180px; max-height: 70px; margin: 10px 0; border-bottom: 1px solid #ddd; }
        .signature-placeholder { height: 70px; margin: 10px 0; }
        .footer { margin-top: 50px; font-size: 8pt; color: #868e96; text-align: center; border-top: 1px solid #dee2e6; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name ?? 'PT KOMITRANDO EMPORIO' }}</div>
        <div class="company-info">
            {{ $company->address ?? 'Jalan Solo KM 14' }}, {{ $company->city ?? 'Sleman, Yogyakarta' }}<br>
            Phone: {{ $company->phone ?? '-' }} | Email: {{ $company->email ?? '-' }} | NPWP: {{ $company->npwp ?? '-' }}
        </div>
    </div>

    <div class="title-bar">
        <div class="title">GOODS RECEIPT NOTE (GRN)</div>
    </div>

    <div class="metadata-container">
        <div class="left-col">
            <strong>WAREHOUSE / RECEIPT INFO:</strong><br>
            Warehouse: {{ $warehouse->name ?? '-' }}<br>
            Address: {{ $warehouse->address ?? '-' }}<br>
            Received By: {{ $goodsReceipt->received_by ?? '-' }}<br>
            Status: {{ strtoupper($goodsReceipt->status ?? '-') }}
        </div>
        <div class="right-col">
            <strong>GRN DETAILS:</strong><br>
            <table>
                <tr>
                    <td style="border: none; padding: 2px 0; width: 45%;">GRN Number:</td>
                    <td style="border: none; padding: 2px 0;"><strong>{{ $goodsReceipt->gr_number }}</strong></td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">PO Reference:</td>
                    <td style="border: none; padding: 2px 0;">{{ $po->po_number ?? '-' }}</td>
                </tr>
                <tr>
                    <td style="border: none; padding: 2px 0;">Receipt Date:</td>
                    <td style="border: none; padding: 2px 0;">{{ $goodsReceipt->receipt_date?->format('d M Y') ?? '-' }}</td>
                </tr>
            </table>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section-title">RECEIVED ITEMS</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Material Code</th>
                <th>Material Description</th>
                <th class="text-right" style="width: 15%;">Qty Ordered</th>
                <th class="text-right" style="width: 15%;">Qty Received</th>
                <th class="text-right" style="width: 15%;">Qty Rejected</th>
                <th style="width: 10%;">Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse($goodsReceipt->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->material->code ?? '-' }}</td>
                    <td>{{ $item->material->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($item->qty_ordered, 2) }}</td>
                    <td class="text-right">{{ number_format($item->qty_received, 2) }}</td>
                    <td class="text-right">{{ number_format($item->qty_rejected, 2) }}</td>
                    <td>{{ $item->unit }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No items found in this Goods Receipt.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($goodsReceipt->notes)
    <div class="section-title">NOTES</div>
    <div style="font-size: 9pt; color: #495057;">
        {{ $goodsReceipt->notes }}
    </div>
    @endif

    <div class="signature-block">
        <div class="signature-col-left">
            <strong>Verified By,</strong>
            <div class="signature-placeholder"></div>
            <div style="border-top: 1px solid #333; width: 180px; margin: 0 auto; padding-top: 5px;">
                Warehouse Supervisor
            </div>
        </div>
        <div class="signature-col-right">
            <strong>Receiver Signature,</strong>
            <div>
                @if($goodsReceipt->receiver_signature)
                    <img src="{{ $goodsReceipt->receiver_signature }}" class="signature-img" />
                @else
                    <div class="signature-placeholder" style="border-bottom: 1px solid #ddd; width: 180px; margin: 10px auto 0;">[Pending Signature]</div>
                @endif
            </div>
            <div style="width: 180px; margin: 0 auto; padding-top: 5px;">
                {{ $goodsReceipt->received_by ?? 'Receiver representative' }}
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="footer">
        This document is system-generated and valid without wet signature.
    </div>
</body>
</html>
