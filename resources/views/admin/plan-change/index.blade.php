@extends('admin.layout')

@section('title', 'Plan Change Requests')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
            <h2 class="mb-1" style="color:#2c3e50;font-weight:600;">Plan Change Requests</h2>
            <p class="text-muted mb-0">Manage user requests for plan upgrades and downgrades.</p>
    </div>

    <!-- Filters -->
    <div class="card border-c-blue shadow-sm mb-4" style="border-radius: 16px;">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.plan-change.index') }}" class="theme-forms">
                <div class="row gy-3">
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="change_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="upgrade" {{ request('change_type') === 'upgrade' ? 'selected' : '' }}>Upgrade</option>
                            <option value="downgrade" {{ request('change_type') === 'downgrade' ? 'selected' : '' }}>Downgrade</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('admin.plan-change.index') }}" class="btn btn-danger">Clear</a>
                        </div>
                    </div>
                </div>
                
                
                
            </form>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                            <th style="color: #2c3e50; font-weight: 600;">User</th>
                            <th style="color: #2c3e50; font-weight: 600;">Current Plan</th>
                            <th style="color: #2c3e50; font-weight: 600;">Requested Plan</th>
                            <th style="color: #2c3e50; font-weight: 600;">Change Type</th>
                            <th style="color: #2c3e50; font-weight: 600;">Adjustment</th>
                            <th style="color: #2c3e50; font-weight: 600;">Status</th>
                            <th style="color: #2c3e50; font-weight: 600;">Effective From</th>
                            <th style="color: #2c3e50; font-weight: 600;">Requested</th>
                            <th class="text-end pe-3" style="color: #2c3e50; font-weight: 600;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $request)
                        <tr class="align-middle">
                            <td>
                                <a href="{{ route('admin.applications.show', $request->application_id) }}" style="color: #0d6efd; text-decoration: none;">
                                    {{ $request->application->application_id }}
                                </a>
                            </td>
                            <td>{{ $request->user->fullname ?? 'N/A' }}</td>
                            <td>
                                <small>
                                    <strong>{{ $request->current_port_capacity ?? 'N/A' }}</strong><br>
                                    {{ strtoupper($request->current_billing_plan ?? 'N/A') }}<br>
                                    ₹{{ number_format($request->current_amount ?? 0, 2) }}
                                </small>
                            </td>
                            <td>
                                <small>
                                    <strong>{{ $request->new_port_capacity }}</strong><br>
                                    {{ strtoupper($request->new_billing_plan) }}<br>
                                    ₹{{ number_format($request->new_amount, 2) }}
                                </small>
                            </td>
                            <td>
                                @if($request->change_type === 'upgrade')
                                    <span class="badge bg-success">Upgrade</span>
                                @else
                                    <span class="badge bg-info">Downgrade</span>
                                @endif
                            </td>
                            <td>
                                @if($request->adjustment_amount > 0)
                                    <span class="text-danger">+₹{{ number_format($request->adjustment_amount, 2) }}</span>
                                @elseif($request->adjustment_amount < 0)
                                    <span class="text-success">₹{{ number_format(abs($request->adjustment_amount), 2) }}</span>
                                @else
                                    <span class="text-muted">₹0.00</span>
                                @endif
                            </td>
                            <td>
                                @if($request->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($request->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($request->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($request->status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($request->effective_from)
                                    {{ $request->effective_from->format('d M Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $request->created_at->format('d M Y') }}</td>
                            <td>
                                <div class="d-flex gap-1 align-items-center justify-content-end flex-wrap">
                                    <a href="{{ route('admin.plan-change.show', $request->id) }}" class="btn btn-sm btn-primary">View</a>
                                    @if(in_array(strtolower($request->status), ['pending', 'approved']))
                                    <form method="POST" action="{{ route('admin.plan-change.destroy', $request->id) }}" class="d-inline-block m-0" onsubmit="return confirm('Are you sure you want to delete this plan change request? This will allow the user to apply for a new plan change. This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                No plan change requests found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-center">
                {{ $requests->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection
