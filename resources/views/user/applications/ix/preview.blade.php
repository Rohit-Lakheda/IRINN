@extends('user.layout')

@section('title', 'Preview IRINN Application')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="color:#1f2937;">Preview IRINN Application</h2>
            <p class="text-muted mb-0">Review your application details before final submission.</p>
        </div>
        <button type="button" onclick="history.back()" class="btn btn-outline-secondary">Back</button>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <!-- Personal Information -->
            <div class="mb-4">
                <h5 class="mb-3" style="color:#1e40af;">Personal Information</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <strong>Full Name:</strong> {{ $user->fullname }}
                    </div>
                    <div class="col-md-6">
                        <strong>Email:</strong> {{ $user->email }}
                    </div>
                    <div class="col-md-6">
                        <strong>Mobile:</strong> {{ $user->mobile }}
                    </div>
                    <div class="col-md-6">
                        <strong>PAN Card:</strong> {{ $user->pancardno }}
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            @if($gstVerification || $kyc)
            <div class="mb-4">
                <h5 class="mb-3" style="color:#1e40af;">Company/Business Information</h5>
                <div class="row g-3">
                    @if($gstVerification)
                    <div class="col-md-6">
                        <strong>Legal Name:</strong> {{ $gstVerification->legal_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>GSTIN:</strong> {{ $gstVerification->gstin ?? '—' }}
                    </div>
                    <div class="col-md-12">
                        <strong>Address:</strong> {{ $gstVerification->primary_address ?? '—' }}
                    </div>
                    @endif
                    @if($kyc)
                    <div class="col-md-6">
                        <strong>Contact Person:</strong> {{ $kyc->contact_name ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Contact Email:</strong> {{ $kyc->contact_email ?? '—' }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Application Details -->
            @php
                $data = $application->application_data;
            @endphp
            <div class="mb-4">
                <h5 class="mb-3" style="color:#1e40af;">Application Details</h5>
                <div class="row g-3">
                    @if(isset($data['member_type']))
                    <div class="col-md-6">
                        <strong>Member Type:</strong> {{ $data['member_type'] }}
                    </div>
                    @endif
                    @if(isset($data['location']))
                    <div class="col-md-6">
                        <strong>NIXI Location:</strong> {{ $data['location']['name'] ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Node Type:</strong> {{ ucfirst($data['location']['node_type'] ?? '—') }}
                    </div>
                    <div class="col-md-6">
                        <strong>State:</strong> {{ $data['location']['state'] ?? '—' }}
                    </div>
                    @endif
                    @if(isset($data['port_selection']))
                    <div class="col-md-6">
                        <strong>Port Capacity:</strong> {{ $data['port_selection']['capacity'] ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Billing Plan:</strong> {{ strtoupper($data['port_selection']['billing_plan'] ?? '—') }}
                    </div>
                    <div class="col-md-6">
                        <strong>Amount:</strong> ₹{{ number_format($data['port_selection']['amount'] ?? 0, 2) }}
                    </div>
                    @endif
                    @if(isset($data['ip_prefix']))
                    <div class="col-md-6">
                        <strong>IP Prefixes:</strong> {{ $data['ip_prefix']['count'] ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <strong>IP Prefix Source:</strong> {{ strtoupper($data['ip_prefix']['source'] ?? '—') }}
                    </div>
                    @endif
                    @if(isset($data['peering']))
                    <div class="col-md-6">
                        <strong>Pre-NIXI Connectivity:</strong> {{ ucfirst($data['peering']['pre_nixi_connectivity'] ?? '—') }}
                    </div>
                    <div class="col-md-6">
                        <strong>ASN Number:</strong> {{ $data['peering']['asn_number'] ?? '—' }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Documents -->
            @if(isset($data['documents']) && !empty($data['documents']))
            <div class="mb-4">
                <h5 class="mb-3" style="color:#1e40af;">Uploaded Documents</h5>
                <div class="row g-2">
                    @php
                        $documentNames = [
                            'agreement_file' => 'Signed Agreement',
                            'license_isp_file' => 'ISP License',
                            'license_vno_file' => 'VNO License',
                            'cdn_declaration_file' => 'CDN Declaration',
                            'general_declaration_file' => 'General Declaration',
                            'board_resolution_file' => 'Board Resolution',
                            'whois_details_file' => 'Whois Details',
                            'pan_document_file' => 'PAN Document',
                            'gstin_document_file' => 'GSTIN Document',
                            'msme_document_file' => 'MSME Certificate',
                            'incorporation_document_file' => 'Certificate of Incorporation',
                            'authorized_rep_document_file' => 'Authorized Representative Document',
                        ];
                    @endphp
                    @foreach($data['documents'] as $field => $path)
                    <div class="col-md-6">
                        <div class="border rounded p-2">
                            <strong>{{ $documentNames[$field] ?? $field }}:</strong>
                            <a href="{{ asset('storage/'.$path) }}" target="_blank" class="ms-2">View</a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('user.applications.ix.create') }}" class="btn btn-outline-secondary">Edit Application</a>
                <form action="{{ route('user.applications.ix.final-submit', $application->application_id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to submit this application? You will not be able to edit it after submission.');">Submit Application</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

