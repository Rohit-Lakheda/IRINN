@extends('superadmin.layout')

@section('title', 'IX Location History')

@section('content')
<style>
    @media (max-width: 767.98px) {
        .app-details .col-md-6:nth-child(odd), .app-details .col-md-12:nth-child(odd) {
            border-bottom: none;
            margin-bottom: 0px;
        }
        .app-details .col-md-6:nth-child(even), .app-details .col-md-12:nth-child(even) {
            border-bottom: 1px solid #e4e5f5;
        }
    }
</style>
<div class="container-fluid px-2 py-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-0 border-0">Change History: {{ $ixLocation->name }}</h2>
            <p class="text-muted mb-1">View all changes made to this IX location.</p>
        </div>
        <div>
            <a href="{{ route('superadmin.ix-locations.index') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left fs-6"></i> Back to Locations
            </a>
        </div>
    </div>
    <div class="accent-line"></div>

    <!-- Location Details Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0" style="font-weight: 600;">Current Location Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="row app-details">
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Name:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>State:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->state }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>City:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->city ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Node Type:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><span class="badge {{ $ixLocation->node_type === 'metro' ? 'bg-primary' : 'bg-warning text-dark' }}">{{ strtoupper($ixLocation->node_type) }}</span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Switch Details:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->switch_details ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Ports:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->ports ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Nodal Officer:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->nodal_officer ?? '—' }}</p>
                        </div>
                        <div class="col-md-6 border-bottom-0">
                            <p class="mb-0"><strong>Zone:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->zone ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row app-details">
                        
                        <div class="col-md-6">
                            <p class="mb-0"><strong>P2P Capacity:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->p2p_capacity ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>P2P Provider:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->p2p_provider ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Connected Main Node:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->connected_main_node ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>P2P ARC:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->p2p_arc ? '₹' . number_format($ixLocation->p2p_arc, 2) : '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Colocation Provider:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->colocation_provider ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Colocation ARC:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0">{{ $ixLocation->colocation_arc ? '₹' . number_format($ixLocation->colocation_arc, 2) : '—' }}</p>
                        </div>
                        <div class="col-md-6 border-bottom-0">
                            <p class="mb-0"><strong>Status:</strong></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><span class="badge {{ $ixLocation->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $ixLocation->is_active ? 'Active' : 'Hidden' }}</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- History Table -->
    <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
            <h5 class="mb-0 fw-semibold">Change History ({{ $history->count() }})</h5>
        </div>
        <div class="card-body">
            @if($history->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="text-nowrap">
                            <tr>
                                <th>Change Type</th>
                                <th>Updated By</th>
                                <th>Changes</th>
                                <th>Changed On</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $entry)
                            <tr class="align-middle">
                                <td>
                                    <span class="badge 
                                        @if($entry->change_type === 'created') bg-success
                                        @elseif($entry->change_type === 'updated') bg-primary
                                        @elseif($entry->change_type === 'activated') bg-info
                                        @elseif($entry->change_type === 'deactivated') bg-warning text-dark
                                        @elseif($entry->change_type === 'deleted') bg-danger
                                        @else bg-secondary
                                        @endif">
                                        {{ ucfirst($entry->change_type) }}
                                    </span>
                                </td>
                                <td>{{ $entry->updated_by ?? 'System' }}</td>
                                <td>
                                    @if($entry->notes)
                                        <small>{{ $entry->notes }}</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $entry->created_at->format('d M Y, h:i A') }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#details{{ $entry->id }}" aria-expanded="false">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="details{{ $entry->id }}">
                                <td colspan="5">
                                    <div class="p-3 bg-light rounded">
                                        @if($entry->old_data && $entry->new_data)
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="text-danger">Old Values:</h6>
                                                    <pre class="small mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($entry->old_data, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                                <div class="col-md-6">
                                                    <h6 class="text-success">New Values:</h6>
                                                    <pre class="small mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($entry->new_data, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            </div>
                                        @elseif($entry->new_data)
                                            <h6 class="text-success">Created Data:</h6>
                                            <pre class="small mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($entry->new_data, JSON_PRETTY_PRINT) }}</pre>
                                        @elseif($entry->old_data)
                                            <h6 class="text-danger">Deleted Data:</h6>
                                            <pre class="small mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($entry->old_data, JSON_PRETTY_PRINT) }}</pre>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5 text-muted">
                    <p>No change history available for this location.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
