<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IRINN Agreement</title>
    <style>
        @page {
            margin: 2cm 1.5cm;
        }
        
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
            position: relative;
        }
        
        .logo-container {
            position: absolute;
            top: 0.3cm;
            left: 0.3cm;
            z-index: 1000;
            width: 80px;
            height: auto;
        }
        
        .logo-container img {
            width: 100%;
            height: auto;
            max-width: 80px;
            display: block;
            object-fit: contain;
        }
        
        .content-wrapper {
            position: relative;
            padding-top: 0;
        }
        
        .header-note {
            font-size: 11pt;
            margin-bottom: 15px;
            font-weight: bold;
            text-decoration: underline;
        }
        
        h1 {
            font-size: 16pt;
            text-align: center;
            text-decoration: underline;
            margin: 15px 0;
            font-weight: bold;
        }
        
        h2 {
            font-size: 13pt;
            font-weight: bold;
            text-decoration: underline;
            margin-top: 15px;
            margin-bottom: 8px;
        }
        
        p {
            margin-bottom: 8px;
            text-align: justify;
        }
        
        .agreement-date {
            margin-bottom: 12px;
        }
        
        .party-details {
            margin: 12px 0;
        }
        
        .party-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .whereas {
            margin: 12px 0;
            text-align: justify;
        }
        
        .definition-term {
            font-weight: bold;
        }
        
        .definition-content {
            margin-left: 0;
            margin-bottom: 10px;
        }
        
        ol, ul {
            margin-left: 20px;
            margin-bottom: 8px;
            padding-left: 20px;
        }
        
        li {
            margin-bottom: 6px;
            text-align: justify;
        }
        
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        
        .signature-block {
            width: 100%;
            margin-top: 25px;
        }
        
        .signature-left {
            width: 48%;
            float: left;
        }
        
        .signature-right {
            width: 48%;
            float: right;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 5px;
            font-size: 11pt;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }
        
        .signature-field {
            margin-top: 5px;
            min-height: 20px;
            font-size: 11pt;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        .prefilled-field {
            border-bottom: 1px solid #000;
            min-height: 20px;
            padding-bottom: 2px;
        }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="logo-container">
            @if(file_exists(public_path('images/nixi-logo.jpg')))
                <img src="{{ public_path('images/nixi-logo.jpg') }}" alt="NIXI Logo">
            @elseif(file_exists(public_path('images/nixi-logo.png')))
                <img src="{{ public_path('images/nixi-logo.png') }}" alt="NIXI Logo">
            @endif
        </div>
        
        <h1>IRINN Agreement</h1>
        
        <div class="agreement-date">
            <p>This Agreement is executed on this <strong>{{ $date ?? now('Asia/Kolkata')->format('d') }}</strong> day of <strong>{{ now('Asia/Kolkata')->format('F') }}</strong>, <strong>{{ now('Asia/Kolkata')->format('Y') }}</strong>.</p>
        </div>
        
        <p><strong>Between</strong></p>
        
        <div class="party-details">
            <div class="party-name">{{ $company_name ?? '[Company Name]' }}</div>
            <div>{{ $company_address ?? '[Company Address]' }}</div>
            <div>GSTIN: {{ $gstin ?? '[GSTIN]' }}</div>
            <div>PAN: {{ $pan ?? '[PAN]' }}</div>
            <p>hereinafter referred to as "Member"</p>
        </div>
        
        <p><strong>AND</strong></p>
        
        <div class="party-details">
            <p><strong>The Chief Executive Officer, National Internet Exchange of India,</strong></p>
            <p>B-901, 9th Floor Tower B, World Trade Centre, Nauroji Nagar, New Delhi-110029 India</p>
            <p>hereinafter referred to as "NIXI"</p>
        </div>
        
        <div class="whereas">
            <p><strong>WHEREAS</strong> NIXI operates the Indian Registry for Internet Names and Numbers (IRINN) for allocation and management of Internet Protocol (IP) addresses and Autonomous System Numbers (ASN) in India.</p>
            <p><strong>AND WHEREAS</strong> the Member is seeking allocation of IP addresses and/or ASN from IRINN.</p>
            <p><strong>NOW</strong> the Member and NIXI agree as follows:</p>
        </div>
        
        <h2>1. Definitions:</h2>
        
        <p class="definition-content"><span class="definition-term">"IP Address"</span> means Internet Protocol address, a numerical label assigned to each device connected to a computer network.</p>
        <p class="definition-content"><span class="definition-term">"IPv4"</span> means Internet Protocol version 4.</p>
        <p class="definition-content"><span class="definition-term">"IPv6"</span> means Internet Protocol version 6.</p>
        <p class="definition-content"><span class="definition-term">"ASN"</span> means Autonomous System Number, which is a globally unique identifier for Autonomous Systems.</p>
        <p class="definition-content"><span class="definition-term">"IRINN"</span> means Indian Registry for Internet Names and Numbers operated by NIXI.</p>
        <p class="definition-content"><span class="definition-term">"Member"</span> means the organization that has entered into this agreement with NIXI for allocation of IP resources.</p>
        
        <h2>2. Allocation of Resources:</h2>
        <p>NIXI agrees to allocate IP addresses and/or ASN to the Member subject to:</p>
        <ol type="a">
            <li>Member's compliance with IRINN policies and procedures.</li>
            <li>Member's payment of applicable fees as per IRINN fee schedule.</li>
            <li>Member's adherence to APNIC policies and guidelines.</li>
            <li>Member's proper justification and documentation for resource requirements.</li>
        </ol>
        
        <h2>3. Member's Obligations:</h2>
        <p>The Member agrees to:</p>
        <ol type="a">
            <li>Use the allocated resources solely for the purposes stated in the application.</li>
            <li>Maintain accurate records of resource utilization.</li>
            <li>Comply with all IRINN and APNIC policies and procedures.</li>
            <li>Make timely payments of all fees and charges.</li>
            <li>Provide accurate and up-to-date contact information.</li>
            <li>Report any changes in organizational structure or contact details promptly.</li>
        </ol>
        
        <h2>4. Fees and Payment:</h2>
        <p>The Member agrees to pay:</p>
        <ol type="a">
            <li>Application fee as applicable at the time of submission.</li>
            <li>Annual maintenance fees as per IRINN fee schedule.</li>
            <li>Any other charges as may be applicable from time to time.</li>
        </ol>
        <p>All fees are non-refundable and non-transferable.</p>
        
        <h2>5. Term and Termination:</h2>
        <p>This agreement shall remain in effect until terminated by either party in accordance with IRINN policies. NIXI reserves the right to revoke resource allocation in case of non-compliance with policies or non-payment of fees.</p>
        
        <h2>6. Limitation of Liability:</h2>
        <p>NIXI shall not be liable for any indirect, incidental, or consequential damages arising from the allocation or use of IP resources.</p>
        
        <h2>7. Governing Law:</h2>
        <p>This agreement shall be governed by the laws of India and subject to the jurisdiction of courts in New Delhi.</p>
        
        <h2>8. General Provisions:</h2>
        <ol type="a">
            <li>This agreement constitutes the entire understanding between the parties.</li>
            <li>Any modifications must be in writing and signed by both parties.</li>
            <li>If any provision is found to be invalid, the remaining provisions shall remain in effect.</li>
        </ol>
        
        <div class="signature-section clearfix">
            <div class="signature-left">
                <div class="signature-label">For Member:</div>
                <div class="signature-field prefilled-field">{{ $company_name ?? '[Company Name]' }}</div>
                <div class="signature-line">
                    <div class="signature-field">Authorized Signatory</div>
                    <div class="signature-field prefilled-field">{{ $authorized_name ?? '[Name]' }}</div>
                    <div class="signature-field">Designation: <span class="prefilled-field">{{ '[Designation]' }}</span></div>
                    <div class="signature-field">Email: <span class="prefilled-field">{{ $authorized_email ?? '[Email]' }}</span></div>
                    <div class="signature-field">Mobile: <span class="prefilled-field">{{ $authorized_mobile ?? '[Mobile]' }}</span></div>
                    <div class="signature-field">PAN: <span class="prefilled-field">{{ $authorized_pan ?? '[PAN]' }}</span></div>
                </div>
            </div>
            
            <div class="signature-right">
                <div class="signature-label">Witness:</div>
                <div class="signature-line">
                    <div class="signature-field">Name: <span class="prefilled-field">{{ '[Witness Name]' }}</span></div>
                    <div class="signature-field">Signature:</div>
                    <div class="signature-field" style="min-height: 50px;"></div>
                    <div class="signature-field">Date:</div>
                </div>
            </div>
        </div>
        
        <div class="signature-section" style="margin-top: 50px;">
            <div class="signature-label">For NIXI:</div>
            <div class="signature-line">
                <div class="signature-field">Chief Executive Officer</div>
                <div class="signature-field">National Internet Exchange of India</div>
            </div>
        </div>
    </div>
</body>
</html>
