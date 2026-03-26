<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ isset($isCreditNote) && $isCreditNote ? 'Credit Note' : 'Tax Invoice' }} - {{ $invoiceNumber }}</title>
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
            font-size: 12px;
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
        .tax-invoice-title {
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
            line-height: 1.25;
        }
        .seller-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 8px;
            line-height: 1.25;
        }
        .section-label {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 6px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        .detail-row {
            font-size: 12px;
            margin-bottom: 2px;
            line-height: 1.25;
        }
        .detail-label {
            font-weight: bold;
            display: inline-block;
            min-width: 70px;
            vertical-align: top;
        }
        .detail-value {
            display: inline-block;
            vertical-align: top;
            position: relative;
            top: -1px;
        }
        .invoice-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 12px;
            border: 1px solid #000;
        }
        .invoice-info-table td {
            padding: 5px;
            border: 1px solid #000;
        }
        .invoice-info-table td:first-child {
            font-weight: bold;
            width: 25%;
        }
        .particulars-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 12px;
            border: 1px solid #000;
        }
        .particulars-table th,
        .particulars-table td {
            padding: 4px;
            border: 1px solid #000;
            text-align: left;
        }
        .particulars-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }
        .particulars-table td {
            text-align: center;
        }
        .particulars-table td:nth-child(5),
        .particulars-table td:nth-child(6) {
            text-align: right;
        }
        .amount-summary {
            width: 98.5%;
            margin-bottom: 6px;
            font-size: 12px;
            border: 1px solid #000;
            padding: 5px;
        }
        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 2px;
        }
        .amount-label {
            display: table-cell;
            width: 80%;
            text-align: right;
            padding-right: 10px;
            font-weight: bold;
            font-size: 12px;
        }
        .amount-value {
            display: table-cell;
            width: 20%;
            text-align: right;
            font-weight: bold;
            font-size: 12px;
        }
        .total-row {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 4px 0;
            font-size: 10px;
        }
        .amount-in-words {
            font-size: 12px;
            font-style: italic;
            margin-bottom: 6px;
            text-align: right;
            padding: 4px;
            border: 1px solid #000;
        }
        /* .payment-section {
            display: flex;
            width: 100%;
            margin-bottom: 6px;
        } */
        /* .payment-center {
            width: 15%;
        } */
        /* .payment-left { */
            /* display: table-cell; */
            /* width: 40%;
            vertical-align: top; */
            /* padding-right: 5px; */
            /* line-height: 180%;
            box-sizing: border-box;
        } */
        /* .payment-right { */
            /* display: table-cell; */
            /* width: 40%;
            vertical-align: top; */
            /* padding-left: 5px; */
            /* line-height: 180%;
            box-sizing: border-box;
        } */
        .payment-section {
            border: 1px solid #000;
            padding: 10px;
    display: table;
    width: 100%;
    margin-bottom: 6px;
    table-layout: fixed;
}

.payment-left,
.payment-center,
.payment-right {
    margin:20px;
    display: table-cell;
    vertical-align: middle;
    box-sizing: border-box;
}

.payment-left {
    width: 45%;
}

.payment-center {
    width: 5%;
    text-align: center;
    font-weight: bold;
}

.payment-right {
    width: 45%;
}
        .payment-box {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
            line-height: 1.3;
        }
        .payment-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .or-separator {
            text-align: center;
            font-weight: bold;
            margin: 4px 0;
            font-size: 12px;
        }
        .footer-section {
            display: table;
            width: 100%;
            margin-top: 6px;
            page-break-inside: avoid;
        }
        .footer-top {
            display: table;
            width: 100%;
            margin-bottom: 6px;
        }
        .footer-top-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
            padding-right: 5px;
        }
        .footer-top-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            padding-left: 5px;
            text-align: center;
        }
        .terms-section {
            font-size: 12px;
            line-height: 1.3;
            padding: 5px;
        }
        .terms-title {
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 3px;
        }
        .terms-list {
            margin-left: 12px;
        }
        .terms-list li {
            margin-bottom: 1px;
        }
        .esign-section {
            padding: 5px;
            text-align: center;
        }
        .esign-logo {
            font-size: 16px;
            font-weight: bold;
            color: #0066cc;
            margin-bottom: 4px;
        }
        .qr-code-container {
            margin: 5px 0;
        }
        .qr-code-container img {
            max-width: 120px;
            height: auto;
        }
        .qr-esign-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .qr-esign-wrapper img {
            width: 120px;
            height: 120px;
            opacity: 0.9;
            filter: brightness(1.1) contrast(0.9);
        }
        .qr-esign-text {
            font-size: 16px;
            font-weight: bold;
            color: #0066cc;
        }
        .footer-bottom {
            display: table;
            width: 100%;
            margin-top: 6px;
        }
        .footer-bottom-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 5px;
            font-size: 12px;
        }
        .footer-bottom-center {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 5px;
            font-size: 12px;
            text-align: center;
        }
        .footer-address {
            text-align: center;
            font-size: 12px;
            margin-top: 6px;
            padding: 0;
            font-weight: bold;
            page-break-inside: avoid;
        }
        .footer-image {
            text-align: center;
            margin-top: 0px;
            page-break-inside: avoid;
            page-break-after: avoid;
        }
        .footer-image img {
            max-width: 100%;
            height: 90%;
            max-height: 150px;
        }
        .signature-row {
            margin-bottom: 2px;
            white-space: nowrap;
        }
        .signature-row strong {
            display: inline;
            margin-right: 5px;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    @php
        // Ensure Attn and Place of Supply are derived from application data everywhere this PDF is rendered.
        $applicationData = is_array($data ?? null) ? $data : ($application->application_data ?? []);

        $authorizedRepresentativeName =
            $applicationData['authorized_representative_details']['name'] ??
            $applicationData['representative']['name'] ??
            $applicationData['authorized_representative_name'] ??
            $applicationData['authorised_representative_name'] ??
            null;

        $attnName = $authorizedRepresentativeName ?: ($user->fullname ?? null);

        $selectedLocationName = $applicationData['location']['name'] ?? null;
        $selectedLocationState = $applicationData['location']['state'] ?? null;

        $placeOfSupply = null;
        if ($selectedLocationName && $selectedLocationState) {
            $placeOfSupply = $selectedLocationName.' ('.$selectedLocationState.')';
        } elseif ($selectedLocationName) {
            $placeOfSupply = $selectedLocationName;
        } elseif ($selectedLocationState) {
            $placeOfSupply = $selectedLocationState;
        }
    @endphp

    <!-- Header Section -->
    <div class="header-section">
        <div class="header-left">
            @php
                $logoPath = null;
                $logoMime = null;
                if (file_exists(public_path('images/nixi-logo.jpg'))) {
                    $logoPath = public_path('images/nixi-logo.jpg');
                    $logoMime = 'image/jpeg';
                } elseif (file_exists(public_path('images/nixi-logo.png'))) {
                    $logoPath = public_path('images/nixi-logo.png');
                    $logoMime = 'image/png';
                } elseif (file_exists(public_path('images/logo.jpg'))) {
                    $logoPath = public_path('images/logo.jpg');
                    $logoMime = 'image/jpeg';
                } elseif (file_exists(public_path('images/logo.png'))) {
                    $logoPath = public_path('images/logo.png');
                    $logoMime = 'image/png';
                }
                $logoBase64 = $logoPath ? 'data:' . $logoMime . ';base64,' . base64_encode(file_get_contents($logoPath)) : null;
            @endphp
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="NIXI" class="nixi-logo">
            @endif
            <div class="tax-invoice-title">{{ isset($isCreditNote) && $isCreditNote ? 'Credit Note' : 'Tax Invoice' }}</div>
        </div>
        <div class="header-right">
            ORIGINAL FOR RECEIPIENT
        </div>
    </div>

    <!-- Buyer and Seller Section -->
    <div class="buyer-seller-section">
        <div class="buyer-column">
            <div class="section-label">Buyer</div>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value">{{ $buyerDetails['company_name'] ?? $user->fullname ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Address:</span>
                <span class="detail-value">{{ $buyerDetails['address'] ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value">{{ $buyerDetails['phone'] ?? $user->mobile ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value">{{ $buyerDetails['email'] ?? $user->email ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">GSTIN/UIN:</span>
                @php
                    $buyerGstinValue = $buyerDetails['gstin'] ?? ($data['gstin'] ?? null);
                    $buyerGstinValue = is_string($buyerGstinValue) ? strtoupper(trim($buyerGstinValue)) : null;
                    $buyerGstinValid = $buyerGstinValue && preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $buyerGstinValue);
                @endphp
                <span class="detail-value">{{ ($invoice && $invoice->einvoice_irn && $buyerGstinValid) ? $buyerGstinValue : 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">PAN:</span>
                <span class="detail-value">{{ $buyerDetails['pan'] ?? $user->pancardno ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Attn:</span>
                <span class="detail-value">{{ $attnName ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Place of Supply:</span>
                <span class="detail-value">{{ $placeOfSupply ?? 'N/A' }}</span>
            </div>
        </div>
        <div class="seller-column">
            <div class="section-label">Seller</div>
            <div class="detail-row">
                <span class="detail-label">Name:</span>
                <span class="detail-value">National Internet Exchange of India</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">PAN:</span>
                <span class="detail-value">{{ $supplierPan ?? 'AABCN9308A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">CIN:</span>
                <span class="detail-value">U72900DL2003NPL120999</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">GSTIN:</span>
                <span class="detail-value">{{ $supplierGstin ?? '07AABCN9308A1ZT' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">HSN CODE:</span>
                <span class="detail-value">998319</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Category of Service:</span>
                <span class="detail-value">Other Information Technology Services N.E.C.</span>
            </div>
        </div>
    </div>

    <!-- Invoice Information Table -->
    <table class="invoice-info-table">
        <tr>
            <td>{{ isset($isCreditNote) && $isCreditNote ? 'Credit Note No:' : 'Invoice No:' }}</td>
            <td>{{ $invoiceNumber }}</td>
            <td>Customer Id:</td>
            <td>{{ $application->customer_id ?? $application->membership_id ?? $application->application_id ?? 'N/A' }}</td>
        </tr>
        @if(isset($isCreditNote) && $isCreditNote)
        <tr>
            <td>Credit Note Date (dd/mm/yyyy):</td>
            <td>{{ $invoiceDate }}</td>
            <td colspan="2"></td>
        </tr>
        @else
        <tr>
            <td>Invoice Date (dd/mm/yyyy):</td>
            <td>{{ $invoiceDate }}</td>
            <td>Invoice Due Date (dd/mm/yyyy):</td>
            <td>{{ $dueDate }}</td>
        </tr>
        @endif
    </table>

    <!-- Particulars Table -->
    @php
        // Get invoice amounts
        $amount = $invoice ? (float)$invoice->amount : 0;
        $gstAmount = $invoice ? (float)$invoice->gst_amount : 0;
        $tdsAmount = $invoice ? (float)($invoice->tds_amount ?? 0) : 0;
        $totalAmount = $invoice ? (float)$invoice->total_amount : 0;
        
        // Get billing period dates - Always recalculate based on billing cycle to ensure correctness
        $billingStartDate = null;
        $billingEndDate = null;
        $billingPeriodText = '';
        
        // Get billing cycle from application (prioritize database field over application_data)
        $billingCycle = strtolower(trim($application->billing_cycle ?? ($data['port_selection']['billing_plan'] ?? 'monthly')));
        
        // Normalize billing cycle values
        if (in_array($billingCycle, ['arc', 'annual'])) {
            $billingCycle = 'annual';
        } elseif (in_array($billingCycle, ['mrc', 'monthly'])) {
            $billingCycle = 'monthly';
        } elseif ($billingCycle === 'quarterly') {
            $billingCycle = 'quarterly';
        } else {
            $billingCycle = 'monthly'; // Default fallback
        }
        
        // Use stored billing dates from invoice if available
        if ($invoice && $invoice->billing_start_date && $invoice->billing_end_date) {
            // Parse dates as date-only to avoid timezone issues
            $startDateStr = is_string($invoice->billing_start_date) ? $invoice->billing_start_date : $invoice->billing_start_date->format('Y-m-d');
            $endDateStr = is_string($invoice->billing_end_date) ? $invoice->billing_end_date : $invoice->billing_end_date->format('Y-m-d');
            
            // Extract just the date part (Y-m-d) if it includes time
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $startDateStr, $startMatch)) {
                $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startMatch[1]);
            } else {
                $startDate = \Carbon\Carbon::parse($startDateStr);
            }
            
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $endDateStr, $endMatch)) {
                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endMatch[1]);
            } else {
                $endDate = \Carbon\Carbon::parse($endDateStr);
            }
            
            $billingPeriodText = $startDate->format('d/m/Y') . ' to ' . $endDate->format('d/m/Y');
        } elseif ($invoice && $invoice->invoice_date) {
            // Fallback: calculate from invoice date if billing dates not stored
            $startDate = null;
            
            // Check if this is the first invoice (no previous paid invoices)
            $lastPaidInvoice = \App\Models\Invoice::where('application_id', $application->id)
                ->where('status', 'paid')
                ->where('id', '<', $invoice->id)
                ->latest('invoice_date')
                ->first();
            
            if ($lastPaidInvoice && $lastPaidInvoice->due_date) {
                // Subsequent invoice: start from last invoice's due date
                $startDate = \Carbon\Carbon::parse($lastPaidInvoice->due_date);
            } elseif ($application->service_activation_date) {
                // First invoice: start from service activation date
                $startDate = \Carbon\Carbon::parse($application->service_activation_date);
            } else {
                // Fallback: use invoice date
                $startDate = \Carbon\Carbon::parse($invoice->invoice_date);
            }
            
            // Calculate end date based on billing cycle
            // Note: End date should be one day before the start of the next period
            switch ($billingCycle) {
                case 'annual':
                case 'arc':
                    // Annual: end date is one day before the same date next year
                    // Example: Jan 21, 2024 to Jan 20, 2025
                    $endDate = $startDate->copy()->addYear()->subDay();
                    break;
                case 'quarterly':
                    // Quarterly: end date is one day before the same date 3 months later
                    // Example: Jan 21, 2024 to Apr 20, 2024
                    $endDate = $startDate->copy()->addMonths(3)->subDay();
                    break;
                case 'monthly':
                case 'mrc':
                default:
                    // Monthly: end date is one day before the same day next month
                    // Example: Jan 21, 2024 to Feb 20, 2024
                    $endDate = $startDate->copy()->addMonth()->subDay();
                    break;
            }
            
            $billingPeriodText = $startDate->format('d/m/Y') . ' to ' . $endDate->format('d/m/Y');
        } else {
            // Fallback if no invoice dates
            $billingPeriodText = $invoiceDate . ' to ' . $dueDate;
        }
        
        // Line items (proration segments)
        $lineItems = $invoice->line_items ?? [];
        
        // Get port capacity
        $portCapacity = $application->assigned_port_capacity ?? ($data['port_selection']['capacity'] ?? 'N/A');
        
        // Format port capacity for display (e.g., "1000 Mbps")
        if (strpos($portCapacity, 'Gig') !== false) {
            $portCapacity = str_replace('Gig', ' Gbps', $portCapacity);
        } elseif (strpos($portCapacity, 'Mbps') === false && is_numeric(str_replace([' ', 'Mbps', 'Gbps'], '', $portCapacity))) {
            $portCapacity = $portCapacity . ' Mbps';
        }
        
        // Determine GST type (IGST vs CGST+SGST) by buyer GST state code vs seller (supplier) state code, not place of supply
        $supplierStateCode = '';
        $buyerStateCode = '';
        $isSameState = false;
        
        if (!empty($supplierGstin) && strlen($supplierGstin) >= 2) {
            $supplierStateCode = substr($supplierGstin, 0, 2);
        }
        $buyerGstinForState = $buyerDetails['gstin'] ?? ($data['gstin'] ?? '');
        if (!empty($buyerGstinForState) && strlen($buyerGstinForState) >= 2) {
            $buyerStateCode = substr($buyerGstinForState, 0, 2);
        }
        $isSameState = (!empty($supplierStateCode) && !empty($buyerStateCode) && $supplierStateCode === $buyerStateCode);
        
        // Split GST amount based on same-state logic (do NOT hardcode 18% so it supports configurable rates).
        // We trust the stored invoice->gst_amount for totals, and only split for display.
        $gstPercent = $amount > 0 ? round(($gstAmount / $amount) * 100, 2) : 0;
        if ($isSameState) {
            $cgstAmount = round($gstAmount / 2, 2);
            $sgstAmount = round($gstAmount - $cgstAmount, 2);
            $igstAmount = 0;
        } else {
            $cgstAmount = 0;
            $sgstAmount = 0;
            $igstAmount = $gstAmount;
        }
    @endphp

    <table class="particulars-table">
        <thead>
            <tr>
                <th>S.N.o.</th>
                <th>Particulars</th>
                <th>Quantity</th>
                <th>Peering Capacity</th>
                <th>Unit Charges</th>
                <th>Amount()</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $itemIndex = 1;
                // Extract adjustments and service items from line_items
                $adjustments = [];
                $prorationTotal = $amount;
                $upgradeAdjustmentTotal = 0;
                $downgradeAdjustmentTotal = 0;
                $adjustmentTotal = 0;
                $serviceItems = [];
                
                if (is_array($lineItems)) {
                    // Check if metadata exists (new format with adjustments)
                    if (isset($lineItems['_metadata'])) {
                        $metadata = $lineItems['_metadata'];
                        $adjustments = $metadata['adjustments'] ?? [];
                        $prorationTotal = $metadata['proration_total'] ?? $amount;
                        $upgradeAdjustmentTotal = $metadata['upgrade_adjustment_total'] ?? 0;
                        $downgradeAdjustmentTotal = $metadata['downgrade_adjustment_total'] ?? 0;
                        $adjustmentTotal = $metadata['adjustment_total'] ?? ($upgradeAdjustmentTotal - $downgradeAdjustmentTotal);
                        // Get service items (all items except _metadata)
                        // Adjustments are now included as regular line items with is_adjustment flag
                        $serviceItems = [];
                        foreach ($lineItems as $key => $item) {
                            if ($key !== '_metadata' && is_array($item)) {
                                $serviceItems[] = $item;
                            }
                        }
                    } else {
                        // Old format: all items are service segments
                        $serviceItems = is_array($lineItems) ? array_values($lineItems) : [];
                    }
                } else {
                    $serviceItems = [];
                }
                
                // Calculate GST on upgrade adjustments (using same state code comparison logic)
                // Note: $isSameState is already calculated above based on supplier and buyer state codes
                $upgradeAdjustmentGst = 0;
                if ($upgradeAdjustmentTotal > 0) {
                    if ($isSameState) {
                        // Same state: 9% CGST + 9% SGST = 18% total
                        $upgradeAdjustmentGst = round(($upgradeAdjustmentTotal * 18) / 100, 2);
                    } else {
                        // Different state: 18% IGST
                        $upgradeAdjustmentGst = round(($upgradeAdjustmentTotal * 18) / 100, 2);
                    }
                }
            @endphp
            
            {{-- Service/Port Charges Segments --}}
            @if(!empty($serviceItems) && is_array($serviceItems))
                @foreach($serviceItems as $item)
                    @if(is_array($item))
                        @if(isset($item['start']))
                            {{-- Old format: proration segments with start/end dates --}}
                            @php
                                // Get plan label, with fallback logic
                                $planLabel = $item['plan_label'] ?? null;
                                
                                // If plan_label not found, try to get from plan field
                                if (!$planLabel && isset($item['plan'])) {
                                    $plan = strtolower(trim($item['plan']));
                                    $planLabel = match ($plan) {
                                        'annual', 'arc' => 'Annual (ARC)',
                                        'quarterly' => 'Quarterly',
                                        'monthly', 'mrc' => 'Monthly (MRC)',
                                        default => ucfirst($plan),
                                    };
                                }
                                
                                // If still not found, try to extract from description
                                if (!$planLabel && isset($item['description'])) {
                                    $description = $item['description'];
                                    // Extract plan from description like "IRINN Service - 1Gig Port Capacity (Monthly (MRC))"
                                    if (preg_match('/\(([^)]+)\)/', $description, $matches)) {
                                        $extractedPlan = trim($matches[1]);
                                        // Check if it's already a label format
                                        if (stripos($extractedPlan, 'Monthly') !== false || stripos($extractedPlan, 'MRC') !== false) {
                                            $planLabel = $extractedPlan;
                                        } elseif (stripos($extractedPlan, 'Quarterly') !== false) {
                                            $planLabel = 'Quarterly';
                                        } elseif (stripos($extractedPlan, 'Annual') !== false || stripos($extractedPlan, 'ARC') !== false) {
                                            $planLabel = 'Annual (ARC)';
                                        } else {
                                            // Try to match the plan value
                                            $plan = strtolower(trim($extractedPlan));
                                            $planLabel = match ($plan) {
                                                'annual', 'arc' => 'Annual (ARC)',
                                                'quarterly' => 'Quarterly',
                                                'monthly', 'mrc' => 'Monthly (MRC)',
                                                default => ucfirst($extractedPlan),
                                            };
                                        }
                                    }
                                }
                                
                                $planLabel = $planLabel ?? 'N/A';
                            @endphp
                            <tr>
                                <td>{{ $itemIndex++ }}</td>
                                <td>
                                    Port Charges - {{ $item['capacity'] ?? 'N/A' }}<br>
                                    Plan: {{ $planLabel }}<br>
                                    @php
                                        // Check if show_period is set and true (default to false if not set - unchecked checkboxes don't submit a value)
                                        $showPeriod = isset($item['show_period']) ? (bool) $item['show_period'] : false;
                                    @endphp
                                    @if($showPeriod && isset($item['start']) && isset($item['end']))
                                        Period: {{ \Carbon\Carbon::parse($item['start'])->format('d/m/Y') }} to {{ \Carbon\Carbon::parse($item['end'])->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td>1</td>
                                <td>{{ $item['capacity'] ?? 'N/A' }}</td>
                                <td>{{ number_format($item['amount_full'] ?? 0, 2) }}</td>
                                <td>{{ number_format($item['amount_prorated'] ?? 0, 2) }}</td>
                            </tr>
                        @elseif(isset($item['description']))
                            {{-- New format: line items with description, quantity, rate, amount --}}
                            @php
                                // Check if this is an adjustment or carry forward
                                $isAdjustment = isset($item['is_adjustment']) && $item['is_adjustment'];
                                $isCarryForward = isset($item['is_carry_forward']) && $item['is_carry_forward'];
                                $isSuspension = isset($item['is_suspension']) && $item['is_suspension'];
                                
                                // Extract plan label and period from description
                                $itemPlanLabel = null;
                                $itemPeriodStart = null;
                                $itemPeriodEnd = null;
                                
                                if (isset($item['description'])) {
                                    $description = $item['description'];
                                    
                                    // Extract plan from description like "IRINN Service - 1Gig Port Capacity (Monthly (MRC))"
                                    // Find the outermost opening parenthesis that comes before "Period:" or at the end
                                    $periodPos = stripos($description, 'Period:');
                                    $searchEnd = $periodPos !== false ? $periodPos : strlen($description);
                                    
                                    // Find all opening parentheses before the period, then find the outermost one
                                    $openParens = [];
                                    for ($i = 0; $i < $searchEnd; $i++) {
                                        if ($description[$i] === '(') {
                                            $openParens[] = $i;
                                        }
                                    }
                                    
                                    // Find the outermost opening parenthesis (the first one that has a matching closing parenthesis before Period:)
                                    $planStart = -1;
                                    $planEnd = -1;
                                    
                                    foreach (array_reverse($openParens) as $openPos) {
                                        // Find the matching closing parenthesis
                                        $depth = 0;
                                        $closePos = -1;
                                        for ($i = $openPos; $i < $searchEnd; $i++) {
                                            if ($description[$i] === '(') {
                                                $depth++;
                                            } elseif ($description[$i] === ')') {
                                                $depth--;
                                                if ($depth === 0) {
                                                    $closePos = $i;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // If we found a matching closing parenthesis, check if this contains nested parentheses
                                        // (which indicates it's the outer one we want)
                                        if ($closePos > $openPos) {
                                            $innerContent = substr($description, $openPos + 1, $closePos - $openPos - 1);
                                            // If it contains another opening parenthesis, it's the outer one
                                            if (strpos($innerContent, '(') !== false) {
                                                $planStart = $openPos;
                                                $planEnd = $closePos;
                                                break;
                                            } elseif ($planStart === -1) {
                                                // If no outer one found yet, use this one as fallback
                                                $planStart = $openPos;
                                                $planEnd = $closePos;
                                            }
                                        }
                                    }
                                    
                                    if ($planStart !== -1 && $planEnd > $planStart) {
                                        // Extract the plan label (without the outer parentheses)
                                        $extractedPlan = substr($description, $planStart + 1, $planEnd - $planStart - 1);
                                        $extractedPlan = trim($extractedPlan);
                                        
                                        // Check if it's already a label format
                                        if (stripos($extractedPlan, 'Monthly') !== false || stripos($extractedPlan, 'MRC') !== false) {
                                            $itemPlanLabel = $extractedPlan;
                                        } elseif (stripos($extractedPlan, 'Quarterly') !== false) {
                                            $itemPlanLabel = 'Quarterly';
                                        } elseif (stripos($extractedPlan, 'Annual') !== false || stripos($extractedPlan, 'ARC') !== false) {
                                            $itemPlanLabel = 'Annual (ARC)';
                                        } else {
                                            // Try to match the plan value
                                            $plan = strtolower(trim($extractedPlan));
                                            $itemPlanLabel = match ($plan) {
                                                'annual', 'arc' => 'Annual (ARC)',
                                                'quarterly' => 'Quarterly',
                                                'monthly', 'mrc' => 'Monthly (MRC)',
                                                default => ucfirst($extractedPlan),
                                            };
                                        }
                                    }
                                    
                                    // Extract period from description like " - Period: 18/02/2026 to 17/03/2026"
                                    if (preg_match('/Period:\s*(\d{2}\/\d{2}\/\d{4})\s+to\s+(\d{2}\/\d{2}\/\d{4})/', $description, $periodMatches)) {
                                        $itemPeriodStart = $periodMatches[1];
                                        $itemPeriodEnd = $periodMatches[2];
                                    }
                                }
                                
                                // If period not found in description, use invoice billing dates
                                // The billing_end_date already has -1 day applied (same as monthly, quarterly, and annual)
                                if (!$itemPeriodStart && !$isAdjustment && !$isCarryForward && isset($invoice->billing_start_date) && isset($invoice->billing_end_date)) {
                                    // Parse dates as date-only (Y-m-d format) to avoid timezone issues
                                    $startDateStr = is_string($invoice->billing_start_date) ? $invoice->billing_start_date : $invoice->billing_start_date->format('Y-m-d');
                                    $endDateStr = is_string($invoice->billing_end_date) ? $invoice->billing_end_date : $invoice->billing_end_date->format('Y-m-d');
                                    
                                    // Extract just the date part (Y-m-d) if it includes time
                                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $startDateStr, $startMatch)) {
                                        $itemPeriodStart = \Carbon\Carbon::createFromFormat('Y-m-d', $startMatch[1])->format('d/m/Y');
                                    } else {
                                        $itemPeriodStart = \Carbon\Carbon::parse($startDateStr)->format('d/m/Y');
                                    }
                                    
                                    if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $endDateStr, $endMatch)) {
                                        $itemPeriodEnd = \Carbon\Carbon::createFromFormat('Y-m-d', $endMatch[1])->format('d/m/Y');
                                    } else {
                                        $itemPeriodEnd = \Carbon\Carbon::parse($endDateStr)->format('d/m/Y');
                                    }
                                }
                                
                                // Get capacity for this specific line item (only for service items)
                                $itemCapacity = null;
                                $itemCapacityDisplay = '-';

                                $isApplicationOrMembershipFee = !empty($item['is_application_fee']) || !empty($item['is_membership_fee'])
                                    || ($invoice && in_array($invoice->invoice_purpose ?? '', ['application', 'membership'], true));

                                if (!$isAdjustment && !$isCarryForward && !$isApplicationOrMembershipFee) {
                                    $itemCapacity = $item['capacity'] ?? null;

                                    // If capacity is not stored, try to extract from description
                                    if (!$itemCapacity && isset($item['description'])) {
                                        // Try to extract capacity from description like "IRINN Service - 3Gig Port Capacity"
                                        if (preg_match('/(\d+(?:\.\d+)?)\s*(?:Gig|Gbps|G)/i', $item['description'], $matches)) {
                                            $itemCapacity = $matches[1] . 'Gig';
                                        }
                                    }

                                    // Format capacity for display
                                    if ($itemCapacity) {
                                        if (strpos($itemCapacity, 'Gig') !== false) {
                                            $itemCapacityDisplay = str_replace('Gig', ' Gbps', $itemCapacity);
                                        } elseif (strpos($itemCapacity, 'Mbps') === false && is_numeric(str_replace([' ', 'Mbps', 'Gbps'], '', $itemCapacity))) {
                                            $itemCapacityDisplay = $itemCapacity . ' Mbps';
                                        } else {
                                            $itemCapacityDisplay = $itemCapacity;
                                        }
                                    } else {
                                        // Fallback to application's port capacity
                                        $itemCapacityDisplay = $portCapacity;
                                    }
                                }
                                
                                // Format amount for adjustments (can be negative)
                                $itemAmount = $item['amount'] ?? 0;
                                $displayAmount = $itemAmount;
                                if ($isAdjustment && $itemAmount < 0) {
                                    $displayAmount = abs($itemAmount);
                                }
                            @endphp
                            <tr>
                                <td>{{ $itemIndex++ }}</td>
                                <td>
                                    @php
                                        // Clean description - remove plan and period if we're displaying them separately
                                        $displayDescription = $item['description'] ?? 'Service Charge';
                                        if (!$isAdjustment && !$isCarryForward) {
                                            // Remove period from description first (e.g., " - Period: 18/02/2026 to 17/03/2026")
                                            $displayDescription = preg_replace('/\s*-\s*Period:\s*\d{2}\/\d{2}\/\d{4}\s+to\s+\d{2}\/\d{2}\/\d{4}/', '', $displayDescription);
                                            
                                            // Remove all plan label parentheses from description (handle nested and multiple sets)
                                            // Keep removing parentheses until none are left (in case there are multiple sets like "(Monthly ) (Monthly (MRC))")
                                            $maxIterations = 10; // Safety limit
                                            $iteration = 0;
                                            while (($lastOpenPos = strrpos($displayDescription, '(')) !== false && $iteration < $maxIterations) {
                                                $iteration++;
                                                // Find the matching closing parenthesis by counting nested ones
                                                $depth = 0;
                                                $removeStart = $lastOpenPos;
                                                $removeEnd = $lastOpenPos;
                                                for ($i = $lastOpenPos; $i < strlen($displayDescription); $i++) {
                                                    if ($displayDescription[$i] === '(') {
                                                        $depth++;
                                                    } elseif ($displayDescription[$i] === ')') {
                                                        $depth--;
                                                        if ($depth === 0) {
                                                            $removeEnd = $i;
                                                            break;
                                                        }
                                                    }
                                                }
                                                // Remove the matched parentheses section
                                                if ($removeEnd > $removeStart) {
                                                    $before = substr($displayDescription, 0, $removeStart);
                                                    $after = substr($displayDescription, $removeEnd + 1);
                                                    $displayDescription = trim($before . ' ' . $after);
                                                } else {
                                                    break; // No matching closing parenthesis found
                                                }
                                            }
                                            
                                            // Clean up any extra whitespace and trailing parentheses
                                            $displayDescription = preg_replace('/\s+/', ' ', $displayDescription); // Multiple spaces to single space
                                            $displayDescription = preg_replace('/\s*\)\s*/', '', $displayDescription); // Remove any orphaned closing parentheses
                                            $displayDescription = trim($displayDescription);
                                            
                                            // Add plan label back with proper formatting
                                            if ($itemPlanLabel) {
                                                $displayDescription .= ' (' . $itemPlanLabel . ')';
                                            }
                                        }
                                    @endphp
                                    {{ $displayDescription }}
                                    @php
                                        // Check if show_period is set and true (default to false if not set - unchecked checkboxes don't submit a value)
                                        $showPeriod = isset($item['show_period']) ? (bool) $item['show_period'] : false;
                                    @endphp
                                    @if(!$isAdjustment && !$isCarryForward && !$isSuspension && $itemPeriodStart && $itemPeriodEnd && $showPeriod)
                                        <br>Period: {{ $itemPeriodStart }} to {{ $itemPeriodEnd }}
                                    @endif
                                    @if($isCarryForward)
                                        <br><small style="font-style: italic;">(GST already included in this amount)</small>
                                    @endif
                                </td>
                                <td>{{ $isAdjustment || $isCarryForward || $isSuspension ? '-' : number_format($item['quantity'] ?? 1, 2) }}</td>
                                <td>{{ $isAdjustment || $isCarryForward || $isSuspension ? '-' : $itemCapacityDisplay }}</td>
                                <td>{{ $isAdjustment || $isCarryForward || $isSuspension ? '-' : number_format($item['rate'] ?? 0, 2) }}</td>
                                <td>
                                    @if($isAdjustment && $itemAmount < 0)
                                        <span style="color: #198754;">-{{ number_format($displayAmount, 2) }}</span>
                                    @else
                                        {{ number_format($displayAmount, 2) }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endif
                @endforeach
            @else
                <tr>
                    <td>{{ $itemIndex++ }}</td>
                    <td>Port Charges For {{ $billingPeriodText }}</td>
                    <td>1</td>
                    <td>{{ $portCapacity }}</td>
                    <td>{{ number_format($amount, 2) }}</td>
                    <td>{{ number_format($amount, 2) }}</td>
                </tr>
            @endif
            
            {{-- Subtotal Rows --}}
            @if(!empty($adjustments) && ($upgradeAdjustmentTotal > 0 || $downgradeAdjustmentTotal > 0))
                <tr style="background-color: #f8f9fa;">
                    <td colspan="4" class="text-right"><strong>Subtotal (Service Charges)</strong></td>
                    <td colspan="2" class="text-right"><strong>{{ number_format($prorationTotal, 2) }}</strong></td>
                </tr>
                @if($upgradeAdjustmentTotal > 0)
                <tr style="background-color: #fff3cd;">
                    <td colspan="4" class="text-right"><strong>Upgrade Adjustment (Additional Payment)</strong></td>
                    <td colspan="2" class="text-right"><strong>+{{ number_format($upgradeAdjustmentTotal, 2) }}</strong></td>
                </tr>
                @if($upgradeAdjustmentGst > 0)
                <tr style="background-color: #fff3cd;">
                    <td colspan="4" class="text-right"><strong>GST on Upgrade Adjustment (18%)</strong></td>
                    <td colspan="2" class="text-right"><strong>+{{ number_format($upgradeAdjustmentGst, 2) }}</strong></td>
                </tr>
                @endif
                @endif
                @if($downgradeAdjustmentTotal > 0)
                <tr style="background-color: #d1e7dd;">
                    <td colspan="4" class="text-right"><strong>Downgrade Adjustment (Credit - GST Already Paid)</strong></td>
                    <td colspan="2" class="text-right"><strong style="color: #198754;">-{{ number_format($downgradeAdjustmentTotal, 2) }}</strong></td>
                </tr>
                @endif
            @endif
            
            {{-- Carry Forward Subtotal --}}
            @php
                $carryForwardTotal = 0;
                if (is_array($serviceItems)) {
                    foreach ($serviceItems as $item) {
                        if (is_array($item) && isset($item['is_carry_forward']) && $item['is_carry_forward']) {
                            $carryForwardTotal += $item['amount'] ?? 0;
                        }
                    }
                }
            @endphp
            @if($carryForwardTotal > 0)
                <tr style="background-color: #f0f8ff;">
                    <td colspan="4" class="text-right"><strong>Subtotal (Service Charges + Adjustments)</strong></td>
                    <td colspan="2" class="text-right"><strong>{{ number_format($prorationTotal + $adjustmentTotal, 2) }}</strong></td>
                </tr>
                <tr style="background-color: #f0f8ff;">
                    <td colspan="4" class="text-right"><strong>Carry Forward from Previous Invoice(s) <small>(GST included)</small></strong></td>
                    <td colspan="2" class="text-right"><strong>{{ number_format($carryForwardTotal, 2) }}</strong></td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Amount Summary -->
    <div class="amount-summary">
        @if($isSameState)
            <div class="amount-row">
                <div class="amount-label">CGST({{ number_format($gstPercent / 2, 2) }}%):</div>
                <div class="amount-value">{{ number_format($cgstAmount, 2) }}</div>
            </div>
            <div class="amount-row">
                <div class="amount-label">SGST({{ number_format($gstPercent / 2, 2) }}%):</div>
                <div class="amount-value">{{ number_format($sgstAmount, 2) }}</div>
            </div>
        @else
            <div class="amount-row">
                <div class="amount-label">IGST({{ number_format($gstPercent, 2) }}%):</div>
                <div class="amount-value">{{ number_format($igstAmount, 2) }}</div>
            </div>
        @endif
        @if(isset($tdsAmount) && $tdsAmount > 0)
            <div class="amount-row">
                <div class="amount-label">TDS Amount:</div>
                <div class="amount-value">{{ number_format($tdsAmount, 2) }}</div>
            </div>
        @endif
        <div class="amount-row total-row">
            <div class="amount-label">Total Amount Due:</div>
            <div class="amount-value">{{ number_format($totalAmount, 2) }}</div>
        </div>
    </div>

    <!-- Amount in Words -->
    <div class="amount-in-words">
        @php
            $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
            $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

            if (!function_exists('convertToWords')) {
                function convertToWords($num, $ones, $tens) {
                    if ($num < 20) return $ones[$num];
                    if ($num < 100) return $tens[floor($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
                    if ($num < 1000) return $ones[floor($num / 100)] . ' Hundred' . ($num % 100 ? ' ' . convertToWords($num % 100, $ones, $tens) : '');
                    if ($num < 100000) return convertToWords(floor($num / 1000), $ones, $tens) . ' Thousand' . ($num % 1000 ? ' ' . convertToWords($num % 1000, $ones, $tens) : '');
                    if ($num < 10000000) return convertToWords(floor($num / 100000), $ones, $tens) . ' Lakh' . ($num % 100000 ? ' ' . convertToWords($num % 100000, $ones, $tens) : '');
                    return convertToWords(floor($num / 10000000), $ones, $tens) . ' Crore' . ($num % 10000000 ? ' ' . convertToWords($num % 10000000, $ones, $tens) : '');
                }
            }

            $amountInWords = '';
            if ($totalAmount > 0) {
                $amountInWords = convertToWords((int)$totalAmount, $ones, $tens);
                // Handle decimal part
                $decimalPart = round(($totalAmount - (int)$totalAmount) * 100);
                if ($decimalPart > 0) {
                    $amountInWords .= ' and ' . convertToWords($decimalPart, $ones, $tens) . ' Paise';
                }
            }
        @endphp
        <strong>Rupees: {{ ucwords($amountInWords ?: 'Zero') }} Only</strong>
    </div>

    <!-- Payment Instructions -->
    <div class="payment-section">
        <div class="payment-left">
            <div class="payment-box">
                <div class="payment-title">Please pay as per following instructions:</div>
                <div style="margin-bottom: 4px;">Online Payment/Internet Banking/Credit Card/Debit Card.</div>
                <div class="detail-row"><span class="detail-label">Bank Name:</span> AXIS Bank Ltd.</div>
                <div class="detail-row"><span class="detail-label">IFSC Code:</span> UTIB0000007</div>
                <div class="detail-row"><span class="detail-label">MICR No:</span> 110211002</div>
                <div class="detail-row"><span class="detail-label">Account Name:</span> National Internet Exchange of India.</div>
                <div class="detail-row"><span class="detail-label">Account Type:</span> Savings Bank Account</div>
                <div class="detail-row"><span class="detail-label">Account Number:</span> 922010006414634</div>
                <div class="detail-row"><span class="detail-label">Branch:</span> Statesman House, 148, Barakhamba Road, New Delhi-110001 (India)</div>
            </div>
        </div>
        <div class="payment-center">
        
            <div class="or-separator">OR</div>
            
        </div>
        <div class="payment-right">
            
            <div class="payment-box">
                <div style="margin-bottom: 4px;">Make Cheque/Online PG / D.D in Favour of</div>
                <div style="font-weight: bold; margin-bottom: 4px;">National Internet Exchange of India</div>
                <div style="margin-bottom: 4px;">Payable to New Delhi and deposit it in your nearest ICICI branch and acknowledge the payment detail to 'ixbilling@nixi.in'.</div>
                <div style="margin-top: 4px; font-weight: bold;">Online Payment:</div>
                <div style="margin-top: 2px;">Please login to your account at:</div>
                <div style="color: #0066cc; margin-top: 2px; font-weight: bold;">{{ url('/login') }}</div>
                <div style="margin-top: 3px;">Use your registered credentials to login and proceed with the payment.</div>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <div class="footer-section">
        <!-- Top Section: Terms & Conditions (Left) and eSign/QR Code (Right) -->
        <div class="footer-top">
            <div class="footer-top-left">
                <div class="terms-section">
                    <div class="terms-title">Terms & Conditions:-</div>
                    <ol class="terms-list">
                        <li>Please Note that the date of receipt of payment in NIXI Bank account shall be treated as the date of payment.</li>
                        <li>Payment should be made as per NIXI Exchange billing procedure.</li>
                        <li>Any dispute subject to jurisdiction under the "Delhi Courts only".</li>
                    </ol>
                </div>
                @php
                    // For credit notes, use credit note IRN and AckNo; otherwise use invoice IRN and AckNo
                    $irnNumber = (isset($isCreditNote) && $isCreditNote && $invoice->credit_note_irn) ? $invoice->credit_note_irn : ($invoice->einvoice_irn ?? null);
                    $ackNumber = (isset($isCreditNote) && $isCreditNote && $invoice->credit_note_ack_no) ? $invoice->credit_note_ack_no : ($invoice->einvoice_ack_no ?? null);
                @endphp
                @if($irnNumber)
                    <div class="signature-row" style="margin-top: 10px;"><strong>IRN Number:</strong> {{ $irnNumber }}</div>
                    @if($ackNumber)
                        <div class="signature-row"><strong>Acknowledge Number:</strong> {{ $ackNumber }}</div>
                    @endif
                @endif
            </div>
            <div class="footer-top-right">
                <div class="esign-section">
                    @php
                        // For credit notes, use credit note IRN and SignedQRCode; otherwise use invoice IRN
                        $irnForQR = (isset($isCreditNote) && $isCreditNote && $invoice->credit_note_irn) ? $invoice->credit_note_irn : ($invoice->einvoice_irn ?? null);
                        
                        // Get SignedQRCode (JWT token) - for credit notes, check credit_note_api_response
                        $signedQRCode = null;
                        if (isset($isCreditNote) && $isCreditNote && $invoice->credit_note_api_response) {
                            $creditNoteResponse = is_array($invoice->credit_note_api_response) ? $invoice->credit_note_api_response : json_decode($invoice->credit_note_api_response, true);
                            if (isset($creditNoteResponse['SignedQRCode'])) {
                                $signedQRCode = $creditNoteResponse['SignedQRCode'];
                            }
                        } elseif ($invoice->einvoice_signed_data && isset($invoice->einvoice_signed_data['SignedQRCode'])) {
                            $signedQRCode = $invoice->einvoice_signed_data['SignedQRCode'];
                        } elseif ($invoice->einvoice_response && isset($invoice->einvoice_response['SignedQRCode'])) {
                            $signedQRCode = $invoice->einvoice_response['SignedQRCode'];
                        }
                        
                        // Generate QR code image from the SignedQRCode JWT token
                        $qrCodeDataUri = null;
                        
                        // Check if QrCode facade is available
                        $qrCodeAvailable = class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode');
                        
                        if ($qrCodeAvailable && $irnForQR) {
                            if ($signedQRCode) {
                                // Try to generate QR code as SVG (lighter/better for PDF)
                                try {
                                    $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)
                                        ->errorCorrection('M')
                                        ->margin(1)
                                        ->generate($signedQRCode);
                                    $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
                                } catch (\Exception $e) {
                                    // Fallback: try with IRN if QR code generation fails
                                    if ($irnForQR) {
                                        try {
                                            $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)
                                                ->errorCorrection('M')
                                                ->margin(1)
                                                ->generate($irnForQR);
                                            $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
                                        } catch (\Exception $e3) {
                                            // QR code generation failed
                                        }
                                    }
                                }
                            } elseif ($irnForQR) {
                                // Fallback: Generate QR code from IRN if SignedQRCode not available
                                try {
                                    $qrCodeSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(120)
                                        ->errorCorrection('M')
                                        ->margin(1)
                                        ->generate($irnForQR);
                                    $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);
                                } catch (\Exception $e) {
                                    // QR code generation failed
                                }
                            }
                        }
                    @endphp
                    @if($irnForQR)
                        @php
                            // Get eSign image for valid GSTIN
                            $esignImageBase64 = null;
                            if (file_exists(public_path('images/esign-valid.png'))) {
                                $esignImagePath = public_path('images/esign-valid.png');
                                $esignImageData = file_get_contents($esignImagePath);
                                $esignImageBase64 = 'data:image/png;base64,' . base64_encode($esignImageData);
                            } elseif (file_exists(public_path('images/esign-valid.jpg'))) {
                                $esignImagePath = public_path('images/esign-valid.jpg');
                                $esignImageData = file_get_contents($esignImagePath);
                                $esignImageBase64 = 'data:image/jpeg;base64,' . base64_encode($esignImageData);
                            } elseif (file_exists(public_path('images/esign-valid.jpeg'))) {
                                $esignImagePath = public_path('images/esign-valid.jpeg');
                                $esignImageData = file_get_contents($esignImagePath);
                                $esignImageBase64 = 'data:image/jpeg;base64,' . base64_encode($esignImageData);
                            }
                        @endphp
                        @if($qrCodeDataUri)
                            <div class="qr-esign-wrapper">
                                <img src="{{ $qrCodeDataUri }}" alt="eSign QR Code" />
                                @if($esignImageBase64)
                                    <div style="margin-top: 5px;">
                                        <img src="{{ $esignImageBase64 }}" alt="eSign" style="max-width: 80px; height: auto;" />
                                    </div>
                                @else
                                    <div class="qr-esign-text">eSign</div>
                                @endif
                            </div>
                        @elseif($esignImageBase64)
                            {{-- Show eSign image even if QR code is not available --}}
                            <div style="margin-bottom: 5px;">
                                <img src="{{ $esignImageBase64 }}" alt="eSign" style="max-width: 80px; height: auto;" />
                            </div>
                        @endif
                        @php
                            // For credit notes, use credit note AckDate; otherwise use invoice AckDate
                            $ackDate = (isset($isCreditNote) && $isCreditNote && $invoice->credit_note_ack_date) 
                                ? $invoice->credit_note_ack_date->format('Y-m-d H:i:s') 
                                : ($invoice->einvoice_ack_date ?? null);
                        @endphp
                        @if($ackDate)
                            <div style="font-size: 8px;">Digitally Signed by NIC-IRP on: {{ $ackDate }}</div>
                        @else
                            <div style="font-size: 8px;">Digitally Signed by NIC-IRP on: {{ $invoice->created_at ? $invoice->created_at->format('Y-m-d\TH:i') : now('Asia/Kolkata')->format('Y-m-d\TH:i') }}</div>
                        @endif
                    @else
                        {{-- For inactive/cancelled GSTIN - show eSign image and date without QR code --}}
                        @php
                            $esignImageBase64 = null;
                            if (file_exists(public_path('images/esign-invalid.png'))) {
                                $esignImagePath = public_path('images/esign-invalid.png');
                                $esignImageData = file_get_contents($esignImagePath);
                                $esignImageBase64 = 'data:image/png;base64,' . base64_encode($esignImageData);
                            } elseif (file_exists(public_path('images/esign-invalid.jpg'))) {
                                $esignImagePath = public_path('images/esign-invalid.jpg');
                                $esignImageData = file_get_contents($esignImagePath);
                                $esignImageBase64 = 'data:image/jpeg;base64,' . base64_encode($esignImageData);
                            } elseif (file_exists(public_path('images/esign-invalid.jpeg'))) {
                                $esignImagePath = public_path('images/esign-invalid.jpeg');
                                $esignImageData = file_get_contents($esignImagePath);
                                $esignImageBase64 = 'data:image/jpeg;base64,' . base64_encode($esignImageData);
                            }
                        @endphp
                        @if($esignImageBase64)
                            <div style="margin-bottom: 5px;">
                                <img src="{{ $esignImageBase64 }}" alt="eSign" style="max-width: 80px; height: auto;" />
                            </div>
                        @endif
                        <div style="font-size: 8px; color: #7f8c8d;">
                            Digitally Signed on: {{ $invoice->created_at ? $invoice->created_at->format('Y-m-d H:i:s') : now('Asia/Kolkata')->format('Y-m-d H:i:s') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Section: IRN/Acknowledge (Left) -->
        
    </div>
    
    <!-- Organization Address at Bottom Center -->
    <div class="footer-address">
        {{ $supplierAddress ?? 'National Internet Exchange of India, B-901, 9th Floor Tower B, World Trade Centre Nauroji Nagar New Delhi-110029 India' }}
    </div>
    
    <!-- Footer Image -->
    @php
        $footerImageBase64 = null;
        if (file_exists(public_path('images/footer_img.jpeg'))) {
            $footerImagePath = public_path('images/footer_img.jpeg');
            $footerImageData = file_get_contents($footerImagePath);
            $footerImageBase64 = 'data:image/jpeg;base64,' . base64_encode($footerImageData);
        }
    @endphp
    @if($footerImageBase64)
        <div class="footer-image">
            <a href="https://www.nixi.in" target="_blank"><img  src="{{ $footerImageBase64 }}" alt="Footer Image" /></a>
        </div>
    @endif
</body>
</html>
