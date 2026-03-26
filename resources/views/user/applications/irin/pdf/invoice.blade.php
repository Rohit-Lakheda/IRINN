<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $invoiceNumber }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        @page {
            size: A4;
            margin: 6mm;
        }
        body {
            font-family: 'Nunito', 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #2c3e50;
            padding: 6px;
            background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
        }
        .invoice-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 12px;
            border-radius: 8px 8px 0 0;
        }
        .invoice-header h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #2c3e50;
        }
        .invoice-header h2 {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 4px;
            color: #7f8c8d;
        }
        .nixi-logo {
            text-align: right;
            margin-bottom: 8px;
            font-size: 16px;
            font-weight: 700;
            color: #27ae60;
        }
        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
            font-size: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 0 5px;
        }
        .column:first-child {
            margin-right: 5px;
        }
        .column:last-child {
            margin-left: 5px;
        }
        .section-title {
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
            color: #2c3e50;
            padding-bottom: 5px;
            border-bottom: 2px solid #3498db;
        }
        .row {
            margin-bottom: 4px;
            font-size: 9px;
        }
        .label {
            font-weight: 700;
            display: inline-block;
            width: 100px;
            color: #34495e;
        }
        .value {
            display: inline-block;
            position: relative;
            top: -1px;
        }
        .invoice-details {
            margin-bottom: 12px;
        }
        .invoice-details table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .invoice-details td {
            padding: 6px;
            border: 1px solid #dee2e6;
            font-size: 9px;
            background: #ffffff;
            font-weight: 600;
        }
        .invoice-details td:first-child {
            font-weight: 700;
            width: 35%;
            background: #e9ecef;
        }
        .description-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .description-table th,
        .description-table td {
            border: 1px solid #dee2e6;
            padding: 7px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
        }
        .description-table th {
            background: linear-gradient(to bottom, #3498db, #2980b9);
            color: #ffffff;
            font-weight: 700;
        }
        .description-table td {
            background: #ffffff;
            font-weight: 600;
        }
        .text-right {
            text-align: right;
        }
        .amount-section {
            margin-top: 10px;
            font-size: 10px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 8px;
        }
        .amount-row {
            display: table;
            width: 100%;
            margin-bottom: 4px;
        }
        .amount-label {
            display: table-cell;
            width: 70%;
            text-align: right;
            padding-right: 8px;
            font-weight: 700;
            color: #2c3e50;
        }
        .amount-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            color: #27ae60;
            font-weight: 700;
        }
        .total-row {
            border-top: 3px solid #3498db;
            border-bottom: 3px solid #3498db;
            padding: 8px 0;
            font-weight: 700;
            font-size: 12px;
            background: #e8f4f8;
        }
        .irp-section {
            display: table;
            width: 100%;
            margin-top: 12px;
        }
        .irp-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 8px;
        }
        .irp-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 8px;
            text-align: center;
        }
        .irp-box {
            border: 2px solid #3498db;
            padding: 8px;
            font-size: 8px;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .irp-box .row {
            margin-bottom: 4px;
            font-weight: 600;
        }
        .bank-details {
            margin-top: 10px;
            display: table;
            width: 100%;
        }
        .bank-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 6px;
        }
        .bank-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-left: 6px;
        }
        .bank-box {
            border: 2px solid #27ae60;
            padding: 8px;
            font-size: 8px;
            border-radius: 8px;
            background: #f0f8f4;
        }
        .bank-box .row {
            font-weight: 600;
        }
        .terms {
            margin-top: 10px;
            font-size: 8px;
            line-height: 1.4;
            background: #fff9e6;
            padding: 8px;
            border-radius: 8px;
            border-left: 4px solid #f39c12;
        }
        .terms ol {
            margin-left: 20px;
        }
        .terms li {
            margin-bottom: 4px;
        }
        .qr-code {
            width: 80px;
            height: 80px;
            border: 2px solid #3498db;
            margin: 8px auto;
            display: block;
            border-radius: 8px;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="nixi-logo">
            <div>nixi</div>
            <div style="font-size: 9px; color: #7f8c8d;">Empowering Netizens</div>
        </div>

        <div class="invoice-header">
            <h1>Tax Invoice</h1>
            <h2>Annual Invoice for IPV4/IPV6 Resources (ORIGINAL FOR RECEIPENT)</h2>
        </div>

        <div class="two-column">
            <div class="column">
                <div class="section-title">BUYER</div>
                @php
                    $attnName =
                        $data['authorized_representative_details']['name'] ??
                        $data['representative']['name'] ??
                        $data['authorized_representative_name'] ??
                        $data['authorised_representative_name'] ??
                        $data['billing_affiliate_name'] ??
                        $companyDetails['legal_name'] ??
                        null;
                @endphp
                <div class="row"><span class="label">Name:</span> <span class="value">{{ $data['billing_affiliate_name'] ?? $companyDetails['legal_name'] ?? 'N/A' }}</span></div>
                <div class="row"><span class="label">Address:</span> <span class="value">{{ $data['billing_address'] ?? ($companyDetails['pradr']['addr'] ?? 'N/A') }}</span></div>
                <div class="row"><span class="label">Attn:</span> <span class="value">{{ $attnName ?? 'N/A' }}</span></div>
                <div class="row"><span class="label">GSTIN:</span> <span class="value">{{ $data['gstin'] ?? 'N/A' }}</span></div>
                <div class="row"><span class="label">PAN:</span> <span class="value">{{ $companyDetails['pan'] ?? $user->pancardno ?? 'N/A' }}</span></div>
            </div>
            <div class="column">
                <div class="section-title">SELLER</div>
                <div class="row"><span class="label">Name:</span> <span class="value">NATIONAL INTERNET EXCHANGE OF INDIA</span></div>
                <div class="row"><span class="label">Address:</span> <span class="value">B-901, 9TH FLOOR TOWER B, World Trade Centre, NAUROJI NAGAR, New Delhi, Delhi, 110029</span></div>
                <div class="row"><span class="label">PAN:</span> <span class="value">AABCN9308A</span></div>
                <div class="row"><span class="label">CIN:</span> <span class="value">U72900DL2003NPL120999</span></div>
                <div class="row"><span class="label">GSTIN:</span> <span class="value">07AABCN9308A1ZT</span></div>
                <div class="row"><span class="label">HSN:</span> <span class="value">998319</span></div>
                <div class="row"><span class="label">Category:</span> <span class="value">Other IT Services N.E.C</span></div>
            </div>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td>Invoice No.</td>
                    <td>{{ $invoiceNumber }}</td>
                    <td>Account Name</td>
                    <td>{{ strtoupper($data['account_name'] ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <td>Invoice Date</td>
                    <td>{{ $invoiceDate }}</td>
                    <td>Due Date</td>
                    <td>{{ $dueDate }}</td>
                </tr>
                <tr>
                    <td>Place of Supply</td>
                    <td>{{ $data['billing_state'] ?? ($companyDetails['state_info']['name'] ?? 'N/A') }}</td>
                    <td>Reverse Charge Applicable</td>
                    <td>NO</td>
                </tr>
            </table>
        </div>

        @php
            // total_fee already includes GST, so we need to extract base amount and GST
            $totalWithGst = $data['total_fee'] ?? 0;
            $gstPercentage = $data['gst_percentage'] ?? 18;
            
            // If we have stored breakdown, use it; otherwise calculate
            if (isset($data['max_amount']) && isset($data['gst_amount'])) {
                $baseAmount = $data['max_amount'];
                $gstAmount = $data['gst_amount'];
            } else {
                $baseAmount = $totalWithGst / (1 + ($gstPercentage / 100));
                $gstAmount = $totalWithGst - $baseAmount;
            }
        @endphp

        <table class="description-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Annual Renewal fee based on resources holding as on {{ now('Asia/Kolkata')->format('d F Y') }}.
                        @if($data['ipv4_selected'])
                            Total IPv4 Count: {{ $data['ipv4_size'] ?? 'N/A' }}
                        @endif
                        @if($data['ipv4_selected'] && $data['ipv6_selected'])
                            & 
                        @endif
                        @if($data['ipv6_selected'])
                            Total IPv6 Count: {{ $data['ipv6_size'] ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($baseAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Rebate on the base price (0% Special discount)</td>
                    <td class="text-right">0.00</td>
                </tr>
                <tr>
                    <td><strong>Amount after Rebate</strong></td>
                    <td class="text-right"><strong>{{ number_format($baseAmount, 2) }}</strong></td>
                </tr>
                <tr>
                    <td>IGST ({{ number_format($gstPercentage, 2) }}%)</td>
                    <td class="text-right">{{ number_format($gstAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Round(+-)</td>
                    <td class="text-right">{{ number_format($totalWithGst - round($totalWithGst), 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td><strong>Total Amount</strong></td>
                    <td class="text-right"><strong>{{ number_format(round($totalWithGst), 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

        <div class="amount-section">
            <div class="amount-row">
                <div class="amount-label">Total Amount in Words:</div>
                <div class="amount-value"></div>
            </div>
            <div style="text-align: right; font-style: italic; margin-top: 4px; font-size: 9px; font-weight: 600;">
                @php
                    $totalAmount = round($totalWithGst);
                    $amountInWords = '';
                    if ($totalAmount > 0) {
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
                        $amountInWords = convertToWords($totalAmount, $ones, $tens);
                    }
                @endphp
                (Rupees {{ ucwords($amountInWords ?: 'Zero') }} Only)
            </div>
        </div>

        <div class="irp-section">
            <div class="irp-left">
                <div class="irp-box">
                    <div class="section-title">IRP Acknowledgement</div>
                    <div class="row">1. ACK. DATE - {{ now('Asia/Kolkata')->format('Y-m-d H:i:s') }}</div>
                    <div class="row">2. ACK. No- {{ str_pad($application->id, 15, '0', STR_PAD_LEFT) }}</div>
                    <div class="row">3. IRN No- {{ substr(hash('sha256', $application->application_id . $invoiceNumber), 0, 20) }}</div>
                </div>
            </div>
            <div class="irp-right">
                <div class="qr-code"></div>
                <div style="margin-top: 8px; font-size: 8px;">
                    <div style="font-weight: 700; color: #2c3e50;">eSign</div>
                    <div style="margin-top: 3px; color: #7f8c8d;">
                        Digitally Signed by NIC-IRP on:{{ now('Asia/Kolkata')->format('Y-m-d H:i:s') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="bank-details">
            <div class="bank-left">
                <div class="bank-box">
                    <div class="section-title">Bank Details</div>
                    <div class="row"><span class="label">Account Name:</span> National Internet Exchange Of India</div>
                    <div class="row"><span class="label">Bank Name:</span> HDFC Bank</div>
                    <div class="row"><span class="label">Account Type:</span> Current</div>
                    <div class="row"><span class="label">Account Number:</span> 02712320001421</div>
                    <div class="row"><span class="label">IFSC Code:</span> HDFC0000271</div>
                    <div class="row"><span class="label">Branch:</span> Kalkaji, New Delhi - 110019 (India)</div>
                </div>
            </div>
            <div class="bank-right">
                <div class="bank-box">
                    <div class="section-title">Cheque / D.D</div>
                    <div style="font-size: 8px; line-height: 1.5;">
                        Please make payment via Cheque / D.D in favour of <strong>National Internet Exchange Of India</strong>, payable at New Delhi.<br><br>
                        Deposit at nearest HDFC branch and acknowledge payment detail to <strong>"billing@irinn.in"</strong>.<br><br>
                        <strong>Note:</strong> Non-payment will incur re-activation fee.
                    </div>
                </div>
            </div>
        </div>

        <div class="terms">
            <div class="section-title">Terms & Conditions</div>
            <ol>
                <li>Please Note that the date of receipt of payment in IRINN Bank account shall be treated as the date of payment.</li>
                <li>Payment should be made as per IRINN billing procedure available at www.irinn.in</li>
                <li>Any dispute subject to jurisdiction under the "Delhi Courts only".</li>
                <li>*Secure your IP prefix with ROA. For more information regarding ROA, visit our website or mail at hostmaster@irinn.in</li>
            </ol>
        </div>
    </div>
</body>
</html>
