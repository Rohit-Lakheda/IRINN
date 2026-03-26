@extends('admin.layout')

@section('title', 'Reactivation Requests')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1 fw-semibold">Reactivation Requests</h2>
            <p class="text-muted mb-0">Manage reactivation requests for disconnected applications</p>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reactivation-requests.index') }}" class="row g-3 theme-forms">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Search</label>
                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="App ID / Membership ID / User (name, email, mobile) / Invoice no."
                                value="{{ $search ?? '' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="invoiced" {{ $status === 'invoiced' ? 'selected' : '' }}>Invoiced</option>
                                <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Service Status</label>
                            <select name="service_status" class="form-select">
                                <option value="all" {{ ($serviceStatus ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="live" {{ ($serviceStatus ?? '') === 'live' ? 'selected' : '' }}>Live</option>
                                <option value="suspended" {{ ($serviceStatus ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="disconnected" {{ ($serviceStatus ?? '') === 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Invoice / Payment</label>
                            <select name="payment_status" class="form-select">
                                <option value="all" {{ ($paymentStatus ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                                <option value="none" {{ ($paymentStatus ?? '') === 'none' ? 'selected' : '' }}>No Invoice</option>
                                <option value="unpaid" {{ ($paymentStatus ?? '') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                <option value="paid" {{ ($paymentStatus ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ ($paymentStatus ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Requested From</label>
                            <input type="date" name="requested_from" class="form-control" value="{{ $requestedFrom ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Requested To</label>
                            <input type="date" name="requested_to" class="form-control" value="{{ $requestedTo ?? '' }}">
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('admin.reactivation-requests.index') }}" class="btn btn-danger">Clear</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Requests ({{ $requests->total() }})</h5>
                </div>
                <div class="card-body">
                    @if($requests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th>Application</th>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Invoice</th>
                                        <th>Requested At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $req)
                                        <tr class="align-middle">
                                            <td>
                                                <a href="{{ route('admin.applications.show', $req->application_id) }}" class="text-decoration-none">
                                                    <strong>{{ $req->application->application_id ?? 'N/A' }}</strong><br>
                                                    <small class="text-muted">Service: {{ strtoupper($req->application->service_status ?? 'LIVE') }}</small>
                                                </a>
                                            </td>
                                            <td>
                                                <strong>{{ $req->application->user->fullname ?? 'N/A' }}</strong><br>
                                                <small class="text-muted">{{ $req->application->user->email ?? '' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ in_array($req->status, ['pending']) ? 'warning text-dark' : (in_array($req->status, ['paid','completed']) ? 'success' : 'secondary') }}">
                                                    {{ strtoupper($req->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($req->invoice)
                                                    <strong>{{ $req->invoice->invoice_number }}</strong><br>
                                                    <small class="text-muted">{{ strtoupper($req->invoice->payment_status) }} • ₹{{ number_format((float) $req->invoice->total_amount, 2) }}</small>
                                                @else
                                                    <span class="text-muted">—</span>
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                {{ $req->created_at ? $req->created_at->format('d/m/Y H:i') : '—' }}
                                            </td>
                                            <td class="text-nowrap">
                                                @if($req->status === 'pending')
                                                    <form method="POST" action="{{ route('admin.reactivation-requests.approve', $req->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve and generate reactivation invoice?')">Approve</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.reactivation-requests.reject', $req->id) }}" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this reactivation request?')">Reject</button>
                                                    </form>
                                                @endif

                                                @if($req->status === 'paid')
                                                    <form method="POST" action="{{ route('admin.reactivation-requests.set-date', $req->id) }}" class="d-inline-flex align-items-center gap-2">
                                                        @csrf
                                                        <input type="date" name="reactivation_date" class="form-control form-control-sm" required>
                                                        <button type="submit" class="btn btn-sm btn-primary">Set Date</button>
                                                    </form>
                                                @endif

                                                @if($req->status === 'invoiced')
                                                    <span class="text-muted small">Waiting for payment</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            {{ $requests->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <div class="mb-2 text-muted">No reactivation requests found.</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

