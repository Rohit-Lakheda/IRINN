@extends('admin.layout')

@section('title', 'Cron Job Report')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="border-0">IX Invoice Invoice Generation Report</h2>
            <p class="text-muted small mb-0">View and export logs from the monthly IX invoice generation cron (<code>ix:generate-monthly-invoices</code>).</p>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('admin.cron-report') }}" class="row g-3 theme-forms">
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Run ID</label>
                            <input type="text" name="run_id" class="form-control" placeholder="UUID" value="{{ $filters['run_id'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="generated" {{ ($filters['status'] ?? '') === 'generated' ? 'selected' : '' }}>Generated</option>
                                <option value="skipped" {{ ($filters['status'] ?? '') === 'skipped' ? 'selected' : '' }}>Skipped</option>
                                <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="dry_run" {{ ($filters['status'] ?? '') === 'dry_run' ? 'selected' : '' }}>Dry Run</option>
                                <option value="started" {{ ($filters['status'] ?? '') === 'started' ? 'selected' : '' }}>Started</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Application Code</label>
                            <input type="text" name="application_code" class="form-control" placeholder="Application ID" value="{{ $filters['application_code'] ?? '' }}">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-grow-1">Apply</button>
                            <a href="{{ route('admin.cron-report') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                    <div class="mt-3 d-flex align-items-center gap-2 flex-wrap">
                        @php
                            $exportQuery = http_build_query(array_filter($filters ?? []));
                            $exportUrl = $exportQuery ? route('admin.cron-report.export') . '?' . $exportQuery : route('admin.cron-report.export');
                        @endphp
                        <a href="{{ $exportUrl }}" class="btn btn-success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                                <path d="M8.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                            </svg>
                            Export to Excel
                        </a>
                        <small class="text-muted">Export uses current filters. Open in Excel or Google Sheets.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div class="card border-c-blue shadow-sm">
        <div class="card-header theme-bg-blue d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 text-capitalize">Cron Logs</h5>
            <span class="badge bg-light text-dark">{{ $logs->total() }} record(s)</span>
        </div>
        <div class="card-body p-0">
            @if($logs->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>Run ID</th>
                                <th>Started</th>
                                <th>Application</th>
                                <th>Member</th>
                                <th>Billing Period</th>
                                <th>Invoice #</th>
                                <th>Status</th>
                                <th>Skip / Error</th>
                                <th>E-invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($logs as $log)
                                <tr>
                                    <td class="small font-monospace" style="max-width: 120px;" title="{{ $log->run_id }}">{{ Str::limit($log->run_id, 8) }}</td>
                                    <td class="small">{{ $log->started_at?->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @if($log->application)
                                            <a href="{{ route('admin.applications.show', $log->application_id) }}">{{ $log->application_code ?? $log->application->application_id }}</a>
                                        @else
                                            {{ $log->application_code ?? '—' }}
                                        @endif
                                    </td>
                                    <td class="small">{{ $log->application?->user?->fullname ?? '—' }}</td>
                                    <td class="small">{{ $log->billing_period ?? '—' }}</td>
                                    <td class="small">{{ $log->invoice_number ?? '—' }}</td>
                                    <td>
                                        @if($log->status === 'generated')
                                            <span class="badge bg-success">Generated</span>
                                        @elseif($log->status === 'skipped')
                                            <span class="badge bg-secondary">Skipped</span>
                                        @elseif($log->status === 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @elseif($log->status === 'dry_run')
                                            <span class="badge bg-info">Dry Run</span>
                                        @else
                                            <span class="badge bg-warning text-dark">{{ $log->status }}</span>
                                        @endif
                                        @if($log->is_dry_run)
                                            <span class="badge bg-light text-dark">Dry</span>
                                        @endif
                                    </td>
                                    <td class="small text-break" style="max-width: 200px;">
                                        @if($log->skip_reason)
                                            <span class="text-muted">{{ Str::limit($log->skip_reason, 60) }}</span>
                                        @endif
                                        @if($log->error_message)
                                            <span class="text-danger">{{ Str::limit($log->error_message, 60) }}</span>
                                        @endif
                                        @if(empty($log->skip_reason) && empty($log->error_message))
                                            —
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($log->einvoice_attempted)
                                            @if($log->einvoice_irn)
                                                <span class="text-success" title="{{ $log->einvoice_irn }}">IRN ✓</span>
                                            @else
                                                <span class="text-warning">No IRN</span>
                                                @if($log->einvoice_error_code || $log->einvoice_error_message)
                                                    <span class="text-danger" title="{{ $log->einvoice_error_message }}">{{ $log->einvoice_error_code ?? 'Err' }}</span>
                                                @endif
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-transparent border-0">
                    {{ $logs->links('vendor.pagination.bootstrap-5') }}
                </div>
            @else
                <div class="p-4 text-center text-muted">
                    No cron logs found. Apply different filters or run the cron (<code>php artisan ix:generate-monthly-invoices --force</code>) to generate logs.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
