@extends('user.layout')

@section('title', 'Preview IRINN Application')

@push('styles')
<style>
    .irin-preview-header h2{
        color:#1e3a8a;
        font-weight:700;
        letter-spacing:-0.02em;
    }
    .irin-preview-subtext{ color:#64748b; }
    .irin-preview-card{
        background:#fff;
        border:1px solid rgba(124,58,237,0.16);
        border-radius:16px;
        box-shadow:0 6px 20px rgba(124,58,237,0.06);
    }
    .irin-preview-card .card-header{
        background:#6b46c1;
        color:#fff;
        border-radius:16px 16px 0 0;
        padding:14px 18px;
        border-bottom:0;
    }
    .irin-preview-kv{
        display:flex;
        justify-content:space-between;
        gap:12px;
        padding:10px 0;
        border-bottom:1px dashed rgba(30,58,138,0.12);
    }
    .irin-preview-kv:last-child{ border-bottom:0; }
    .irin-preview-kv .k{ color:#64748b; font-weight:600; }
    .irin-preview-kv .v{ color:#0f172a; font-weight:600; text-align:right; }
    .irin-doc-row{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        padding:10px 0;
        border-bottom:1px dashed rgba(30,58,138,0.12);
    }
    .irin-doc-row:last-child{ border-bottom:0; }
    .irin-doc-title{ color:#0f172a; font-weight:700; }
    .irin-badge{
        display:inline-flex;
        align-items:center;
        gap:6px;
        padding:4px 10px;
        border-radius:999px;
        font-weight:700;
        font-size:0.78rem;
        border:1px solid transparent;
    }
    .irin-badge.ok{ background:rgba(34,197,94,0.10); color:#166534; border-color:rgba(34,197,94,0.20); }
    .irin-badge.no{ background:rgba(239,68,68,0.10); color:#991b1b; border-color:rgba(239,68,68,0.20); }
    .irin-btn-primary{
        background:#6b46c1;
        border-color:#6b46c1;
        color:#fff;
        font-weight:700;
    }
    .irin-btn-primary:hover{ background:#5b21b6; border-color:#5b21b6; color:#fff; }
    .irin-btn-outline{
        border:1px solid rgba(124,58,237,0.35);
        color:#5b21b6;
        font-weight:700;
        background:#fff;
    }
    .irin-btn-outline:hover{
        background:rgba(124,58,237,0.06);
        color:#4c1d95;
    }
    .irin-btn-view{
        border:1px solid rgba(30,58,138,0.22);
        color:#1e3a8a;
        background:#fff;
        font-weight:700;
    }
    .irin-btn-view:hover{ background:rgba(30,58,138,0.06); color:#1e3a8a; }
    .irin-sticky-actions{
        position:sticky;
        bottom:0;
        z-index:10;
        background:linear-gradient(180deg, rgba(248,250,252,0.70), rgba(248,250,252,1));
        padding:14px 0;
        border-top:1px solid rgba(30,58,138,0.08);
        backdrop-filter: blur(8px);
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-4 irin-preview-header">
        <div>
            <h2 class="mb-1">Preview IRINN Application</h2>
            <p class="irin-preview-subtext mb-0">Review the details and documents. You can edit any step or submit to proceed to payment.</p>
        </div>
        <a href="{{ route('user.irinn.create') }}?from_preview=1" class="btn irin-btn-outline">Edit Application</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card irin-preview-card mb-3">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="fw-bold">Part 1: Application</div>
                        <div class="small opacity-75">IRINN</div>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="irin-preview-kv">
                        <div class="k">Affiliate Type</div>
                        <div class="v">{{ ucfirst($previewData['part1']['affiliate_type'] ?? '—') }}</div>
                    </div>
                    <div class="irin-preview-kv">
                        <div class="k">.IN Domain Required</div>
                        <div class="v">{{ ucfirst($previewData['part1']['domain_required'] ?? '—') }}</div>
                    </div>
                </div>
            </div>

            <div class="card irin-preview-card mb-3">
                <div class="card-header">
                    <div class="fw-bold">Part 2: New Resources</div>
                </div>
                <div class="card-body p-3">
                    <div class="irin-preview-kv">
                        <div class="k">IPv4 Prefix</div>
                        <div class="v">{{ $previewData['part2']['ipv4_prefix'] ?? '—' }}</div>
                    </div>
                    <div class="irin-preview-kv">
                        <div class="k">IPv6 Prefix</div>
                        <div class="v">{{ $previewData['part2']['ipv6_prefix'] ?? '—' }}</div>
                    </div>
                    <div class="irin-preview-kv">
                        <div class="k">ASN Required</div>
                        <div class="v">{{ ucfirst($previewData['part2']['asn_required'] ?? '—') }}</div>
                    </div>
                </div>
            </div>

            <div class="card irin-preview-card mb-3">
                <div class="card-header">
                    <div class="fw-bold">Part 5: Payment</div>
                </div>
                <div class="card-body p-3">
                    <div class="alert mb-0" style="background: rgba(107,70,193,0.08); border: 1px solid rgba(107,70,193,0.18); color:#1e3a8a;">
                        <div class="fw-bold mb-1">Application Fee</div>
                        <div>₹1,000.00 + 18% GST = <span class="fw-bold">₹1,180.00</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card irin-preview-card mb-3">
                <div class="card-header">
                    <div class="fw-bold">Part 3: Documents</div>
                </div>
                <div class="card-body p-3">
                    <div class="irin-doc-row">
                        <div>
                            <div class="irin-doc-title">Board Resolution / Authority Letter</div>
                            @if(!empty($previewData['part3']['board_resolution_file']))
                                <span class="irin-badge ok">Uploaded</span>
                            @else
                                <span class="irin-badge no">Missing</span>
                            @endif
                        </div>
                        @if(!empty($previewData['part3']['board_resolution_file']))
                            <a class="btn btn-sm irin-btn-view" target="_blank" href="{{ route('user.applications.irin.preview-document', ['doc' => 'board_resolution_file']) }}">
                                <i class="bi bi-eye"></i> View
                            </a>
                        @endif
                    </div>
                    <div class="irin-doc-row">
                        <div>
                            <div class="irin-doc-title">IRINN Agreement</div>
                            @if(!empty($previewData['part3']['irinn_agreement_file']))
                                <span class="irin-badge ok">Uploaded</span>
                            @else
                                <span class="irin-badge no">Missing</span>
                            @endif
                        </div>
                        @if(!empty($previewData['part3']['irinn_agreement_file']))
                            <a class="btn btn-sm irin-btn-view" target="_blank" href="{{ route('user.applications.irin.preview-document', ['doc' => 'irinn_agreement_file']) }}">
                                <i class="bi bi-eye"></i> View
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card irin-preview-card mb-3">
                <div class="card-header">
                    <div class="fw-bold">Part 4: Resource Justification</div>
                </div>
                <div class="card-body p-3">
                    <div class="irin-doc-row">
                        <div>
                            <div class="irin-doc-title">Network Diagram</div>
                            @if(!empty($previewData['part4']['network_diagram_file']))
                                <span class="irin-badge ok">Uploaded</span>
                            @else
                                <span class="irin-badge no">Missing</span>
                            @endif
                        </div>
                        @if(!empty($previewData['part4']['network_diagram_file']))
                            <a class="btn btn-sm irin-btn-view" target="_blank" href="{{ route('user.applications.irin.preview-document', ['doc' => 'network_diagram_file']) }}">
                                <i class="bi bi-eye"></i> View
                            </a>
                        @endif
                    </div>
                    <div class="irin-doc-row">
                        <div>
                            <div class="irin-doc-title">Core Physical Equipment Invoice</div>
                            @if(!empty($previewData['part4']['equipment_invoice_file']))
                                <span class="irin-badge ok">Uploaded</span>
                            @else
                                <span class="irin-badge no">Missing</span>
                            @endif
                        </div>
                        @if(!empty($previewData['part4']['equipment_invoice_file']))
                            <a class="btn btn-sm irin-btn-view" target="_blank" href="{{ route('user.applications.irin.preview-document', ['doc' => 'equipment_invoice_file']) }}">
                                <i class="bi bi-eye"></i> View
                            </a>
                        @endif
                    </div>
                    <div class="irin-doc-row">
                        <div>
                            <div class="irin-doc-title">Bandwidth Invoices (Last 3 months)</div>
                            @php $bw = $previewData['part4']['bandwidth_invoice_file'] ?? []; @endphp
                            @if(is_array($bw) && count($bw) > 0)
                                <span class="irin-badge ok">{{ count($bw) }} file(s)</span>
                            @else
                                <span class="irin-badge no">Missing</span>
                            @endif
                        </div>
                        @if(is_array($bw) && count($bw) > 0)
                            <div class="d-flex gap-2 flex-wrap justify-content-end">
                                @foreach($bw as $i => $p)
                                    <a class="btn btn-sm irin-btn-view" target="_blank" href="{{ route('user.applications.irin.preview-document', ['doc' => 'bandwidth_invoice_file']) }}?index={{ $i }}">View {{ $i+1 }}</a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="irin-doc-row">
                        <div>
                            <div class="irin-doc-title">Bandwidth Agreement</div>
                            @if(!empty($previewData['part4']['bandwidth_agreement_file']))
                                <span class="irin-badge ok">Uploaded</span>
                            @else
                                <span class="irin-badge no">Missing</span>
                            @endif
                        </div>
                        @if(!empty($previewData['part4']['bandwidth_agreement_file']))
                            <a class="btn btn-sm irin-btn-view" target="_blank" href="{{ route('user.applications.irin.preview-document', ['doc' => 'bandwidth_agreement_file']) }}">
                                <i class="bi bi-eye"></i> View
                            </a>
                        @endif
                    </div>

                    @if(isset($previewData['part4']['upstream_provider']))
                        <div class="mt-3 p-3" style="background: rgba(30,58,138,0.04); border:1px solid rgba(30,58,138,0.10); border-radius:14px;">
                            <div class="fw-bold mb-2" style="color:#1e3a8a;">Upstream Provider Details</div>
                            <div class="row g-2">
                                <div class="col-md-6"><span class="text-muted fw-semibold">Name:</span> <span class="fw-semibold text-dark">{{ $previewData['part4']['upstream_provider']['name'] ?? '—' }}</span></div>
                                <div class="col-md-6"><span class="text-muted fw-semibold">Mobile:</span> <span class="fw-semibold text-dark">{{ $previewData['part4']['upstream_provider']['mobile'] ?? '—' }}</span></div>
                                <div class="col-md-6"><span class="text-muted fw-semibold">Email:</span> <span class="fw-semibold text-dark">{{ $previewData['part4']['upstream_provider']['email'] ?? '—' }}</span></div>
                                <div class="col-md-6"><span class="text-muted fw-semibold">Organization:</span> <span class="fw-semibold text-dark">{{ $previewData['part4']['upstream_provider']['org_name'] ?? '—' }}</span></div>
                                <div class="col-md-12"><span class="text-muted fw-semibold">ASN Details:</span> <span class="fw-semibold text-dark">{{ $previewData['part4']['upstream_provider']['asn_details'] ?? '—' }}</span></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="irin-sticky-actions">
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            <a href="{{ route('user.irinn.create') }}?from_preview=1" class="btn irin-btn-outline" id="editFromPreviewBtn">Edit Application</a>
            <button type="button" class="btn irin-btn-primary" id="submitFromPreviewBtn" onclick="submitFromPreview()">Final Submit</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Store current values in sessionStorage so the form can be restored when user clicks "Edit Application"
document.addEventListener('DOMContentLoaded', function() {
    const restore = {
        affiliate_type: @json($previewData['part1']['affiliate_type'] ?? ''),
        domain_required: @json($previewData['part1']['domain_required'] ?? ''),
        ipv4_prefix: @json($previewData['part2']['ipv4_prefix'] ?? ''),
        ipv6_prefix: @json($previewData['part2']['ipv6_prefix'] ?? ''),
        asn_required: @json($previewData['part2']['asn_required'] ?? ''),
        upstream_name: @json($previewData['part4']['upstream_provider']['name'] ?? ''),
        upstream_mobile: @json($previewData['part4']['upstream_provider']['mobile'] ?? ''),
        upstream_email: @json($previewData['part4']['upstream_provider']['email'] ?? ''),
        upstream_org_name: @json($previewData['part4']['upstream_provider']['org_name'] ?? ''),
        upstream_asn_details: @json($previewData['part4']['upstream_provider']['asn_details'] ?? ''),
    };
    try { sessionStorage.setItem('irin_form_data', JSON.stringify(restore)); } catch(e) {}
});

function submitFromPreview() {
    if (!confirm('Are you sure you want to submit this application? Once submitted, it cannot be edited unless allowed by admin.')) {
        return;
    }
    
    const submitBtn = document.getElementById('submitFromPreviewBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    // Submit using the session data stored on the server
    fetch('{{ route("user.applications.irin.store-new") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            action: 'submit',
            from_preview: '1',
            _token: '{{ csrf_token() }}'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.payment_url && data.payment_data) {
                // Create and submit payment form to PayU
                const paymentForm = document.createElement('form');
                paymentForm.method = 'POST';
                paymentForm.action = data.payment_url;
                
                Object.keys(data.payment_data).forEach(key => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = key;
                    input.value = data.payment_data[key];
                    paymentForm.appendChild(input);
                });
                
                document.body.appendChild(paymentForm);
                paymentForm.submit();
            } else if (data.redirect_url) {
                // Wallet payment successful, redirect
                window.location.href = data.redirect_url;
            } else {
                alert('Application submitted successfully!');
                window.location.href = '{{ route("user.applications.index") }}';
            }
        } else {
            alert('Error submitting application: ' + (data.message || 'Unknown error'));
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error submitting application');
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
}
</script>
@endpush
@endsection
