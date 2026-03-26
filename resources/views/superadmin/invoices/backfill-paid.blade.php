@extends('superadmin.layout')

@section('title', 'Backfill Paid Invoices')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="flex-wrap">
                <h2 class="mb-1 border-0">Backfill Paid Invoices</h2>
                <p class="mb-2 text-muted">Generate <strong>Application Fee</strong> invoices (already paid) for selected applications. No emails will be sent.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('superadmin.invoices.index') }}" class="btn btn-primary">Back to Invoices</a>
            </div>
        </div>
        <div class="accent-line"></div>
    </div>
</div>

@if(session('backfill_generated') || session('backfill_skipped') || session('backfill_failed'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue">
                <div class="card-header theme-bg-blue text-white">
                    <h5 class="mb-0">Last Backfill Result</h5>
                </div>
                <div class="card-body">
                    @php
                        $generated = session('backfill_generated', []);
                        $skipped = session('backfill_skipped', []);
                        $failed = session('backfill_failed', []);
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="alert alert-success mb-0">
                                <strong>Generated:</strong> {{ count($generated) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning mb-0">
                                <strong>Skipped:</strong> {{ count($skipped) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-danger mb-0">
                                <strong>Failed:</strong> {{ count($failed) }}
                            </div>
                        </div>
                    </div>

                    @if(count($generated) > 0)
                        <div class="mt-3">
                            <h6 class="mb-2">Generated</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Invoice #</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($generated as $row)
                                            <tr>
                                                <td>{{ $row['application_id'] ?? '—' }}</td>
                                                <td>{{ $row['invoice_number'] ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(count($failed) > 0)
                        <div class="mt-3">
                            <h6 class="mb-2">Failed</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Application ID</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($failed as $row)
                                            <tr>
                                                <td>{{ $row['application_id'] ?? '—' }}</td>
                                                <td class="text-danger">{{ $row['reason'] ?? 'Unknown error' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('superadmin.invoices.backfill-paid.index') }}" class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Application ID, Membership ID, user name/email/mobile...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Submitted From</label>
                        <input type="date" name="submitted_from" class="form-control" value="{{ request('submitted_from') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Submitted To</label>
                        <input type="date" name="submitted_to" class="form-control" value="{{ request('submitted_to') }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Go</button>
                    </div>
                    @if(request()->filled('search') || request()->filled('submitted_from') || request()->filled('submitted_to'))
                        <div class="col-12">
                            <a href="{{ route('superadmin.invoices.backfill-paid.index') }}" class="btn btn-sm btn-danger">Reset</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('superadmin.invoices.backfill-paid.store') }}" onsubmit="return confirm('Generate paid invoices for selected applications? No email will be sent.');">
    @csrf
    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue">
                <div class="card-header theme-bg-blue text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">Eligible Applications (No Application Fee Invoice Yet)</h5>
                    <button type="submit" class="btn btn-sm btn-primary">Generate Paid Invoices</button>
                </div>
                <div class="card-body">
                    @if($applications->count() === 0)
                        <p class="text-muted mb-0">No eligible applications found.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Application ID</th>
                                        <th>Membership ID</th>
                                        <th>User</th>
                                        <th>Submitted At</th>
                                        <th>Status</th>
                                        <th>Service Status</th>
                                        <th>Billing Cycle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($applications as $app)
                                        <tr class="align-middle">
                                            <td>
                                                <input type="checkbox" name="application_ids[]" value="{{ $app->id }}" class="row-check">
                                            </td>
                                            <td>
                                                <a href="{{ route('superadmin.applications.show', $app->id) }}" class="text-decoration-none">
                                                    <strong>{{ $app->application_id }}</strong>
                                                </a>
                                            </td>
                                            <td>{{ $app->membership_id }}</td>
                                            <td class="text-break">
                                                <div class="fw-semibold">{{ $app->user->fullname ?? '—' }}</div>
                                                <div class="text-muted small">{{ $app->user->email ?? '—' }}</div>
                                            </td>
                                            <td class="text-nowrap">{{ $app->submitted_at ? $app->submitted_at->format('d M Y') : '—' }}</td>
                                            <td><span class="badge bg-secondary">{{ $app->status_display ?? $app->status }}</span></td>
                                            <td><span class="badge bg-info text-dark">{{ $app->service_status ?? 'live' }}</span></td>
                                            <td class="text-capitalize">{{ $app->billing_cycle ?? 'monthly' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-center">
                            {{ $applications->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectAll = document.getElementById('selectAll');
        const checks = document.querySelectorAll('.row-check');

        if (selectAll) {
            selectAll.addEventListener('change', function () {
                checks.forEach(function (c) {
                    c.checked = selectAll.checked;
                });
            });
        }
    });
</script>
@endpush
@endsection

