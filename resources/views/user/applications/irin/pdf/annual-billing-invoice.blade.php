<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 10mm 12mm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 10pt; color: #000; line-height: 1.35; margin: 0; }
        .title { font-weight: bold; font-size: 11pt; text-align: center; margin-bottom: 10px; }
        .sub { font-size: 9pt; text-align: center; margin-bottom: 12px; }
        table.grid { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.grid td, table.grid th { border: 1px solid #000; padding: 5px 6px; vertical-align: top; font-size: 9.5pt; }
        .no-border td { border: none; padding: 2px 0; }
        .bold { font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .amt-table { width: 55%; margin-left: auto; border-collapse: collapse; margin-top: 8px; }
        .amt-table td { padding: 4px 6px; border-bottom: 1px solid #999; font-size: 10pt; }
        .footer { margin-top: 14px; font-size: 8.5pt; text-align: center; }
        .pay-box { border: 1px solid #000; padding: 8px; margin-top: 10px; font-size: 9pt; }
    </style>
</head>
<body>
@php
    $id = $invoice->invoice_date instanceof \Carbon\Carbon ? $invoice->invoice_date : \Carbon\Carbon::parse($invoice->invoice_date);
    $dd = $invoice->due_date instanceof \Carbon\Carbon ? $invoice->due_date : \Carbon\Carbon::parse($invoice->due_date);
    $base = (float) ($meta['annual_base_before_discount'] ?? 0);
    $discPct = (float) ($meta['discount_percent'] ?? 0);
    $discAmt = (float) ($meta['discount_amount'] ?? 0);
    $after = (float) ($invoice->amount ?? 0);
    $igst = (float) ($invoice->gst_amount ?? 0);
    $total = (float) ($invoice->total_amount ?? 0);
    $totalInt = (int) round($total);
    $words = '';
    if (class_exists(\NumberFormatter::class)) {
        $fmt = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
        $words = ucfirst($fmt->format($totalInt));
    }
@endphp

<div class="title">TAX INVOICE [Annual Invoice for IPV4/IPV6 Resources (ORIGINAL FOR RECEIPENT)]</div>

<table class="grid">
    <tr>
        <td style="width:50%;">
            <span class="bold">BUYER:</span> {{ $buyerLegalName }}<br>
            {{ $buyerAddress }}<br>
            <span class="bold">Attn:</span> {{ $attnName }}<br>
            <span class="bold">GSTIN/UIN:</span> {{ $gstDisplay }}<br>
            <span class="bold">PAN:</span> {{ $panDisplay }}<br>
            <span class="bold">Place of Supply:</span> {{ $placeOfSupply }}<br>
            <span class="bold">Reverse Charge Applicable:</span> No
        </td>
        <td style="width:50%;">
            <span class="bold">SELLER :</span> NATIONAL INTERNET EXCHANGE OF INDIA<br>
            9th Floor, B-Wing, Statesman House, 148, Barakhamba Road, New Delhi-110001<br>
            <span class="bold">PAN :</span> AABCN9308A<br>
            <span class="bold">CIN :</span> U72900DL2003NPL120999<br>
            <span class="bold">GSTIN :</span> 07AABCN9308A1ZT<br>
            <span class="bold">HSN CODE :</span> 998319<br>
            <span class="bold">Category of Service :</span> Other Information Technology Services N.E.C.
        </td>
    </tr>
</table>

<table class="grid">
    <tr>
        <th>Invoice No.</th>
        <th>Account Name</th>
        <th>Invoice Date (dd/mm/yyyy)</th>
        <th>Due Date (dd/mm/yyyy)</th>
    </tr>
    <tr>
        <td class="center">{{ $invoice->invoice_number }}</td>
        <td class="center">{{ $accountShort }}</td>
        <td class="center">{{ $id->format('d/m/Y') }}</td>
        <td class="center">{{ $dd->format('d/m/Y') }}</td>
    </tr>
</table>

<table class="grid">
    <tr>
        <th style="width:75%;">Description</th>
        <th class="right" style="width:25%;">Amount (₹)</th>
    </tr>
    <tr>
        <td>
            Annual Renewal fee based on resources holding as on {{ $asOnFormatted }}.<br>
            <span class="bold">Total IPV4 Count:</span> {{ $ipv4Count }}<br>
            <span class="bold">Total IPV6 Count:</span> {{ $ipv6Count }}
        </td>
        <td class="right">{{ number_format($base, 0) }}</td>
    </tr>
    <tr>
        <td>Rebate on the base price ({{ number_format($discPct, 1) }}% special discount)</td>
        <td class="right">{{ number_format($discAmt, 0) }}</td>
    </tr>
    <tr>
        <td class="bold">Amount after Rebate</td>
        <td class="right bold">{{ number_format($after, 0) }}</td>
    </tr>
    <tr>
        <td>IGST ( 18% )</td>
        <td class="right">{{ number_format($igst, 0) }}</td>
    </tr>
    <tr>
        <td class="bold">Total Amount</td>
        <td class="right bold">{{ number_format($total, 0) }}</td>
    </tr>
</table>

<p style="margin: 8px 0; font-style: italic;"><strong>(Rupees: {{ $words !== '' ? $words : number_format($totalInt).' (see figures)' }} Only)</strong></p>

<div class="pay-box">
    <p class="bold" style="margin-top:0;">Pay online (recommended)</p>
    <p style="margin: 4px 0;">Log in to the IRINN portal with your registered email and password, open <strong>Invoices</strong>, then pay the pending amount for this invoice.</p>
    <p style="margin: 4px 0;"><strong>Login:</strong> {{ $portalLoginUrl }}<br>
    <strong>Invoices (after login):</strong> {{ $invoicesUrl }}</p>
    <p style="margin: 8px 0 4px;" class="bold">Online Payment / Internet Banking / Credit Card / Debit Card (via portal).</p>
    <p class="bold" style="margin-bottom:4px;">Bank details (direct transfer)</p>
    Bank Name:- HDFC Bank Ltd. | IFSC Code:- HDFC0000271<br>
    Account Type:- Current Account<br>
    Account Name:- National Internet Exchange of India.<br>
    Account Number:- 02712320001421 | Branch:- Kalkaji, New Delhi.-110019 (India)<br>
    <p style="margin-top:8px;"><strong>OR</strong></p>
    Make Cheque / D.D in Favour of "National Internet Exchange of India" payable to New Delhi and deposit it in your nearest HDFC branch and acknowledge the payment detail to "billing@irinn.in".<br>
    <strong>Note:</strong> In case of non-payment, Re-activation fee will be levied as per billing procedure.
</div>

<div style="margin-top: 12px; font-size: 9pt;">
    <span class="bold">Terms &amp; Conditions:-</span><br>
    1. Please Note that the date of receipt of payment in IRINN Bank account shall be treated as the date of payment.<br>
    2. Payment should be made as per IRINN billing procedure available at www.irinn.in<br>
    3. Any dispute subject to jurisdiction under the "Delhi Courts only".<br>
    *Secure your IP prefix with ROA (Route Origin Authorization). For more information regarding ROA, visit our website or mail at hostmaster@irinn.in
</div>

@if(filled($invoice->einvoice_irn ?? null))
    <p style="margin-top: 14px; font-size: 8.5pt;">
        <strong>IRN No-</strong> {{ $invoice->einvoice_irn }}
        @if(filled($invoice->einvoice_ack_no ?? null))
            | <strong>ACK. No-</strong> {{ $invoice->einvoice_ack_no }}
        @endif
        @if(filled($invoice->einvoice_ack_date ?? null))
            | <strong>ACK. DATE -</strong> {{ $invoice->einvoice_ack_date }}
        @endif
    </p>
@else
    <p style="margin-top: 14px; font-size: 8.5pt; color: #444;">
        @if(($gstDisplay ?? '') === 'NA')
            E-invoice (IRN/ACK) is not generated when billing GST is not provided (GST shown as NA).
        @else
            IRN/ACK will appear here when the e-invoice API returns successfully (check invoice record if missing).
        @endif
    </p>
@endif

<div class="footer">
    Indian Registry for Internet Names and Numbers<br>
    C/o National Internet Exchange of India<br>
    9th Floor, B-Wing, Statesman House, 148, Barakhamba Road, New Delhi-110001 India.<br>
    Phone: +91-11-48202020. Fax +91-11-48202013. Email: billing@irinn.in.
</div>
</body>
</html>
