<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>IRINN Application - {{ $application->application_id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: 'Nunito', 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #2c3e50;
            margin: 0;
            padding: 8px;
            background: linear-gradient(to bottom, #e8f4f8 0%, #ffffff 100%);
        }
        .application-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 15px;
            border-radius: 8px 8px 0 0;
        }
        .header h1 {
            margin: 0;
            color: #e74c3c;
            font-size: 24px;
            font-weight: 700;
        }
        .header p {
            margin: 5px 0;
            font-size: 11px;
            color: #7f8c8d;
        }
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            border-left: 4px solid #3498db;
        }
        .section-title {
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            padding: 10px 15px;
            font-weight: 700;
            margin: -12px -12px 10px -12px;
            font-size: 13px;
            border-radius: 8px 8px 0 0;
        }
        .row {
            display: table;
            width: 100%;
            margin-bottom: 6px;
            font-size: 11px;
        }
        .label {
            display: table-cell;
            width: 35%;
            font-weight: 600;
            vertical-align: top;
            color: #34495e;
        }
        .value {
            display: table-cell;
            width: 65%;
            color: #2c3e50;
        }
        .two-column {
            display: table;
            width: 100%;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table th, table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        table th {
            background: linear-gradient(to bottom, #e74c3c, #c0392b);
            color: white;
            font-weight: 700;
        }
        table td {
            background: #ffffff;
        }
        .document-section {
            page-break-before: always;
            margin-top: 20px;
            background: #ffffff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .document-title {
            background: linear-gradient(to right, #e74c3c, #c0392b);
            color: white;
            padding: 12px 15px;
            font-weight: 700;
            margin: -15px -15px 15px -15px;
            font-size: 14px;
            border-radius: 12px 12px 0 0;
        }
        .document-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 10px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            page-break-inside: avoid;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .document-page {
            page-break-after: always;
        }
        .document-page:last-child {
            page-break-after: auto;
        }
        .pdf-placeholder {
            padding: 40px;
            text-align: center;
            border: 3px dashed #3498db;
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f8ff 100%);
            border-radius: 12px;
            margin: 15px 0;
        }
        .pdf-placeholder-icon {
            font-size: 48px;
            color: #3498db;
            margin-bottom: 15px;
        }
        .pdf-placeholder-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .pdf-placeholder-text {
            font-size: 11px;
            color: #7f8c8d;
            margin: 5px 0;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #dee2e6;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="application-container">
        <div class="header">
            <h1>IRINN Application Form</h1>
            <p><strong>Application ID:</strong> {{ $application->application_id }}</p>
            <p><strong>Date:</strong> {{ $application->submitted_at->format('d/m/Y h:i A') }}</p>
        </div>

        <!-- Company Details -->
        <div class="section">
            <div class="section-title">1. Company Details</div>
            <div class="row">
                <div class="label">GSTIN:</div>
                <div class="value">{{ $data['gstin'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Legal Name:</div>
                <div class="value">{{ $companyDetails['legal_name'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Trade Name:</div>
                <div class="value">{{ $companyDetails['trade_name'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">PAN:</div>
                <div class="value">{{ $companyDetails['pan'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">State:</div>
                <div class="value">{{ $companyDetails['state'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">UDYAM Number:</div>
                <div class="value">{{ $data['udyam_number'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">MCA TAN:</div>
                <div class="value">{{ $data['mca_tan'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Industry Type:</div>
                <div class="value">{{ $data['industry_type'] ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Technical Person Details -->
        <div class="section">
            <div class="section-title">2. Technical Person Details</div>
            <div class="row">
                <div class="label">Name:</div>
                <div class="value">{{ $data['mr_name'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Email:</div>
                <div class="value">{{ $data['mr_email'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Mobile:</div>
                <div class="value">{{ $data['mr_mobile'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Designation:</div>
                <div class="value">{{ $data['mr_designation'] ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- IRINN Specific Details -->
        <div class="section">
            <div class="section-title">3. IRINN Specific Details</div>
            <div class="row">
                <div class="label">Account Name:</div>
                <div class="value">{{ $data['account_name'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Dot in Domain Required:</div>
                <div class="value">{{ $data['dot_in_domain_required'] ? 'Yes' : 'No' }}</div>
            </div>
        </div>

        <!-- Billing Details -->
        <div class="section">
            <div class="section-title">4. Billing Details</div>
            <div class="row">
                <div class="label">Affiliate Name:</div>
                <div class="value">{{ $data['billing_affiliate_name'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Email:</div>
                <div class="value">{{ $data['billing_email'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Address:</div>
                <div class="value">{{ $data['billing_address'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">City:</div>
                <div class="value">{{ $data['billing_city'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">State:</div>
                <div class="value">{{ $data['billing_state'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Postal Code:</div>
                <div class="value">{{ $data['billing_postal_code'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Mobile:</div>
                <div class="value">{{ $data['billing_mobile'] ?? 'N/A' }}</div>
            </div>
        </div>

        <!-- Resource Requirements -->
        <div class="section">
            <div class="section-title">5. Resource Requirements</div>
            <div class="row">
                <div class="label">IPv4 Selected:</div>
                <div class="value">{{ $data['ipv4_selected'] ? 'Yes' : 'No' }}</div>
            </div>
            @if($data['ipv4_selected'])
            <div class="row">
                <div class="label">IPv4 Size:</div>
                <div class="value">{{ $data['ipv4_size'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">IPv4 Fee:</div>
                <div class="value">&#8377;{{ number_format($data['ipv4_fee'] ?? 0, 2) }}</div>
            </div>
            @endif
            <div class="row">
                <div class="label">IPv6 Selected:</div>
                <div class="value">{{ $data['ipv6_selected'] ? 'Yes' : 'No' }}</div>
            </div>
            @if($data['ipv6_selected'])
            <div class="row">
                <div class="label">IPv6 Size:</div>
                <div class="value">{{ $data['ipv6_size'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">IPv6 Fee:</div>
                <div class="value">&#8377;{{ number_format($data['ipv6_fee'] ?? 0, 2) }}</div>
            </div>
            @endif
            <div class="row">
                <div class="label">Total Fee:</div>
                <div class="value"><strong>&#8377;{{ number_format($data['total_fee'] ?? 0, 2) }}</strong></div>
            </div>
        </div>

        <!-- Business & Network Details -->
        <div class="section">
            <div class="section-title">6. Business & Network Details</div>
            <div class="row">
                <div class="label">Nature of Business:</div>
                <div class="value">{{ $data['nature_of_business'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">AS Number Required:</div>
                <div class="value">{{ $data['as_number_required'] ? 'Yes' : 'No' }}</div>
            </div>
            @if(!$data['as_number_required'])
            <div class="row">
                <div class="label">Company ASN:</div>
                <div class="value">{{ $data['company_asn'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">ISP Company Name:</div>
                <div class="value">{{ $data['isp_company_name'] ?? 'N/A' }}</div>
            </div>
            @else
            <div class="row">
                <div class="label">Upstream Provider Name:</div>
                <div class="value">{{ $data['upstream_name'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Upstream Provider Mobile:</div>
                <div class="value">{{ $data['upstream_mobile'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Upstream Provider Email:</div>
                <div class="value">{{ $data['upstream_email'] ?? 'N/A' }}</div>
            </div>
            <div class="row">
                <div class="label">Upstream ASN:</div>
                <div class="value">{{ $data['upstream_asn'] ?? 'N/A' }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Uploaded Documents -->
    @if(isset($data['files']) && !empty($data['files']))
        @php
            $documentNames = [
                'network_plan_file' => 'Network Diagram',
                'payment_receipts_file' => 'Payment Receipt Invoices',
                'equipment_details_file' => 'Equipment Details with Invoice',
                'kyc_partnership_deed' => 'Partnership Deed',
                'kyc_partnership_entity_doc' => 'Partnership Entity Document',
                'kyc_incorporation_cert' => 'Incorporation Certificate',
                'kyc_company_pan_gstin' => 'Company PAN/GSTIN',
                'kyc_udyam_cert' => 'UDYAM Certificate',
                'kyc_sole_proprietorship_doc' => 'Sole Proprietorship Document',
                'kyc_establishment_reg' => 'Establishment Registration',
                'kyc_school_pan_gstin' => 'School PAN/GSTIN',
                'kyc_rbi_license' => 'RBI License',
                'kyc_bank_pan_gstin' => 'Bank PAN/GSTIN',
                'kyc_business_address_proof' => 'Business Address Proof',
                'kyc_authorization_doc' => 'Authorization Document',
                'kyc_signature_proof' => 'Signature Proof',
                'kyc_gst_certificate' => 'GST Certificate',
            ];
        @endphp
        @foreach($data['files'] as $field => $path)
            @php
                $fullPath = storage_path('app/public/' . $path);
                $documentName = $documentNames[$field] ?? ucwords(str_replace('_', ' ', $field));
                $fileExtension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            @endphp
            @if(file_exists($fullPath))
                <div class="document-section document-page">
                    <div class="document-title">{{ $documentName }}</div>
                    @if(in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif']))
                        @php
                            $imageData = base64_encode(file_get_contents($fullPath));
                            $imageSrc = 'data:image/' . $fileExtension . ';base64,' . $imageData;
                        @endphp
                        <img src="{{ $imageSrc }}" class="document-image" alt="{{ $documentName }}" style="max-height: 700px; object-fit: contain;">
                    @elseif($fileExtension === 'pdf')
                        @php
                            $pdfImage = $pdfImages[$field] ?? null;
                        @endphp
                        @if($pdfImage)
                            <img src="data:image/png;base64,{{ $pdfImage }}" class="document-image" alt="{{ $documentName }}" style="max-height: 700px; object-fit: contain;">
                            <div style="text-align: center; margin-top: 10px; font-size: 10px; color: #7f8c8d; font-style: italic; background: #f8f9fa; padding: 8px; border-radius: 6px;">
                                <strong>Page 1</strong> of {{ $documentName }} - Additional pages available in original document
                            </div>
                        @else
                            <div class="pdf-placeholder">
                                <div class="pdf-placeholder-icon">📄</div>
                                <div class="pdf-placeholder-title">{{ $documentName }}</div>
                                <div class="pdf-placeholder-text">PDF Document</div>
                                <div class="pdf-placeholder-text" style="font-weight: 600; color: #2c3e50; margin-top: 10px;">File: {{ basename($path) }}</div>
                                <div class="pdf-placeholder-text" style="margin-top: 15px; font-size: 10px;">
                                    This PDF document is attached to the application and can be accessed separately from your dashboard.
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="pdf-placeholder">
                            <div class="pdf-placeholder-icon">📎</div>
                            <div class="pdf-placeholder-title">{{ $documentName }}</div>
                            <div class="pdf-placeholder-text">File: {{ basename($path) }}</div>
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    @endif

    <div class="footer">
        <p>This is a system-generated document. Application ID: {{ $application->application_id }}</p>
        <p>Generated on: {{ now('Asia/Kolkata')->format('d/m/Y h:i A') }}</p>
    </div>
</body>
</html>
