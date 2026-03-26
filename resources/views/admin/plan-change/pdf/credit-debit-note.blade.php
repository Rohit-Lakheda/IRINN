<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $noteType === 'credit' ? 'Credit' : 'Debit' }} Note - {{ $noteNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: A4;
            margin: 8mm;
        }
        body {
            font-family: 'Georgia', 'Palatino Linotype', 'Book Antiqua', Palatino, serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            background: #fff;
            padding: 5mm;
        }
        .header-section {
            display: table;
            width: 100%;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #000;
        }
        .header-left {
            display: table-cell;
            width: 70%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 30%;
            vertical-align: top;
            text-align: right;
            font-size: 9px;
            font-weight: bold;
        }
        .nixi-logo {
            max-width: 120px;
            height: auto;
            margin-bottom: 4px;
        }
        .note-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 6px;
        }
        .buyer-seller-section {
            display: table;
            width: 100%;
            margin-bottom: 8px;
            border: 1px solid #000;
        }
        .buyer-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 8px;
            border-right: 1px solid #000;
        }
        .seller-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 8px;
        }
        .section-label {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 6px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        .detail-row {
            font-size: 9px;
            margin-bottom: 3px;
            line-height: 1.4;
        }
        .detail-label {
            font-weight: bold;
            display: inline-block;
            min-width: 70px;
        }
        .note-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 9px;
            border: 1px solid #000;
        }
        .note-info-table td {
            padding: 5px;
            border: 1px solid #000;
        }
        .note-info-table td:first-child {
            font-weight: bold;
            width: 40%;
            background-color: #f5f5f5;
        }
        .amount-section {
            margin-top: 10px;
            padding: 8px;
            border: 1px solid #000;
            background-color: #f9f9f9;
        }
        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }
        .amount-label {
            display: table-cell;
            width: 70%;
            font-weight: bold;
            font-size: 11px;
        }
        .amount-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-size: 11px;
        }
        .total-amount {
            font-size: 13px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #000;
            font-size: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="header-left">
            <img src="{{ public_path('images/nixi-logo.png') }}" alt="NIXI Logo" class="nixi-logo" onerror="this.style.display='none';">
            <div class="note-title">{{ strtoupper($noteType) }} NOTE</div>
        </div>
        <div class="header-right">
            <div>Note No: {{ $noteNumber }}</div>
            <div>Date: {{ $noteDate }}</div>
        </div>
    </div>

    <div class="buyer-seller-section">
        <div class="buyer-column">
            <div class="section-label">BILL TO / SHIP TO</div>
            <div class="detail-row">
                <span class="detail-label">Name:</span> {{ $buyerDetails['name'] ?? 'N/A' }}
            </div>
            @if(isset($buyerDetails['company_name']))
            <div class="detail-row">
                <span class="detail-label">Company:</span> {{ $buyerDetails['company_name'] }}
            </div>
            @endif
            @if(isset($buyerDetails['address']))
            <div class="detail-row">
                <span class="detail-label">Address:</span> {{ $buyerDetails['address'] }}
            </div>
            @endif
            @if(isset($buyerDetails['gstin']))
            <div class="detail-row">
                <span class="detail-label">GSTIN:</span> {{ $buyerDetails['gstin'] }}
            </div>
            @endif
            @if(isset($buyerDetails['email']))
            <div class="detail-row">
                <span class="detail-label">Email:</span> {{ $buyerDetails['email'] }}
            </div>
            @endif
            @if(isset($buyerDetails['phone']))
            <div class="detail-row">
                <span class="detail-label">Phone:</span> {{ $buyerDetails['phone'] }}
            </div>
            @endif
        </div>
        <div class="seller-column">
            <div class="section-label">FROM</div>
            <div class="detail-row">
                <span class="detail-label">Name:</span> National Internet Exchange of India
            </div>
            <div class="detail-row">
                <span class="detail-label">Address:</span> 6C, 6th Floor, DLF Centre, Sansad Marg, New Delhi - 110001
            </div>
            <div class="detail-row">
                <span class="detail-label">GSTIN:</span> 07AAACN1234D1Z5
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span> support@nixi.in
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span> +91-11-23738750
            </div>
        </div>
    </div>

    <table class="note-info-table">
        <tr>
            <td>Application ID</td>
            <td>{{ $applicationId }}</td>
        </tr>
        <tr>
            <td>Plan Change Type</td>
            <td>{{ $changeType === 'upgrade' ? 'Upgrade' : 'Downgrade' }}</td>
        </tr>
        <tr>
            <td>Previous Plan</td>
            <td>{{ $currentPortCapacity }} ({{ $currentBillingPlan }})</td>
        </tr>
        <tr>
            <td>New Plan</td>
            <td>{{ $newPortCapacity }} ({{ $newBillingPlan }})</td>
        </tr>
        <tr>
            <td>Effective From</td>
            <td>{{ $effectiveFrom }}</td>
        </tr>
        @if($remainingDays > 0)
        <tr>
            <td>Adjustment Period</td>
            <td>{{ $remainingDays }} days (of {{ $totalDays }} days)</td>
        </tr>
        @endif
        <tr>
            <td>Reference Invoice</td>
            <td>{{ $referenceInvoiceNumber ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Reason</td>
            <td>{{ $reason ?? 'Plan Change Adjustment' }}</td>
        </tr>
    </table>

    <div class="amount-section">
        <div class="amount-row">
            <div class="amount-label">Base Amount:</div>
            <div class="amount-value">₹{{ number_format(abs($baseAmount), 2) }}</div>
        </div>
        @if($gstAmount > 0)
        <div class="amount-row">
            <div class="amount-label">GST (18%):</div>
            <div class="amount-value">₹{{ number_format($gstAmount, 2) }}</div>
        </div>
        @endif
        <div class="amount-row total-amount">
            <div class="amount-label">{{ $noteType === 'credit' ? 'Credit' : 'Debit' }} Amount:</div>
            <div class="amount-value">₹{{ number_format(abs($totalAmount), 2) }}</div>
        </div>
    </div>

    <div class="footer">
        <p><strong>Note:</strong> This {{ strtolower($noteType) }} note is generated due to plan change adjustment. 
        @if($noteType === 'credit')
        The credit amount will be applied to your next invoice.
        @else
        The additional amount will be charged in your next invoice.
        @endif
        </p>
        <p style="margin-top: 10px;">This is a computer-generated document and does not require a signature.</p>
    </div>
</body>
</html>

