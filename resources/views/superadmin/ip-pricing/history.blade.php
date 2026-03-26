@extends('superadmin.layout')

@section('title', 'Pricing History')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Pricing History</h2>
                <p class="text-muted mb-0">
                    <a href="{{ route('superadmin.ip-pricing.index') }}" class="text-decoration-none">← Back to IP Pricing</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Pricing Details -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0" style="font-weight: 600;">Pricing Information</h5>
        </div>
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">IP Type</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">
                            <span class="badge bg-{{ $pricing->ip_type === 'ipv4' ? 'primary' : 'success' }}">
                                {{ strtoupper($pricing->ip_type) }}
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Size</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ $pricing->size }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Addresses</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">{{ number_format($pricing->addresses) }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Current Price</label>
                        <p class="mb-0" style="color: #2c3e50; font-weight: 500; font-size: 1.1rem;">₹{{ number_format($pricing->getFinalPrice(), 2) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0" style="font-weight: 600;">Change History ({{ $history->count() }})</h5>
        </div>
        <div class="card-body p-4">
            @if($history->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="color: #2c3e50; font-weight: 600;">Change Type</th>
                                <th style="color: #2c3e50; font-weight: 600;">Updated By</th>
                                <th style="color: #2c3e50; font-weight: 600;">Effective From</th>
                                <th style="color: #2c3e50; font-weight: 600;">Effective Until</th>
                                <th style="color: #2c3e50; font-weight: 600;">Price</th>
                                <th style="color: #2c3e50; font-weight: 600;">Amount</th>
                                <th style="color: #2c3e50; font-weight: 600;">GST</th>
                                <th style="color: #2c3e50; font-weight: 600;">Changed On</th>
                                <th style="color: #2c3e50; font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $entry)
                            <tr>
                                <td>
                                    <span class="badge rounded-pill px-3 py-1 
                                        @if($entry->change_type === 'created') bg-success
                                        @elseif($entry->change_type === 'updated') bg-primary
                                        @elseif($entry->change_type === 'deleted') bg-danger
                                        @elseif($entry->change_type === 'activated') bg-success
                                        @elseif($entry->change_type === 'deactivated') bg-warning text-dark
                                        @else bg-info @endif">
                                        {{ ucfirst(str_replace('_', ' ', $entry->change_type)) }}
                                    </span>
                                </td>
                                <td>{{ $entry->updated_by ?? 'System' }}</td>
                                <td>
                                    <small>{{ $entry->effective_from ? $entry->effective_from->format('M d, Y') : '-' }}</small>
                                </td>
                                <td>
                                    <small>{{ $entry->effective_until ? $entry->effective_until->format('M d, Y') : 'No expiry' }}</small>
                                </td>
                                <td>
                                    @if($entry->new_data && isset($entry->new_data['price']))
                                        <strong style="color: #2c3e50;">₹{{ number_format($entry->new_data['price'], 2) }}</strong>
                                    @elseif($entry->old_data && isset($entry->old_data['price']))
                                        <span class="text-muted">₹{{ number_format($entry->old_data['price'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($entry->new_data && isset($entry->new_data['amount']))
                                        ₹{{ number_format($entry->new_data['amount'], 2) }}
                                    @elseif($entry->old_data && isset($entry->old_data['amount']))
                                        <span class="text-muted">₹{{ number_format($entry->old_data['amount'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($entry->new_data && isset($entry->new_data['gst_percentage']))
                                        {{ $entry->new_data['gst_percentage'] }}%
                                    @elseif($entry->old_data && isset($entry->old_data['gst_percentage']))
                                        <span class="text-muted">{{ $entry->old_data['gst_percentage'] }}%</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ $entry->created_at->format('M d, Y h:i A') }}</small>
                                    <br>
                                    <small class="text-muted">{{ $entry->created_at->diffForHumans() }}</small>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detailsModal{{ $entry->id }}" title="View Details">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M8.93 6.588l-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>

                            <!-- Details Modal -->
                            <div class="modal fade" id="detailsModal{{ $entry->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content" style="border-radius: 16px;">
                                        <div class="modal-header bg-info text-white" style="border-radius: 16px 16px 0 0;">
                                            <h5 class="modal-title" style="font-weight: 600;">Change Details - {{ ucfirst(str_replace('_', ' ', $entry->change_type)) }}</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Change Type</label>
                                                    <p class="mb-0">
                                                        <span class="badge rounded-pill px-3 py-1 
                                                            @if($entry->change_type === 'created') bg-success
                                                            @elseif($entry->change_type === 'updated') bg-primary
                                                            @elseif($entry->change_type === 'deleted') bg-danger
                                                            @elseif($entry->change_type === 'activated') bg-success
                                                            @elseif($entry->change_type === 'deactivated') bg-warning text-dark
                                                            @else bg-info @endif">
                                                            {{ ucfirst(str_replace('_', ' ', $entry->change_type)) }}
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Updated By</label>
                                                    <p class="mb-0" style="color: #2c3e50; font-weight: 500;">{{ $entry->updated_by ?? 'System' }}</p>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small mb-1">Changed On</label>
                                                    <p class="mb-0" style="color: #2c3e50; font-weight: 500;">
                                                        {{ $entry->created_at->format('M d, Y h:i A') }}
                                                        <br>
                                                        <small class="text-muted">{{ $entry->created_at->diffForHumans() }}</small>
                                                    </p>
                                                </div>
                                                @if($entry->effective_from)
                                                    <div class="col-md-6">
                                                        <label class="text-muted small mb-1">Effective From</label>
                                                        <p class="mb-0" style="color: #2c3e50; font-weight: 500;">{{ $entry->effective_from->format('M d, Y') }}</p>
                                                    </div>
                                                @endif
                                                @if($entry->effective_until)
                                                    <div class="col-md-6">
                                                        <label class="text-muted small mb-1">Effective Until</label>
                                                        <p class="mb-0" style="color: #2c3e50; font-weight: 500;">{{ $entry->effective_until->format('M d, Y') }}</p>
                                                    </div>
                                                @endif
                                                @if($entry->notes)
                                                    <div class="col-md-12">
                                                        <label class="text-muted small mb-1">Notes</label>
                                                        <p class="mb-0" style="color: #2c3e50;">{{ $entry->notes }}</p>
                                                    </div>
                                                @endif
                                                
                                                @if($entry->old_data)
                                                    <div class="col-md-6">
                                                        <label class="text-muted small mb-1">Old Data</label>
                                                        <div class="p-3 bg-light rounded" style="max-height: 300px; overflow-y: auto;">
                                                            <pre style="margin: 0; font-size: 0.85rem;">{{ json_encode($entry->old_data, JSON_PRETTY_PRINT) }}</pre>
                                                        </div>
                                                    </div>
                                                @endif
                                                
                                                @if($entry->new_data)
                                                    <div class="col-md-6">
                                                        <label class="text-muted small mb-1">New Data</label>
                                                        <div class="p-3 bg-light rounded" style="max-height: 300px; overflow-y: auto;">
                                                            <pre style="margin: 0; font-size: 0.85rem;">{{ json_encode($entry->new_data, JSON_PRETTY_PRINT) }}</pre>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="modal-footer" style="border-top: 1px solid #e0e0e0;">
                                            <button type="button" class="btn btn-outline-secondary px-4" style="border-radius: 8px; font-weight: 500;" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                    </svg>
                    <p class="text-muted mb-0">No history found for this pricing.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

