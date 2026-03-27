@extends('admin.layout')

@php
    use Illuminate\Support\Carbon;
    use Illuminate\Support\Facades\Storage;

    $appData = $application->application_data ?? [];
    $registrationSnapshot = $application->registration_details ?? [];
    $kycSnapshot = $application->kyc_details ?? [];
    $authRep = $application->authorized_representative_details ?? [];

    $statusSteps = ['helpdesk', 'hostmaster', 'billing'];
    $statusSlug = strtolower(trim((string) ($application->status ?? '')));
    $currentStepIndex = array_search($statusSlug, $statusSteps, true);

    $formatDateTime = function ($value) {
        if (empty($value)) {
            return 'N/A';
        }
        try {
            return Carbon::parse($value)->format('d M Y, h:i A');
        } catch (\Throwable $e) {
            return is_scalar($value) ? (string) $value : 'N/A';
        }
    };

    $docParts = [
        'part3' => $appData['part3'] ?? [],
        'part4' => $appData['part4'] ?? [],
    ];
@endphp

@section('title', 'Application Details - Comprehensive')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1" style="color:#1e2a4a;font-weight:700;">Application Comprehensive View</h4>
            <p class="mb-0 text-muted">
                Application ID: <strong>{{ $application->application_id }}</strong> |
                Type: <strong>{{ $application->application_type ?? 'N/A' }}</strong>
            </p>
        </div>
        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-outline-primary btn-sm">
            Back to Summary View
        </a>
    </div>

    <div class="row g-3">
        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top" style="top:80px;border-radius:14px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;">
                    <strong>Quick Navigation</strong>
                </div>
                <div class="list-group list-group-flush">
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link active" data-target="section-app-info">Application Information</a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link" data-target="section-registration">Registration Details</a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link" data-target="section-kyc">KYC Details</a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link" data-target="section-application-data">IRINN Application Data</a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link" data-target="section-status">Application Status</a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link" data-target="section-gst">GST Change History</a>
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action comp-link" data-target="section-json">Raw Snapshot (Debug)</a>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-lg-9">
            <div id="section-app-info" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>Application Information</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><small class="text-muted d-block">Application ID</small><strong>{{ $application->application_id ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Application Type</small><strong>{{ $application->application_type ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Current Stage</small><strong>{{ $application->current_stage ?? $application->status_display ?? ucfirst($statusSlug ?: 'N/A') }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Created At</small><strong>{{ $formatDateTime($application->created_at ?? null) }}</strong></div>
                        <div class="col-md-12">
                            <small class="text-muted d-block">Registered User</small>
                            @if($application->registration)
                                <a href="{{ route('admin.users.show', $application->registration->id) }}" class="fw-bold text-decoration-none">
                                    {{ $application->registration->name ?? 'N/A' }}
                                </a>
                            @else
                                <strong>N/A</strong>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div id="section-registration" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;display:none;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>Registration Details</strong></div>
                <div class="card-body">
                    @php
                        $name = $registrationSnapshot['name'] ?? ($application->registration->name ?? 'N/A');
                        $email = $registrationSnapshot['email'] ?? ($application->registration->email ?? 'N/A');
                        $mobile = $registrationSnapshot['mobile'] ?? ($application->registration->mobile ?? 'N/A');
                        $pan = $registrationSnapshot['pan_no'] ?? ($application->registration->pan_no ?? 'N/A');
                    @endphp
                    <div class="row g-3">
                        <div class="col-md-6"><small class="text-muted d-block">Name</small><strong>{{ $name }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $email }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Mobile</small><strong>{{ $mobile }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">PAN</small><strong>{{ $pan }}</strong></div>
                    </div>
                </div>
            </div>

            <div id="section-kyc" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;display:none;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>KYC Details</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><small class="text-muted d-block">GSTIN</small><strong>{{ $kycSnapshot['gstin'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Legal Name</small><strong>{{ $kycSnapshot['legal_name'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Trade Name</small><strong>{{ $kycSnapshot['trade_name'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Affiliate Type</small><strong>{{ $kycSnapshot['affiliate_type'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-12">
                            <small class="text-muted d-block">Billing Address</small>
                            <strong>
                                @if(isset($kycSnapshot['billing_address']) && is_array($kycSnapshot['billing_address']))
                                    {{ implode(', ', array_filter($kycSnapshot['billing_address'])) }}
                                @else
                                    {{ $kycSnapshot['billing_address'] ?? 'N/A' }}
                                @endif
                            </strong>
                        </div>
                    </div>

                    <hr>
                    <h6 class="mb-2">Management Representative</h6>
                    <div class="row g-3 mb-2">
                        <div class="col-md-6"><small class="text-muted d-block">Name</small><strong>{{ $kycSnapshot['management_representative']['name'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $kycSnapshot['management_representative']['email'] ?? 'N/A' }}</strong></div>
                    </div>

                    <h6 class="mb-2">Authorized Representative / WHOIS</h6>
                    <div class="row g-3">
                        <div class="col-md-6"><small class="text-muted d-block">Name</small><strong>{{ $authRep['name'] ?? ($kycSnapshot['authorized_representative']['name'] ?? 'N/A') }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Email</small><strong>{{ $authRep['email'] ?? ($kycSnapshot['authorized_representative']['email'] ?? 'N/A') }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Mobile</small><strong>{{ $authRep['mobile'] ?? ($kycSnapshot['authorized_representative']['mobile'] ?? 'N/A') }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">DIN</small><strong>{{ $authRep['din_no'] ?? ($kycSnapshot['authorized_representative']['din_no'] ?? 'N/A') }}</strong></div>
                    </div>
                </div>
            </div>

            <div id="section-application-data" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;display:none;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>IRINN Application Data</strong></div>
                <div class="card-body">
                    <h6 class="mb-2">Part 1 - Application</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><small class="text-muted d-block">Affiliate Type</small><strong>{{ $appData['part1']['affiliate_type'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">IN Domain Required</small><strong>{{ $appData['part1']['in_domain_required'] ?? 'N/A' }}</strong></div>
                    </div>

                    <h6 class="mb-2">Part 2 - New Resources</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><small class="text-muted d-block">IPv4 Prefix</small><strong>{{ $appData['part2']['ipv4_prefix'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">IPv6 Prefix</small><strong>{{ $appData['part2']['ipv6_prefix'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-4"><small class="text-muted d-block">ASN Required</small><strong>{{ $appData['part2']['asn_required'] ?? 'N/A' }}</strong></div>
                    </div>

                    <h6 class="mb-2">Documents (from Part 3 / Part 4)</h6>
                    <div class="row g-2 mb-3">
                        @php $hasDoc = false; @endphp
                        @foreach($docParts as $partKey => $items)
                            @foreach($items as $docKey => $docVal)
                                @if(is_string($docVal) && trim($docVal) !== '')
                                    @php $hasDoc = true; @endphp
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1">
                                            <span class="small">{{ ucwords(str_replace('_', ' ', $docKey)) }}</span>
                                            <a class="btn btn-sm btn-outline-primary"
                                               target="_blank"
                                               href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $docKey]) }}">
                                                View
                                            </a>
                                        </div>
                                    </div>
                                @elseif(is_array($docVal))
                                    @foreach($docVal as $i => $entry)
                                        @if(is_string($entry) && trim($entry) !== '')
                                            @php $hasDoc = true; @endphp
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between align-items-center border rounded px-2 py-1">
                                                    <span class="small">{{ ucwords(str_replace('_', ' ', $docKey)) }} #{{ $i + 1 }}</span>
                                                    <a class="btn btn-sm btn-outline-primary"
                                                       target="_blank"
                                                       href="{{ route('admin.applications.document', ['id' => $application->id, 'doc' => $docKey, 'index' => $i]) }}">
                                                        View
                                                    </a>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                        @endforeach
                        @if(!$hasDoc)
                            <div class="col-12"><span class="text-muted">No uploaded documents found.</span></div>
                        @endif
                    </div>

                    <h6 class="mb-2">Part 5 - Payment</h6>
                    <div class="row g-3">
                        <div class="col-md-3"><small class="text-muted d-block">Application Fee</small><strong>{{ $appData['part5']['application_fee'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-3"><small class="text-muted d-block">GST %</small><strong>{{ $appData['part5']['gst_percentage'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-3"><small class="text-muted d-block">GST Amount</small><strong>{{ $appData['part5']['gst_amount'] ?? 'N/A' }}</strong></div>
                        <div class="col-md-3"><small class="text-muted d-block">Total</small><strong>{{ $appData['part5']['total_amount'] ?? 'N/A' }}</strong></div>
                    </div>
                </div>
            </div>

            <div id="section-status" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;display:none;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>Application Status</strong></div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
                        @foreach($statusSteps as $idx => $step)
                            @php
                                $completed = ($currentStepIndex !== false) && ($idx < $currentStepIndex);
                                $active = ($currentStepIndex !== false) && ($idx === $currentStepIndex);
                                $bg = $completed || $active ? '#198754' : '#ced4da';
                            @endphp
                            <div class="d-flex align-items-center flex-grow-1 mb-2">
                                <div class="rounded-circle text-white text-center fw-bold" style="width:30px;height:30px;line-height:30px;background:{{ $bg }};">
                                    {{ strtoupper(substr($step, 0, 1)) }}
                                </div>
                                <span class="ms-2 fw-semibold" style="color:#1e2a4a;">{{ ucfirst($step) }}</span>
                                @if($idx < 2)
                                    <div class="flex-grow-1 mx-2" style="height:2px;background:#d7dee8;"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <h6 class="mb-2">Application History</h6>
                    <div class="border rounded p-2">
                        @forelse($statusHistory ?? [] as $row)
                            <div class="border-bottom mb-2 pb-2 px-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="pe-3">
                                        <strong>{{ $row->status_display ?? ($row->status ?? 'N/A') }}</strong>
                                        @if(!empty($row->remarks))
                                            <div class="small text-muted">{{ $row->remarks }}</div>
                                        @endif
                                        <div class="small text-muted">By: {{ $row->changed_by ?? 'System' }}</div>
                                    </div>
                                    <small class="text-muted text-nowrap">{{ $formatDateTime($row->created_at ?? null) }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">No status history found.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div id="section-gst" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;display:none;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>GST Change History</strong></div>
                <div class="card-body">
                    @if(!empty($gstChangeHistory) && count($gstChangeHistory))
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Old GSTIN</th>
                                        <th>New GSTIN</th>
                                        <th>Old Name</th>
                                        <th>New Name</th>
                                        <th>Created At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($gstChangeHistory as $gst)
                                        <tr>
                                            <td>{{ $gst->old_gstin ?? 'N/A' }}</td>
                                            <td>{{ $gst->new_gstin ?? 'N/A' }}</td>
                                            <td>{{ $gst->old_company_name ?? 'N/A' }}</td>
                                            <td>{{ $gst->new_company_name ?? 'N/A' }}</td>
                                            <td>{{ $formatDateTime($gst->created_at ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <span class="text-muted">No GST change history found.</span>
                    @endif
                </div>
            </div>

            <div id="section-json" class="card shadow-sm border-0 mb-3 comp-section" style="border-radius:14px;display:none;">
                <div class="card-header theme-bg-blue text-white" style="border-radius:14px 14px 0 0;"><strong>Raw Snapshot (Debug)</strong></div>
                <div class="card-body">
                    <p class="small text-muted mb-2">This section helps ensure all captured details are visible, even if a formatted field is missing.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <small class="text-muted d-block">Registration Snapshot JSON</small>
                            <pre class="border rounded p-2 bg-light small mb-0">{{ json_encode($registrationSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">KYC Snapshot JSON</small>
                            <pre class="border rounded p-2 bg-light small mb-0">{{ json_encode($kycSnapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Application Data JSON</small>
                            <pre class="border rounded p-2 bg-light small mb-0">{{ json_encode($appData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .comp-link.active {
        background: #e8f0ff;
        color: #17366d;
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('.comp-link');
    const sections = document.querySelectorAll('.comp-section');

    const showSection = function (targetId) {
        sections.forEach(function (sec) {
            sec.style.display = sec.id === targetId ? 'block' : 'none';
        });
    };

    links.forEach(function (link) {
        link.addEventListener('click', function () {
            const target = this.getAttribute('data-target');
            links.forEach(function (l) { l.classList.remove('active'); });
            this.classList.add('active');
            showSection(target);
        });
    });

    showSection('section-app-info');
});
</script>
@endpush

