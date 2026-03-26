@extends('superadmin.layout')

@section('title', 'IX Locations')

@section('content')
<div class="container-fluid px-2 py-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-0 border-0">IX Location Directory</h2>
            <p class="text-muted mb-1">Manage available NIXI IX nodes and visibility.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary text-white">
                <i class="bi bi-arrow-left fs-6"></i> Back to Dashboard
            </a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                + Add Location
            </button>
        </div>
    </div>
    <div class="accent-line"></div>

    <div class="card border-0 shadow-sm ">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th>Name</th>
                            <th>State</th>
                            <th>Node Type</th>
                            <th>Switch Details</th>
                            <th>Ports</th>
                            <th>Nodal Officer</th>
                            <th>Zone</th>
                            <th>Status</th>
                            <th class="text-end pe-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($locations as $location)
                        <tr class="align-middle">
                            <td>{{ $location->name }}</td>
                            <td>{{ $location->state }}</td>
                            <td>
                                <span class="badge {{ $location->node_type === 'metro' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                    {{ strtoupper($location->node_type) }}
                                </span>
                            </td>
                            <td>{{ $location->switch_details ?? '—' }}</td>
                            <td>{{ $location->ports ?? '—' }}</td>
                            <td>{{ $location->nodal_officer ?? '—' }}</td>
                            <td>{{ $location->zone ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $location->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $location->is_active ? 'Active' : 'Hidden' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm gap-2">
                                    <a href="{{ route('superadmin.ix-locations.history', $location) }}" class="btn btn-info rounded btn-sm text-white" title="View History">
                                        History
                                    </a>
                                    <button class="btn btn-sm btn-primary rounded" data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $location->id }}">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" action="{{ route('superadmin.ix-locations.toggle', $location) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn rounded btn-sm {{ $location->is_active ? 'theme-bg-yellow' : 'btn-success' }}">
                                            {{ $location->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('superadmin.ix-locations.destroy', $location) }}" onsubmit="return confirm('Delete this location?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="editLocationModal{{ $location->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content border-c-blue">
                                    <div class="modal-header theme-bg-blue text-white">
                                        <h5 class="modal-title text-white" style="color: #fff !important;">Update {{ $location->name }}</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" action="{{ route('superadmin.ix-locations.update', $location) }}" class="theme-forms">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body theme-forms">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label">Name</label>
                                                    <input type="text" class="form-control" name="name" value="{{ old('name', $location->name) }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">State</label>
                                                    <input type="text" class="form-control" name="state" value="{{ old('state', $location->state) }}" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">City</label>
                                                    <input type="text" class="form-control" name="city" value="{{ old('city', $location->city) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Node Type</label>
                                                    <select name="node_type" class="form-select" required>
                                                        <option value="metro" @selected($location->node_type === 'metro')>Metro</option>
                                                        <option value="edge" @selected($location->node_type === 'edge')>Edge</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Switch Details</label>
                                                    <input type="text" class="form-control" name="switch_details" value="{{ old('switch_details', $location->switch_details) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Ports</label>
                                                    <input type="number" class="form-control" name="ports" min="1" value="{{ old('ports', $location->ports) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Nodal Officer</label>
                                                    <input type="text" class="form-control" name="nodal_officer" value="{{ old('nodal_officer', $location->nodal_officer) }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Zone</label>
                                                    <input type="text" class="form-control" name="zone" value="{{ old('zone', $location->zone) }}">
                                                </div>
                                                <div class="col-12">
                                                    <hr class="my-2">
                                                    <h6 class="text-muted mt-3">Admin-Only Details (Not visible to users)</h6>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">P2P Capacity</label>
                                                    <input type="text" class="form-control" name="p2p_capacity" value="{{ old('p2p_capacity', $location->p2p_capacity) }}" placeholder="e.g. 80G">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">P2P Provider</label>
                                                    <input type="text" class="form-control" name="p2p_provider" value="{{ old('p2p_provider', $location->p2p_provider) }}" placeholder="e.g. ANI Broadband">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Connected Main Node</label>
                                                    <input type="text" class="form-control" name="connected_main_node" value="{{ old('connected_main_node', $location->connected_main_node) }}" placeholder="e.g. New Delhi GK">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">P2P ARC (₹)</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" name="p2p_arc" value="{{ old('p2p_arc', $location->p2p_arc) }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Colocation Provider</label>
                                                    <input type="text" class="form-control" name="colocation_provider" value="{{ old('colocation_provider', $location->colocation_provider) }}" placeholder="e.g. Netmagic">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Colocation ARC (₹)</label>
                                                    <input type="number" step="0.01" min="0" class="form-control" name="colocation_arc" value="{{ old('colocation_arc', $location->colocation_arc) }}" placeholder="0.00">
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-check mt-1">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="locationActive{{ $location->id }}" @checked($location->is_active)>
                                                        <label class="form-check-label" for="locationActive{{ $location->id }}">Active</label>
                                                    </div>
                                                </div>
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
                            <td colspan="9" class="text-center py-5 text-muted">
                                No IX locations available. Create the first entry.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #fff !important;">Add IX Location</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.ix-locations.store') }}" class="theme-forms">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Node Type</label>
                            <select name="node_type" class="form-select" required>
                                <option value="metro">Metro</option>
                                <option value="edge">Edge</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Switch Details</label>
                            <input type="text" class="form-control" name="switch_details">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ports</label>
                            <input type="number" class="form-control" name="ports" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nodal Officer</label>
                            <input type="text" class="form-control" name="nodal_officer">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zone</label>
                            <input type="text" class="form-control" name="zone">
                        </div>
                        <div class="col-12">
                            <hr class="my-2">
                            <h6 class="text-muted mt-3">Admin-Only Details (Not visible to users)</h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">P2P Capacity</label>
                            <input type="text" class="form-control" name="p2p_capacity" placeholder="e.g. 80G">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">P2P Provider</label>
                            <input type="text" class="form-control" name="p2p_provider" placeholder="e.g. ANI Broadband">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Connected Main Node</label>
                            <input type="text" class="form-control" name="connected_main_node" placeholder="e.g. New Delhi GK">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">P2P ARC (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="p2p_arc" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Colocation Provider</label>
                            <input type="text" class="form-control" name="colocation_provider" placeholder="e.g. Netmagic">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Colocation ARC (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="colocation_arc" placeholder="0.00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Location</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

