<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IRINN Application - {{ $application->application_id }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
            @bottom-right {
                content: "Page " counter(page) " of " counter(pages);
                font-family: DejaVu Sans, sans-serif;
                font-size: 9px;
                color: #000000;
            }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, 'Trebuchet MS', 'Lucida Sans Unicode', 'Lucida Grande', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #000000;
            background: #ffffff;
            padding: 0;
        }
        
        .application-container {
            background: #ffffff;
            padding: 0;
        }
        
        .header {
            text-align: center;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 2px solid #000000;
        }
        
        .nixi-logo {
            max-width: 100px;
            height: auto;
            margin-bottom: 8px;
        }
        
        .header h1 {
            font-size: 16px;
            font-weight: 700;
            color: #000000;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .header .meta {
            font-size: 9px;
            color: #000000;
            margin-top: 6px;
        }
        
        .section {
            margin-bottom: 16px;
            page-break-inside: avoid;
            background: #ffffff;
            padding: 12px 0;
            border: 1px solid #000000;
        }
        
        .section-title {
            background: #ffffff;
            color: #000000;
            padding: 8px 12px;
            font-weight: 700;
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #000000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            background: #ffffff;
        }
        
        table td {
            padding: 8px 10px;
            border: 1px solid #000000;
            vertical-align: top;
        }
        
        table td.label {
            background: #ffffff;
            font-weight: 700;
            color: #000000;
            width: 35%;
            border-right: 1px solid #000000;
        }
        
        table td.value {
            color: #000000;
            font-weight: 400;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #000000;
            text-align: center;
            font-size: 9px;
            color: #000000;
        }
    </style>
