@extends('admin.layout')

@section('title', 'Invoices')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Invoices</h2>
            <p class="text-muted mb-0">View and manage all IX application invoices</p>
        </div>
    </div>

    <!-- Filters and Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.invoices.index') }}" class="row g-3 theme-forms">
                        <!-- Search -->
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Search</label>
                            <input type="text" 
                                   name="search" 
                                   class="form-control" 
                                   placeholder="Search by invoice number, application ID, membership ID, customer ID, user name, email..."
                                   value="{{ request('search') }}">
                        </div>
                        
                        <!-- Filters Row 1 -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Invoice Number</label>
                            <input type="text" 
                                   name="invoice_number" 
                                   class="form-control" 
                                   placeholder="Invoice number..."
                                   value="{{ request('invoice_number') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Membership ID</label>
                            <input type="text" 
                                   name="membership_id" 
                                   class="form-control" 
                                   placeholder="Membership ID..."
                                   value="{{ request('membership_id') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('payment_status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="overdue" {{ request('payment_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Invoice Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        
                        <!-- Filters Row 2 -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Timeline</label>
                            <select name="timeline" class="form-select">
                                <option value="">All Time</option>
                                <option value="today" {{ request('timeline') === 'today' ? 'selected' : '' }}>Today</option>
                                <option value="this_week" {{ request('timeline') === 'this_week' ? 'selected' : '' }}>This Week</option>
                                <option value="this_month" {{ request('timeline') === 'this_month' ? 'selected' : '' }}>This Month</option>
                                <option value="last_month" {{ request('timeline') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                <option value="this_year" {{ request('timeline') === 'this_year' ? 'selected' : '' }}>This Year</option>
                                <option value="last_year" {{ request('timeline') === 'last_year' ? 'selected' : '' }}>Last Year</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Date From</label>
                            <input type="date" 
                                   name="date_from" 
                                   class="form-control" 
                                   value="{{ request('date_from') }}">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Date To</label>
                            <input type="date" 
                                   name="date_to" 
                                   class="form-control" 
                                   value="{{ request('date_to') }}">
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="col-md-12 col-lg-3 d-flex align-items-end">
                            <button type="submit" class="btn theme-bg-blue text-white w-100 me-2 text-nowrap1">Apply Filters</button>
                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-danger w-100">Clear</a>
                        </div>
                        
                        @if(request()->anyFilled(['search', 'invoice_number', 'membership_id', 'payment_status', 'status', 'timeline', 'date_from', 'date_to']))
                            <div class="col-12">
                                <small class="text-muted">
                                    Active filters: 
                                    @if(request('search'))<span class="badge bg-info me-1">{{ request('search') }}</span>@endif
                                    @if(request('invoice_number'))<span class="badge bg-info me-1">Invoice: {{ request('invoice_number') }}</span>@endif
                                    @if(request('membership_id'))<span class="badge bg-info me-1">Membership: {{ request('membership_id') }}</span>@endif
                                    @if(request('payment_status'))<span class="badge bg-info me-1">Payment: {{ ucfirst(request('payment_status')) }}</span>@endif
                                    @if(request('status'))<span class="badge bg-info me-1">Status: {{ ucfirst(request('status')) }}</span>@endif
                                    @if(request('timeline'))<span class="badge bg-info me-1">Timeline: {{ ucfirst(str_replace('_', ' ', request('timeline'))) }}</span>@endif
                                    @if(request('date_from'))<span class="badge bg-info me-1">From: {{ request('date_from') }}</span>@endif
                                    @if(request('date_to'))<span class="badge bg-info me-1">To: {{ request('date_to') }}</span>@endif
                                </small>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="row">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold text-capitalize">Invoices List</h5>
                </div>
                <div class="card-body">
                    @if($invoices->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">Invoice Details</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Application & User</th>
                                        <th style="color: #2c3e50; font-weight: 600; min-width: 160px;">Dates</th>
                                        <th style="color: #2c3e50; font-weight: 600; min-width: 200px;">Financial Details</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Status</th>
                                        <th class="text-end pe-3" style="color: #2c3e50; font-weight: 600;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoices as $invoice)
                                        <tr class="align-middle">
                                            <td>
                                                <div class="mb-1">
                                                    <strong style="color: #2c3e50; font-size: 1rem;">{{ $invoice->invoice_number }}</strong>
                                                </div>
                                                @if($invoice->billing_period)
                                                    <div class="mb-1">
                                                        <small class="text-muted">Period:</small>
                                                        <span class="text-dark">{{ $invoice->billing_period }}</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Application ID:</small>
                                                    <a href="{{ route('admin.applications.show', $invoice->application->id) }}" 
                                                       class="text-primary text-decoration-none fw-bold">
                                                        {{ $invoice->application->application_id }}
                                                    </a>
                                                </div>
                                                @if($invoice->application->membership_id)
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Membership ID:</small>
                                                        <a href="{{ route('admin.applications.show', $invoice->application->id) }}" 
                                                           class="text-primary text-decoration-none fw-bold">
                                                            {{ $invoice->application->membership_id }}
                                                        </a>
                                                    </div>
                                                @endif
                                                @if($invoice->application->customer_id)
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">Customer ID:</small>
                                                        <span class="fw-bold">{{ $invoice->application->customer_id }}</span>
                                                    </div>
                                                @endif
                                                <div class="mb-1">
                                                    <small class="text-muted d-block">User:</small>
                                                    <div>
                                                        <strong>{{ $invoice->application->user->fullname ?? 'N/A' }}</strong>
                                                    </div>
                                                    <small class="text-muted">{{ $invoice->application->user->email ?? 'N/A' }}</small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Invoice Date:</small>
                                                    <span class="text-nowrap fw-bold">{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : 'N/A' }}</span>
                                                </div>
                                                <div class="mb-1">
                                                    <small class="text-muted d-block">Due Date:</small>
                                                    @if($invoice->due_date)
                                                        <span class="{{ $invoice->due_date->isPast() && $invoice->payment_status !== 'paid' ? 'text-danger fw-bold' : '' }} text-nowrap fw-bold">
                                                            {{ $invoice->due_date->format('d M Y') }}
                                                        </span>
                                                        @if($invoice->due_date->isPast() && $invoice->payment_status !== 'paid')
                                                            <br><small class="text-danger">Overdue</small>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Total Amount:</small>
                                                    <strong style="color: #2B2F6C; font-size: 1rem;">₹{{ number_format($invoice->total_amount, 2) }}</strong>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Paid Amount:</small>
                                                    <strong class="text-success">₹{{ number_format($invoice->paid_amount ?? 0, 2) }}</strong>
                                                </div>
                                                <div class="mb-2">
                                                    <small class="text-muted d-block">Balance:</small>
                                                    <strong class="text-danger">₹{{ number_format($invoice->balance_amount ?? $invoice->total_amount, 2) }}</strong>
                                                </div>
                                                @if($invoice->has_carry_forward && $invoice->forwarded_amount > 0)
                                                    <div class="mb-1">
                                                        <small class="text-muted d-block">Carry Forward:</small>
                                                        <strong class="text-warning">₹{{ number_format($invoice->forwarded_amount, 2) }}</strong>
                                                        @if($invoice->forwarded_to_invoice_date)
                                                            <br><small class="text-muted">Forwarded: {{ $invoice->forwarded_to_invoice_date->format('d M Y') }}</small>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if($invoice->isCancelled())
                                                    <span class="badge bg-secondary" style="font-size: 0.875rem;">Cancelled</span>
                                                @elseif($invoice->hasCreditNote())
                                                    <span class="badge bg-info" style="font-size: 0.875rem;">Credit Note</span>
                                                @else
                                                    @php
                                                        $statusClass = match($invoice->payment_status) {
                                                            'paid' => 'bg-success',
                                                            'partial' => 'bg-warning text-dark',
                                                            'overdue' => 'bg-danger',
                                                            default => 'theme-bg-yellow text-blue',
                                                        };
                                                    @endphp
                                                    <span class="badge text-capitalize {{ $statusClass }}" style="font-size: 0.875rem;">
                                                        {{ ucfirst($invoice->payment_status ?? 'Pending') }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($invoice->hasCreditNote())
                                                    <div class="d-flex flex-column gap-1 align-items-end">
                                                        <a href="{{ route('admin.invoices.download', ['id' => $invoice->id, 'type' => 'credit_note']) }}" 
                                                           class="btn btn-sm btn-info" 
                                                           title="Download Credit Note PDF">
                                                           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
                                                                <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
                                                                <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                                                            </svg>
                                                            Credit Note
                                                        </a>
                                                        <a href="{{ route('admin.invoices.download', ['id' => $invoice->id, 'type' => 'invoice']) }}" 
                                                           class="btn btn-sm btn-primary" 
                                                           title="Download Invoice PDF">
                                                           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
                                                                <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
                                                                <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                                                            </svg>
                                                            Invoice
                                                        </a>
                                                    </div>
                                                @else
                                                    <a href="{{ route('admin.invoices.download', $invoice->id) }}" 
                                                       class="btn btn-sm btn-primary" 
                                                       title="Download Invoice">
                                                       <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud-arrow-down" viewBox="0 0 16 16">
                                                            <path fill-rule="evenodd" d="M7.646 10.854a.5.5 0 0 0 .708 0l2-2a.5.5 0 0 0-.708-.708L8.5 9.293V5.5a.5.5 0 0 0-1 0v3.793L6.354 8.146a.5.5 0 1 0-.708.708z"/>
                                                            <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383m.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                                                        </svg>
                                                        Download
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $invoices->links('vendor.pagination.bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="currentColor" class="text-muted mb-3" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                            </svg>
                            <h5 class="text-muted">No invoices found</h5>
                            <p class="text-muted">Try adjusting your filters to see more results.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

