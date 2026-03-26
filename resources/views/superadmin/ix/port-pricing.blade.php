@extends('superadmin.layout')

@section('title', 'IX Port Pricing')

@section('content')
<div class="container-fluid px-2 py-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-0 border-0">IX Port Pricing</h2>
            <p class="text-muted mb-1">Maintain ARC / MRC / Quarterly pricing for metro and edge nodes.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left fs-6"></i> Back to Dashboard
            </a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPricingModal">
                + Add Pricing
            </button>
        </div>
    </div>
    <div class="accent-line"></div>

    @foreach(['metro' => 'Metro Nodes', 'edge' => 'Edge Nodes'] as $nodeType => $label)
        <div class="card border-c-blue shadow-sm mb-4">
            <div class="card-header bg-primary d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $label }}</h5>
                <span class="badge theme-bg-yellow text-blue" style="color: #2B2F6C !important;">{{ ucfirst($nodeType) }} Pricing</span>
            </div>
            <div class="card-body p-2">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light text-nowrap">
                            <tr>
                                <th>Port Capacity</th>
                                <th>ARC (₹)</th>
                                <th>MRC (₹)</th>
                                <th>Quarterly (₹)</th>
                                <th>Order</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($portPricings[$nodeType] ?? collect()) as $pricing)
                            <tr class="align-middle">
                                <td>{{ $pricing->port_capacity }}</td>
                                <td>₹{{ number_format($pricing->price_arc, 2) }}</td>
                                <td>₹{{ number_format($pricing->price_mrc, 2) }}</td>
                                <td>₹{{ number_format($pricing->price_quarterly, 2) }}</td>
                                <td>{{ $pricing->display_order }}</td>
                                <td>
                                    <span class="badge {{ $pricing->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $pricing->is_active ? 'Active' : 'Hidden' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm gap-2">
                                        <button class="btn btn-sm rounded btn-primary" data-bs-toggle="modal" data-bs-target="#editPricingModal{{ $pricing->id }}">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('superadmin.ix-port-pricing.toggle', $pricing) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm rounded {{ $pricing->is_active ? 'theme-bg-yellow' : 'btn-success' }}">
                                                {{ $pricing->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('superadmin.ix-port-pricing.destroy', $pricing) }}" onsubmit="return confirm('Remove this pricing entry?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm rounded btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="editPricingModal{{ $pricing->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content border-c-blue">
                                        <div class="modal-header theme-bg-blue text-white">
                                            <h5 class="modal-title text-white" style="color: #fff !important;">Update {{ $pricing->port_capacity }} ({{ ucfirst($pricing->node_type) }})</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="{{ route('superadmin.ix-port-pricing.update', $pricing) }}" class="theme-forms">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body theme-forms">
                                                <div class="mb-3">
                                                    <label class="form-label">ARC (₹)</label>
                                                    <input type="number" step="0.01" min="0" name="price_arc" class="form-control" value="{{ old('price_arc', $pricing->price_arc) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">MRC (₹)</label>
                                                    <input type="number" step="0.01" min="0" name="price_mrc" class="form-control" value="{{ old('price_mrc', $pricing->price_mrc) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Quarterly (₹)</label>
                                                    <input type="number" step="0.01" min="0" name="price_quarterly" class="form-control" value="{{ old('price_quarterly', $pricing->price_quarterly) }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Display Order</label>
                                                    <input type="number" min="0" name="display_order" class="form-control" value="{{ old('display_order', $pricing->display_order) }}">
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pricingActive{{ $pricing->id }}" @checked($pricing->is_active)>
                                                    <label class="form-check-label" for="pricingActive{{ $pricing->id }}">Active</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No pricing defined for {{ $label }}.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="modal fade" id="createPricingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #fff !important;">Add Pricing Entry</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.ix-port-pricing.store') }}" class="theme-forms">
                @csrf
                <div class="modal-body theme-forms">
                    <div class="mb-3">
                        <label class="form-label">Node Type</label>
                        <select name="node_type" class="form-select" required>
                            <option value="metro">Metro</option>
                            <option value="edge">Edge</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Port Capacity</label>
                        <input type="text" name="port_capacity" class="form-control" placeholder="e.g. 10Gig" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ARC (₹)</label>
                        <input type="number" step="0.01" min="0" name="price_arc" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">MRC (₹)</label>
                        <input type="number" step="0.01" min="0" name="price_mrc" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quarterly (₹)</label>
                        <input type="number" step="0.01" min="0" name="price_quarterly" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Order</label>
                        <input type="number" min="0" name="display_order" class="form-control" value="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createPricingActive" checked>
                        <label class="form-check-label" for="createPricingActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

