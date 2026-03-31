@extends('superadmin.layout')

@section('title', 'IP Pricing Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">IP Pricing Management</h2>
                <p class="text-muted mb-0">Configure IPv4 and IPv6 pricing with GST</p>
            </div>
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-outline-secondary px-4" style="border-radius: 10px; font-weight: 500;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Back to Dashboard
            </a>
    </div>
</div>

    <div class="row g-4">
    <!-- IPv4 Pricing -->
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">IPv4 Pricing Configuration</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addIpv4Modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Add New Pricing
                    </button>
            </div>
                <div class="card-body p-4">
                @if($ipv4Pricings->count() > 0)
                    <div class="table-responsive">
                            <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Size</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Addresses</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Amount (₹)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">IGST (%)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">CGST (%)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">SGST (%)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Price (₹)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Effective From</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Effective Until</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ipv4Pricings as $pricing)
                                <tr>
                                        <td><strong style="color: #2c3e50;">{{ $pricing->size }}</strong></td>
                                    <td>{{ \App\Models\IpPricing::formatAddressCount($pricing->addresses) }}</td>
                                        <td>₹{{ number_format($pricing->getComputedAmount(), 2) }}</td>
                                        <td>{{ $pricing->igst !== null ? number_format((float) $pricing->igst, 2) : '-' }}</td>
                                        <td>{{ $pricing->cgst !== null ? number_format((float) $pricing->cgst, 2) : '-' }}</td>
                                        <td>{{ $pricing->sgst !== null ? number_format((float) $pricing->sgst, 2) : '-' }}</td>
                                        <td><strong style="color: #2c3e50;">₹{{ number_format($pricing->price ?? $pricing->getFinalPrice(), 2) }}</strong></td>
                                        <td>
                                            <small>{{ $pricing->effective_from ? $pricing->effective_from->format('M d, Y') : '-' }}</small>
                                            @if($pricing->effective_from && $pricing->effective_from > now())
                                                <br><span class="badge bg-info text-dark">Scheduled</span>
                                        @endif
                                    </td>
                                    <td>
                                            <small>{{ $pricing->effective_until ? $pricing->effective_until->format('M d, Y') : 'No expiry' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1 {{ $pricing->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $pricing->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            @if($pricing->is_active && $pricing->effective_from && $pricing->effective_from > now())
                                                <br><small class="text-muted">Scheduled</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $pricing->id }}" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707 10.293 16l6.5-6.5-1.586-1.586z"/>
                                                    </svg>
                                                </button>
                                                <a href="{{ route('superadmin.ip-pricing.history', $pricing->id) }}" class="btn btn-sm btn-info" title="View History">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('superadmin.ip-pricing.toggle-status', $pricing->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $pricing->is_active ? 'btn-warning' : 'btn-success' }}" title="{{ $pricing->is_active ? 'Deactivate' : 'Activate' }}">
                                                        @if($pricing->is_active)
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('superadmin.ip-pricing.destroy', $pricing->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this pricing?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                        </svg>
                                        </button>
                                                </form>
                                            </div>
                                    </td>
                                </tr>
                                
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal{{ $pricing->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content" style="border-radius: 16px;">
                                                <div class="modal-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                                                    <h5 class="modal-title" style="font-weight: 600;">Edit IPv4 Pricing - {{ $pricing->size }}</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                                <form method="POST" action="{{ route('superadmin.ip-pricing.update', $pricing->id) }}" id="editForm{{ $pricing->id }}">
                                                @csrf
                                                    @method('PUT')
                                                    <div class="modal-body p-4">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                        <label class="form-label">Size</label>
                                                        <input type="text" class="form-control pricing-size-input" value="{{ $pricing->size }}" data-ip-type="{{ $pricing->ip_type }}" data-target-addresses="editAddresses{{ $pricing->id }}" data-target-preview="editAddressesPreview{{ $pricing->id }}" disabled>
                                                        <small class="text-muted">Size cannot be changed</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                        <label class="form-label">Addresses <span class="text-danger">*</span></label>
                                                        <input type="text" name="addresses" id="editAddresses{{ $pricing->id }}" class="form-control js-addresses-input" value="{{ $pricing->addresses }}" required pattern="[0-9]{1,39}" inputmode="numeric">
                                                        <small class="text-muted" id="editAddressesPreview{{ $pricing->id }}">Current total addresses: {{ \App\Models\IpPricing::formatAddressCount($pricing->addresses) }}</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Calculated Amount (₹)</label>
                                                                <input type="text" class="form-control js-computed-amount" value="{{ number_format($pricing->getComputedAmount(), 2) }}" readonly>
                                                                <small class="text-muted">Auto-calculated from address count</small>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">IGST (%)</label>
                                                                <input type="number" name="igst" class="form-control js-tax-input" value="{{ $pricing->igst }}" step="0.01" min="0" max="100">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">CGST (%)</label>
                                                                <input type="number" name="cgst" class="form-control js-tax-input" value="{{ $pricing->cgst }}" step="0.01" min="0" max="100">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">SGST (%)</label>
                                                                <input type="number" name="sgst" class="form-control js-tax-input" value="{{ $pricing->sgst }}" step="0.01" min="0" max="100">
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Final Price (₹)</label>
                                                                <input type="text" class="form-control js-computed-price" value="{{ number_format($pricing->getFinalPrice(), 2) }}" readonly>
                                                                <small class="text-muted">Auto-calculated from amount + tax percentages</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Effective From</label>
                                                                <input type="date" name="effective_from" class="form-control" value="{{ $pricing->effective_from ? $pricing->effective_from->format('Y-m-d') : '' }}">
                                                                <small class="text-muted">When this pricing becomes active</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Effective Until</label>
                                                                <input type="date" name="effective_until" class="form-control" value="{{ $pricing->effective_until ? $pricing->effective_until->format('Y-m-d') : '' }}">
                                                                <small class="text-muted">Leave empty for no expiry</small>
                                                    </div>
                                                            <div class="col-md-12">
                                                                <div class="form-check p-3 border rounded">
                                                            <input type="checkbox" name="is_active" class="form-check-input" id="active{{ $pricing->id }}" {{ $pricing->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="active{{ $pricing->id }}">
                                                                        Active
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: 1px solid #e0e0e0;">
                                                        <button type="button" class="btn btn-outline-secondary px-4" style="border-radius: 8px; font-weight: 500;" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 500;">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                        <div class="text-center py-5">
                            <p class="text-muted">No IPv4 pricing configurations found. Click "Add New Pricing" to create one.</p>
                        </div>
                @endif
            </div>
        </div>
    </div>

    <!-- IPv6 Pricing -->
    <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0" style="font-weight: 600;">IPv6 Pricing Configuration</h5>
                    <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addIpv6Modal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Add New Pricing
                    </button>
            </div>
                <div class="card-body p-4">
                @if($ipv6Pricings->count() > 0)
                    <div class="table-responsive">
                            <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Size</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Addresses</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Amount (₹)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">IGST (%)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">CGST (%)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">SGST (%)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Price (₹)</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Effective From</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Effective Until</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($ipv6Pricings as $pricing)
                                <tr>
                                        <td><strong style="color: #2c3e50;">{{ $pricing->size }}</strong></td>
                                    <td>{{ \App\Models\IpPricing::formatAddressCount($pricing->addresses) }}</td>
                                        <td>₹{{ number_format($pricing->getComputedAmount(), 2) }}</td>
                                        <td>{{ $pricing->igst !== null ? number_format((float) $pricing->igst, 2) : '-' }}</td>
                                        <td>{{ $pricing->cgst !== null ? number_format((float) $pricing->cgst, 2) : '-' }}</td>
                                        <td>{{ $pricing->sgst !== null ? number_format((float) $pricing->sgst, 2) : '-' }}</td>
                                        <td><strong style="color: #2c3e50;">₹{{ number_format($pricing->price ?? $pricing->getFinalPrice(), 2) }}</strong></td>
                                        <td>
                                            <small>{{ $pricing->effective_from ? $pricing->effective_from->format('M d, Y') : '-' }}</small>
                                            @if($pricing->effective_from && $pricing->effective_from > now())
                                                <br><span class="badge bg-info text-dark">Scheduled</span>
                                        @endif
                                    </td>
                                    <td>
                                            <small>{{ $pricing->effective_until ? $pricing->effective_until->format('M d, Y') : 'No expiry' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill px-3 py-1 {{ $pricing->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $pricing->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                            @if($pricing->is_active && $pricing->effective_from && $pricing->effective_from > now())
                                                <br><small class="text-muted">Scheduled</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#editModal{{ $pricing->id }}" title="Edit">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707 10.293 16l6.5-6.5-1.586-1.586z"/>
                                                    </svg>
                                                </button>
                                                <a href="{{ route('superadmin.ip-pricing.history', $pricing->id) }}" class="btn btn-sm btn-info" title="View History">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                        <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                                                    </svg>
                                                </a>
                                                <form method="POST" action="{{ route('superadmin.ip-pricing.toggle-status', $pricing->id) }}" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $pricing->is_active ? 'btn-warning' : 'btn-success' }}" title="{{ $pricing->is_active ? 'Deactivate' : 'Activate' }}">
                                                        @if($pricing->is_active)
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                                                            </svg>
                                                        @endif
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('superadmin.ip-pricing.destroy', $pricing->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this pricing?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal{{ $pricing->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content" style="border-radius: 16px;">
                                                <div class="modal-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                                                    <h5 class="modal-title" style="font-weight: 600;">Edit IPv6 Pricing - {{ $pricing->size }}</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="{{ route('superadmin.ip-pricing.update', $pricing->id) }}" id="editForm{{ $pricing->id }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body p-4">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                        <label class="form-label">Size</label>
                                                        <input type="text" class="form-control pricing-size-input" value="{{ $pricing->size }}" data-ip-type="{{ $pricing->ip_type }}" data-target-addresses="editAddresses{{ $pricing->id }}" data-target-preview="editAddressesPreview{{ $pricing->id }}" disabled>
                                                        <small class="text-muted">Size cannot be changed</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                        <label class="form-label">Addresses <span class="text-danger">*</span></label>
                                                        <input type="text" name="addresses" id="editAddresses{{ $pricing->id }}" class="form-control js-addresses-input" value="{{ $pricing->addresses }}" required pattern="[0-9]{1,39}" inputmode="numeric">
                                                        <small class="text-muted" id="editAddressesPreview{{ $pricing->id }}">Current total addresses: {{ \App\Models\IpPricing::formatAddressCount($pricing->addresses) }}</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Calculated Amount (₹)</label>
                                                                <input type="text" class="form-control js-computed-amount" value="{{ number_format($pricing->getComputedAmount(), 2) }}" readonly>
                                                                <small class="text-muted">Auto-calculated from address count</small>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">IGST (%)</label>
                                                                <input type="number" name="igst" class="form-control js-tax-input" value="{{ $pricing->igst }}" step="0.01" min="0" max="100">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">CGST (%)</label>
                                                                <input type="number" name="cgst" class="form-control js-tax-input" value="{{ $pricing->cgst }}" step="0.01" min="0" max="100">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label">SGST (%)</label>
                                                                <input type="number" name="sgst" class="form-control js-tax-input" value="{{ $pricing->sgst }}" step="0.01" min="0" max="100">
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Final Price (₹)</label>
                                                                <input type="text" class="form-control js-computed-price" value="{{ number_format($pricing->getFinalPrice(), 2) }}" readonly>
                                                                <small class="text-muted">Auto-calculated from amount + tax percentages</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Effective From</label>
                                                                <input type="date" name="effective_from" class="form-control" value="{{ $pricing->effective_from ? $pricing->effective_from->format('Y-m-d') : '' }}">
                                                                <small class="text-muted">When this pricing becomes active</small>
                                                    </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Effective Until</label>
                                                                <input type="date" name="effective_until" class="form-control" value="{{ $pricing->effective_until ? $pricing->effective_until->format('Y-m-d') : '' }}">
                                                                <small class="text-muted">Leave empty for no expiry</small>
                                                    </div>
                                                            <div class="col-md-12">
                                                                <div class="form-check p-3 border rounded">
                                                            <input type="checkbox" name="is_active" class="form-check-input" id="active{{ $pricing->id }}" {{ $pricing->is_active ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="active{{ $pricing->id }}">
                                                                        Active
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: 1px solid #e0e0e0;">
                                                        <button type="button" class="btn btn-outline-secondary px-4" style="border-radius: 8px; font-weight: 500;" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success px-4" style="border-radius: 8px; font-weight: 500;">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                        <div class="text-center py-5">
                            <p class="text-muted">No IPv6 pricing configurations found. Click "Add New Pricing" to create one.</p>
                        </div>
                @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Add IPv4 Modal -->
    <div class="modal fade" id="addIpv4Modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title" style="font-weight: 600;">Add New IPv4 Pricing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.ip-pricing.store') }}" id="addIpv4Form">
                    @csrf
                    <input type="hidden" name="ip_type" value="ipv4">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Size <span class="text-danger">*</span></label>
                                <input type="text" name="size" class="form-control pricing-size-input" data-ip-type="ipv4" data-target-addresses="addIpv4Addresses" data-target-preview="addIpv4AddressesPreview" placeholder="e.g., /24, /23" required>
                                <small class="text-muted">CIDR notation</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Addresses <span class="text-danger">*</span></label>
                                <input type="text" name="addresses" id="addIpv4Addresses" class="form-control" required pattern="[0-9]{1,39}" inputmode="numeric">
                                <small class="text-muted" id="addIpv4AddressesPreview">Addresses will be auto-calculated from CIDR size.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Calculated Amount (₹)</label>
                                <input type="text" class="form-control" id="addAmountIpv4" readonly>
                                <small class="text-muted">Auto-calculated from address count</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">IGST (%)</label>
                                <input type="number" name="igst" class="form-control" step="0.01" min="0" max="100" id="addIgstIpv4">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CGST (%)</label>
                                <input type="number" name="cgst" class="form-control" step="0.01" min="0" max="100" id="addCgstIpv4">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SGST (%)</label>
                                <input type="number" name="sgst" class="form-control" step="0.01" min="0" max="100" id="addSgstIpv4">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Final Price (₹)</label>
                                <input type="text" class="form-control" id="addPriceIpv4" readonly>
                                <small class="text-muted">Auto-calculated from amount + tax percentages</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Effective From</label>
                                <input type="date" name="effective_from" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                <small class="text-muted">When this pricing becomes active</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Effective Until</label>
                                <input type="date" name="effective_until" class="form-control">
                                <small class="text-muted">Leave empty for no expiry</small>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check p-3 border rounded">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="addActiveIpv4" checked>
                                    <label class="form-check-label" for="addActiveIpv4">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e0e0e0;">
                        <button type="button" class="btn btn-outline-secondary px-4" style="border-radius: 8px; font-weight: 500;" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" style="border-radius: 8px; font-weight: 500;">Add Pricing</button>
                    </div>
                </form>
        </div>
    </div>
</div>

    <!-- Add IPv6 Modal -->
    <div class="modal fade" id="addIpv6Modal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header bg-success text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title" style="font-weight: 600;">Add New IPv6 Pricing</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.ip-pricing.store') }}" id="addIpv6Form">
                    @csrf
                    <input type="hidden" name="ip_type" value="ipv6">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Size <span class="text-danger">*</span></label>
                                <input type="text" name="size" class="form-control pricing-size-input" data-ip-type="ipv6" data-target-addresses="addIpv6Addresses" data-target-preview="addIpv6AddressesPreview" placeholder="e.g., /48, /32" required>
                                <small class="text-muted">CIDR notation</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Addresses <span class="text-danger">*</span></label>
                                <input type="text" name="addresses" id="addIpv6Addresses" class="form-control" required pattern="[0-9]{1,39}" inputmode="numeric">
                                <small class="text-muted" id="addIpv6AddressesPreview">Addresses will be auto-calculated from CIDR size.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Calculated Amount (₹)</label>
                                <input type="text" class="form-control" id="addAmountIpv6" readonly>
                                <small class="text-muted">Auto-calculated from address count</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">IGST (%)</label>
                                <input type="number" name="igst" class="form-control" step="0.01" min="0" max="100" id="addIgstIpv6">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CGST (%)</label>
                                <input type="number" name="cgst" class="form-control" step="0.01" min="0" max="100" id="addCgstIpv6">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">SGST (%)</label>
                                <input type="number" name="sgst" class="form-control" step="0.01" min="0" max="100" id="addSgstIpv6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Final Price (₹)</label>
                                <input type="text" class="form-control" id="addPriceIpv6" readonly>
                                <small class="text-muted">Auto-calculated from amount + tax percentages</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Effective From</label>
                                <input type="date" name="effective_from" class="form-control" value="{{ now()->format('Y-m-d') }}">
                                <small class="text-muted">When this pricing becomes active</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Effective Until</label>
                                <input type="date" name="effective_until" class="form-control">
                                <small class="text-muted">Leave empty for no expiry</small>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check p-3 border rounded">
                                    <input type="checkbox" name="is_active" class="form-check-input" id="addActiveIpv6" checked>
                                    <label class="form-check-label" for="addActiveIpv6">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #e0e0e0;">
                        <button type="button" class="btn btn-outline-secondary px-4" style="border-radius: 8px; font-weight: 500;" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success px-4" style="border-radius: 8px; font-weight: 500;">Add Pricing</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function formatBigIntWithCommas(value) {
        return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function parseCidrPrefix(rawSize) {
        if (!rawSize) {
            return null;
        }

        const match = String(rawSize).trim().match(/^\/?(\d{1,3})$/);

        return match ? parseInt(match[1], 10) : null;
    }

    function getAddressCount(ipType, rawSize) {
        const prefix = parseCidrPrefix(rawSize);
        if (prefix === null) {
            return null;
        }

        if (ipType === 'ipv4') {
            if (prefix < 0 || prefix > 32) {
                return null;
            }

            return {
                raw: (BigInt(1) << BigInt(32 - prefix)).toString(),
                numeric: Number(2 ** (32 - prefix)),
                label: formatBigIntWithCommas(BigInt(1) << BigInt(32 - prefix)),
            };
        }

        if (ipType === 'ipv6') {
            if (prefix < 0 || prefix > 128) {
                return null;
            }

            const addresses = BigInt(1) << BigInt(128 - prefix);

            return {
                raw: addresses.toString(),
                numeric: null, // too large for safe number in most cases
                label: formatBigIntWithCommas(addresses),
            };
        }

        return null;
    }

    function wireAddressesPreview(sizeInput) {
        const ipType = sizeInput.dataset.ipType;
        const targetAddressesId = sizeInput.dataset.targetAddresses;
        const targetPreviewId = sizeInput.dataset.targetPreview;

        if (!ipType || !targetAddressesId || !targetPreviewId) {
            return;
        }

        const addressesInput = document.getElementById(targetAddressesId);
        const previewEl = document.getElementById(targetPreviewId);
        if (!addressesInput || !previewEl) {
            return;
        }

        const refresh = () => {
            const result = getAddressCount(ipType, sizeInput.value);
            if (!result) {
                previewEl.textContent = 'Enter a valid CIDR size to view address count.';
                return;
            }

            previewEl.textContent = `Calculated addresses: ${result.label}`;
            addressesInput.value = result.raw;
        };

        sizeInput.addEventListener('input', refresh);
        refresh();
    }

    document.querySelectorAll('.pricing-size-input').forEach(wireAddressesPreview);

    function parsePositiveNumber(v) {
        const n = parseFloat(v);

        return Number.isFinite(n) && n > 0 ? n : 0;
    }

    function log2FromIntegerString(raw) {
        const digits = String(raw || '').replace(/\D+/g, '');
        if (!digits || /^0+$/.test(digits)) {
            return null;
        }

        // Approximate log2 for very large integers without losing validity in JS number range.
        const lead = parseFloat(digits.slice(0, Math.min(15, digits.length)));
        const leadDigits = digits.length - Math.min(15, digits.length);
        const leadScaled = lead * Math.pow(10, leadDigits);

        return Math.log2(leadScaled);
    }

    function computeAmountByAddresses(ipType, addressesRaw) {
        const log2Addresses = log2FromIntegerString(addressesRaw);
        if (log2Addresses === null) {
            return 0;
        }

        if (ipType === 'ipv4') {
            return 27500 * Math.pow(1.35, log2Addresses - 8);
        }

        return 24199 * Math.pow(1.35, log2Addresses - 80);
    }

    function refreshComputedAmountsInForm(formEl) {
        if (!formEl) {
            return;
        }

        const sizeInput = formEl.querySelector('.pricing-size-input');
        const addressesInput = formEl.querySelector('input[name="addresses"]');
        const amountEl = formEl.querySelector('#addAmountIpv4, #addAmountIpv6, .js-computed-amount');
        const priceEl = formEl.querySelector('#addPriceIpv4, #addPriceIpv6, .js-computed-price');
        const igstEl = formEl.querySelector('input[name="igst"]');
        const cgstEl = formEl.querySelector('input[name="cgst"]');
        const sgstEl = formEl.querySelector('input[name="sgst"]');

        if (!sizeInput || !addressesInput || !amountEl || !priceEl) {
            return;
        }

        const ipType = sizeInput.dataset.ipType || 'ipv4';
        const amount = computeAmountByAddresses(ipType, addressesInput.value);
        const igst = parsePositiveNumber(igstEl ? igstEl.value : 0);
        const cgst = parsePositiveNumber(cgstEl ? cgstEl.value : 0);
        const sgst = parsePositiveNumber(sgstEl ? sgstEl.value : 0);
        const taxPercent = igst > 0 ? igst : (cgst + sgst);
        const finalPrice = amount + ((amount * taxPercent) / 100);

        amountEl.value = Number.isFinite(amount) ? amount.toFixed(2) : '';
        priceEl.value = Number.isFinite(finalPrice) ? finalPrice.toFixed(2) : '';
    }

    document.querySelectorAll('#addIpv4Form, #addIpv6Form, form[id^="editForm"]').forEach((formEl) => {
        const watchers = formEl.querySelectorAll('.pricing-size-input, input[name="addresses"], input[name="igst"], input[name="cgst"], input[name="sgst"]');
        watchers.forEach((el) => {
            el.addEventListener('input', () => refreshComputedAmountsInForm(formEl));
        });

        refreshComputedAmountsInForm(formEl);
    });
});
</script>
@endpush
@endsection
