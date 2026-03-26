@extends('admin.layout')

@section('title', 'IX Points')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex1 justify-content-between align-items-center w-100">
                <div>
                    <h2 class="mb-1">IX Points</h2>
                    <p class="mb-0 text-muted">Manage and view all IX point locations</p>
                    <div class="accent-line"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-4">
                    <form method="GET" action="{{ route('admin.ix-points') }}" id="filterForm">
                        <div class="row g-3">
                            <!-- Search -->
                            <div class="col-md-4">
                                <label for="search" class="form-label small fw-semibold">Search</label>
                                <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                    value="{{ request('search') }}" placeholder="Search by name, state, city, etc.">
                            </div>

                            <!-- Node Type Filter -->
                            <div class="col-md-2">
                                <label for="node_type" class="form-label small fw-semibold">Node Type</label>
                                <select class="form-select form-select-sm" id="node_type" name="node_type">
                                    <option value="">All Types</option>
                                    <option value="metro" {{ request('node_type') === 'metro' ? 'selected' : '' }}>Metro</option>
                                    <option value="edge" {{ request('node_type') === 'edge' ? 'selected' : '' }}>Edge</option>
                                </select>
                            </div>

                            <!-- State Filter -->
                            <div class="col-md-2">
                                <label for="state" class="form-label small fw-semibold">State</label>
                                <select class="form-select form-select-sm" id="state" name="state">
                                    <option value="">All States</option>
                                    @foreach($states as $state)
                                        <option value="{{ $state }}" {{ request('state') === $state ? 'selected' : '' }}>{{ $state }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Zone Filter -->
                            <div class="col-md-2">
                                <label for="zone" class="form-label small fw-semibold">Zone</label>
                                <select class="form-select form-select-sm" id="zone" name="zone">
                                    <option value="">All Zones</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone }}" {{ request('zone') === $zone ? 'selected' : '' }}>{{ $zone }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-md-12 d-flex align-items-end gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary flex-fill1">
                                    <i class="bi bi-funnel"></i> Apply Filters
                                </button>
                                @if(request()->anyFilled(['search', 'node_type', 'state', 'zone']))
                                    <a href="{{ route('admin.ix-points') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                @endif
                            </div>
                        </div>

                        <!-- Active Filters -->
                        @if(request()->anyFilled(['search', 'node_type', 'state', 'zone']))
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <small class="text-muted fw-semibold">Active Filters:</small>
                                        @if(request('search'))
                                            <span class="badge bg-primary">
                                                Search: {{ request('search') }}
                                                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="text-white ms-1" style="text-decoration: none;">×</a>
                                            </span>
                                        @endif
                                        @if(request('node_type'))
                                            <span class="badge bg-info">
                                                Node Type: {{ ucfirst(request('node_type')) }}
                                                <a href="{{ request()->fullUrlWithQuery(['node_type' => null]) }}" class="text-white ms-1" style="text-decoration: none;">×</a>
                                            </span>
                                        @endif
                                        @if(request('state'))
                                            <span class="badge bg-success">
                                                State: {{ request('state') }}
                                                <a href="{{ request()->fullUrlWithQuery(['state' => null]) }}" class="text-white ms-1" style="text-decoration: none;">×</a>
                                            </span>
                                        @endif
                                        @if(request('zone'))
                                            <span class="badge bg-warning text-dark">
                                                Zone: {{ request('zone') }}
                                                <a href="{{ request()->fullUrlWithQuery(['zone' => null]) }}" class="text-dark ms-1" style="text-decoration: none;">×</a>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- IX Points Grid -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    @if($locations->count() > 0)
                        <div class="row g-3">
                            @foreach($locations as $location)
                                @php
                                    $stats = $locationStats[$location->id] ?? [
                                        'total_applications' => 0, 
                                        'live_applications' => 0,
                                        'approved_applications' => 0, 
                                        'pending_applications' => 0,
                                        'live_members' => 0,
                                        'not_live_members' => 0
                                    ];
                                @endphp
                                <div class="col-md-6 col-lg-4">
                                    <div class="card border border-c-blue h-100" style="border-radius: 12px; transition: all 0.3s; cursor: pointer;" 
                                         onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)';" 
                                         onmouseout="this.style.boxShadow='none';"
                                         onclick="showLocationDetails({{ $location->id }}, {{ json_encode($location->name) }}, {{ json_encode($location->node_type) }}, {{ json_encode($location->state) }}, {{ json_encode($location->city ?? 'N/A') }}, {{ json_encode($location->ports ?? 'N/A') }}, {{ json_encode($location->zone ?? 'N/A') }}, {{ json_encode($location->nodal_officer ?? 'N/A') }}, {{ json_encode($location->switch_details ?? 'N/A') }}, {{ $stats['pending_applications'] }}, {{ $stats['live_members'] }}, {{ $stats['not_live_members'] }})">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    {{ $location->name }}
                                                </h6>
                                                @if($location->node_type === 'metro')
                                                    <span class="badge bg-success">Metro</span>
                                                @else
                                                    <span class="badge bg-info">Edge</span>
                                                @endif
                                            </div>

                                            @if($location->switch_details)
                                                <p class="text-muted small mb-2">
                                                    <i class="bi bi-router"></i> {{ $location->switch_details }}
                                                </p>
                                            @endif

                                            <div class="row g-2 small mb-2">
                                                <div class="col-6">
                                                    <span class="text-muted">State:</span><br>
                                                    <strong>{{ $location->state }}</strong>
                                                </div>
                                                @if($location->city)
                                                    <div class="col-6">
                                                        <span class="text-muted">City:</span><br>
                                                        <strong>{{ $location->city }}</strong>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="row g-2 small mb-2">
                                                @if($location->ports)
                                                    <div class="col-6">
                                                        <span class="text-muted">Ports:</span><br>
                                                        <strong>{{ $location->ports }}</strong>
                                                    </div>
                                                @endif
                                                @if($location->zone)
                                                    <div class="col-6">
                                                        <span class="text-muted">Zone:</span><br>
                                                        <strong>{{ $location->zone }}</strong>
                                                    </div>
                                                @endif
                                            </div>

                                            @if($location->nodal_officer)
                                                <div class="small mb-2">
                                                    <span class="text-muted">Nodal Officer:</span><br>
                                                    <strong>{{ $location->nodal_officer }}</strong>
                                                </div>
                                            @endif

                                            <div class="border-top pt-2 mt-2">
                                                <div class="small">
                                                    <div class="mb-2">
                                                        <span class="text-muted d-block mb-1">Pending Applications:</span>
                                                        <a href="{{ route('admin.ix-points.applications', $location->id) }}" 
                                                           class="badge bg-warning text-dark text-decoration-none" 
                                                           onclick="event.stopPropagation();">
                                                            {{ $stats['pending_applications'] }} Applications
                                                        </a>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="text-muted d-block mb-1">Live Members:</span>
                                                        <a href="{{ route('admin.ix-points.members', $location->id) }}" 
                                                           class="badge bg-success text-decoration-none" 
                                                           onclick="event.stopPropagation();">
                                                            {{ $stats['live_members'] }} Members
                                                        </a>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted d-block mb-1">Not Live Members:</span>
                                                        <a href="{{ route('admin.ix-points.members', ['id' => $location->id, 'live' => 'false']) }}" 
                                                           class="badge bg-danger text-decoration-none" 
                                                           onclick="event.stopPropagation();">
                                                            {{ $stats['not_live_members'] }} Members
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mt-2">
                                                @if($location->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $locations->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mb-0 mt-3">No IX points found matching your criteria.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Location Details Modal -->
<div class="modal fade" id="locationDetailsModal" tabindex="-1" aria-labelledby="locationDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-c-blue" style="border-radius: 16px;">
            <div class="modal-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                <h5 class="modal-title text-white" id="locationDetailsModalLabel" style="color: #fff !important;">IX Point Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 app-details">
                    <div class="col-md-6">
                        <strong class="text-muted">Name:</strong>
                        <p class="mb-0" id="modal-name"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">Node Type:</strong>
                        <p class="mb-0" id="modal-node-type"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">State:</strong>
                        <p class="mb-0" id="modal-state"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">City:</strong>
                        <p class="mb-0" id="modal-city"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">Ports:</strong>
                        <p class="mb-0" id="modal-ports"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">Zone:</strong>
                        <p class="mb-0" id="modal-zone"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">Nodal Officer:</strong>
                        <p class="mb-0" id="modal-nodal-officer"></p>
                    </div>
                    <div class="col-md-6">
                        <strong class="text-muted">Switch Details:</strong>
                        <p class="mb-0" id="modal-switch-details"></p>
                    </div>
                </div>
                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <div class="card border-c-blue">
                            <div class="card-body text-center">
                                <h4 class="mb-1" id="modal-live-applications">0</h4>
                                <small class="text-muted">Pending Applications</small>
                                <div class="mt-2">
                                    <a href="#" id="modal-applications-link" class="btn btn-sm btn-primary">
                                        View All Applications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-c-blue">
                            <div class="card-body text-center">
                                <h4 class="mb-1" id="modal-live-members">0</h4>
                                <small class="text-muted">Live Members</small>
                                <div class="mt-2">
                                    <a href="#" id="modal-members-link" class="btn btn-sm btn-success">
                                        View All Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-c-blue">
                            <div class="card-body text-center">
                                <h4 class="mb-1" id="modal-not-live-members">0</h4>
                                <small class="text-muted">Not Live Members</small>
                                <div class="mt-2">
                                    <a href="#" id="modal-not-live-members-link" class="btn btn-sm btn-danger">
                                        View Not Live Members
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                <a href="#" id="modal-view-details-link" class="btn btn-primary">View Full Details</a>
            </div>
        </div>
    </div>
</div>

<script>
function showLocationDetails(locationId, name, nodeType, state, city, ports, zone, nodalOfficer, switchDetails, pendingApplications, liveMembers, notLiveMembers) {
    document.getElementById('modal-name').textContent = name;
    document.getElementById('modal-node-type').innerHTML = nodeType === 'metro' 
        ? '<span class="badge bg-success">Metro</span>' 
        : '<span class="badge bg-info">Edge</span>';
    document.getElementById('modal-state').textContent = state;
    document.getElementById('modal-city').textContent = city;
    document.getElementById('modal-ports').textContent = ports;
    document.getElementById('modal-zone').textContent = zone;
    document.getElementById('modal-nodal-officer').textContent = nodalOfficer;
    document.getElementById('modal-switch-details').textContent = switchDetails;
    document.getElementById('modal-live-applications').textContent = pendingApplications;
    document.getElementById('modal-live-members').textContent = liveMembers;
    document.getElementById('modal-not-live-members').textContent = notLiveMembers;
    
    document.getElementById('modal-applications-link').href = '{{ route("admin.ix-points.applications", ":id") }}'.replace(':id', locationId);
    document.getElementById('modal-members-link').href = '{{ route("admin.ix-points.members", ":id") }}'.replace(':id', locationId);
    document.getElementById('modal-not-live-members-link').href = '{{ route("admin.ix-points.members", ":id") }}'.replace(':id', locationId) + '?live=false';
    document.getElementById('modal-view-details-link').href = '{{ route("admin.ix-points.show", ":id") }}'.replace(':id', locationId);
    
    const modal = new bootstrap.Modal(document.getElementById('locationDetailsModal'));
    modal.show();
}
</script>
@endsection
