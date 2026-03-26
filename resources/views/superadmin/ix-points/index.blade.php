@extends('superadmin.layout')

@section('title', 'IX Points')

@section('content')
<div class="container-fluid px-2 py-0">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                <div>
                    <h2 class="mb-1 border-0">IX Points</h2>
                    <p class="text-muted mb-1">
                        @if($nodeType)
                            {{ ucfirst($nodeType) }} IX Points
                        @else
                            All Active IX Points
                        @endif
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="accent-line"></div>

    <!-- Filter Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-tabs theme-nav-pills border-bottom-0">
                <li class="nav-item">
                    <a class="nav-link {{ !$nodeType ? 'active' : '' }}" href="{{ route('superadmin.ix-points') }}">
                        All Points ({{ $locations->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $nodeType === 'edge' ? 'active' : '' }}" href="{{ route('superadmin.ix-points', ['node_type' => 'edge']) }}">
                        Edge Points ({{ $locations->where('node_type', 'edge')->count() }})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $nodeType === 'metro' ? 'active' : '' }}" href="{{ route('superadmin.ix-points', ['node_type' => 'metro']) }}">
                        Metro Points ({{ $locations->where('node_type', 'metro')->count() }})
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- IX Points Grid View -->
    <div class="row">
        @if($locations->count() > 0)
            @foreach($locations as $location)
            @php
                $stats = $locationStats[$location->id] ?? ['total_applications' => 0, 'approved_applications' => 0, 'pending_applications' => 0, 'rejected_applications' => 0];
            @endphp
            <div class="col-md-4 col-lg-3 mb-4">
                <a href="{{ route('superadmin.ix-points.show', $location->id) }}" class="text-decoration-none">
                    <div class="card border-c-blue shadow-sm h-100" style="border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                         onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 16px rgba(0,0,0,0.15)'"
                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                                <div class="mb-2">
                                    <h5 class="mb-1 fw-semibold">{{ $location->name }}</h5>
                                    @if($location->node_type === 'metro')
                                        <span class="badge bg-success">{{ ucfirst($location->node_type) }}</span>
                                    @else
                                        <span class="badge bg-info">{{ ucfirst($location->node_type) }}</span>
                                    @endif
                                </div>
                                <div>
                                    @if($location->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-1 text-muted small"><strong>State:</strong> {{ $location->state }}</p>
                                @if($location->city)
                                    <p class="mb-1 text-muted small"><strong>City:</strong> {{ $location->city }}</p>
                                @endif
                                @if($location->ports)
                                    <p class="mb-1 text-muted small"><strong>Ports:</strong> {{ $location->ports }}</p>
                                @endif
                            </div>
                            
                            <div class="border-top pt-3">
                                <h6 class="mb-2 text-nevy fw-semibold">Members Static</h6>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge bg-primary">Total: {{ $stats['total_applications'] }}</span>
                                    <span class="badge bg-success">Approved: {{ $stats['approved_applications'] }}</span>
                                    <span class="badge theme-bg-yellow text-blue">Pending: {{ $stats['pending_applications'] }}</span>
                                    <span class="badge bg-danger">Rejected: {{ $stats['rejected_applications'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        @else
            <div class="col-12">
                <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-0">No IX points found.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