</head>
<body>
    <div class="application-container">
        <div class="header">
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
            <h1>IRINN Application</h1>
            <div class="meta">
                <strong>Application ID:</strong> {{ $application->application_id }} | 
                <strong>Generated:</strong> {{ now('Asia/Kolkata')->format('d M Y, h:i A') }}
            </div>
        </div>

        <!-- Personal Information -->
        <div class="section">
            <div class="section-title">Personal Information</div>
            <table>
                <tr>
                    <td class="label">Full Name</td>
                    <td class="value">{{ $user->fullname }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td class="value">{{ $user->email }}</td>
                </tr>
                <tr>
                    <td class="label">Mobile</td>
                    <td class="value">{{ $user->mobile }}</td>
                </tr>
                <tr>
                    <td class="label">PAN Card Number</td>
                    <td class="value">{{ $user->pancardno }}</td>
                </tr>
                <tr>
                    <td class="label">Date of Birth</td>
                    <td class="value">{{ $user->dateofbirth?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Registration ID</td>
                    <td class="value">{{ $user->registrationid }}</td>
                </tr>
            </table>
        </div>

        <!-- Company/Business Information -->
        @if($gstVerification || $kyc)
        <div class="section">
            <div class="section-title">Company/Business Information</div>
            <table>
                @if($gstVerification)
                <tr>
                    <td class="label">Legal Name</td>
                    <td class="value">{{ $gstVerification->legal_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Trade Name</td>
                    <td class="value">{{ $gstVerification->trade_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">GSTIN</td>
                    <td class="value">{{ $gstVerification->gstin ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">PAN</td>
                    <td class="value">{{ $gstVerification->pan ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">State</td>
                    <td class="value">{{ $gstVerification->state ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Primary Address</td>
                    <td class="value">{{ $gstVerification->primary_address ?? '—' }}</td>
                </tr>
                @endif
                @if($kyc)
                <tr>
                    <td class="label">UDYAM Number</td>
                    <td class="value">{{ $kyc->udyam_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">CIN</td>
                    <td class="value">{{ $kyc->cin ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Contact Person</td>
                    <td class="value">{{ $kyc->contact_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Contact Email</td>
                    <td class="value">{{ $kyc->contact_email ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Contact Mobile</td>
                    <td class="value">{{ $kyc->contact_mobile ?? '—' }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif

        <!-- Application Details -->
        <div class="section">
            <div class="section-title">Application Details</div>
            <table>
                @if(isset($data['member_type']))
                <tr>
                    <td class="label">Member Type</td>
                    <td class="value">{{ $data['member_type'] }}</td>
                </tr>
                @endif
                @if(isset($data['location']))
                <tr>
                    <td class="label">NIXI Location</td>
                    <td class="value">{{ $data['location']['name'] ?? '—' }} ({{ ucfirst($data['location']['node_type'] ?? '') }} - {{ $data['location']['state'] ?? '' }})</td>
                </tr>
                <tr>
                    <td class="label">Node Type</td>
                    <td class="value">{{ ucfirst($data['location']['node_type'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">State</td>
                    <td class="value">{{ $data['location']['state'] ?? '—' }}</td>
                </tr>
                @endif
                @if(isset($data['port_selection']))
                <tr>
                    <td class="label">Port Capacity</td>
                    <td class="value">{{ $data['port_selection']['capacity'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">Billing Plan</td>
                    <td class="value">{{ strtoupper($data['port_selection']['billing_plan'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">Amount</td>
                    <td class="value">&#8377;{{ number_format($data['port_selection']['amount'] ?? 0, 2) }}</td>
                </tr>
                @endif
                @if(isset($data['ip_prefix']))
                <tr>
                    <td class="label">Number of IP Prefixes</td>
                    <td class="value">{{ $data['ip_prefix']['count'] ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="label">IP Prefix Source</td>
                    <td class="value">{{ strtoupper($data['ip_prefix']['source'] ?? '—') }}</td>
                </tr>
                @if(isset($data['ip_prefix']['provider']))
                <tr>
                    <td class="label">IP Prefix Provider</td>
                    <td class="value">{{ $data['ip_prefix']['provider'] }}</td>
                </tr>
                @endif
                @endif
                @if(isset($data['peering']))
                <tr>
                    <td class="label">Pre-NIXI Peering Connectivity</td>
                    <td class="value">{{ ucfirst($data['peering']['pre_nixi_connectivity'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">ASN Number</td>
                    <td class="value">{{ $data['peering']['asn_number'] ?? '—' }}</td>
                </tr>
                @endif
                @if(isset($data['router_details']))
                @if($data['router_details']['height_u'] ?? null)
                <tr>
                    <td class="label">Router Height (U)</td>
                    <td class="value">{{ $data['router_details']['height_u'] }}</td>
                </tr>
                @endif
                @if($data['router_details']['make_model'] ?? null)
                <tr>
                    <td class="label">Router Make & Model</td>
                    <td class="value">{{ $data['router_details']['make_model'] }}</td>
                </tr>
                @endif
                @if($data['router_details']['serial_number'] ?? null)
                <tr>
                    <td class="label">Router Serial Number</td>
                    <td class="value">{{ $data['router_details']['serial_number'] }}</td>
                </tr>
                @endif
                @endif
            </table>
        </div>

        <!-- Payment Information -->
        @if(isset($data['payment']))
        <div class="section">
            <div class="section-title">Payment Information</div>
            <table>
                <tr>
                    <td class="label">Billing Plan</td>
                    <td class="value">{{ strtoupper($data['payment']['plan'] ?? '—') }}</td>
                </tr>
                <tr>
                    <td class="label">Amount</td>
                    <td class="value">&#8377;{{ number_format($data['payment']['amount'] ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Currency</td>
                    <td class="value">{{ $data['payment']['currency'] ?? 'INR' }}</td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value">{{ ucfirst($data['payment']['status'] ?? '—') }}</td>
                </tr>
            </table>
        </div>
        @endif

        <div class="footer">
            <p><strong>National Internet Exchange of India (NIXI)</strong></p>
            <p>This document is computer-generated and does not require a signature.</p>
        </div>
    </div>
</body>
</html>
