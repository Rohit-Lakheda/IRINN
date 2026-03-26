@extends('user.layout')

@section('title', 'Request Plan Change')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <h1>Request Plan Change</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('user.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user.applications.index') }}">Applications</a></li>
                <li class="breadcrumb-item"><a href="{{ route('user.applications.show', $application->id) }}">{{ $application->application_id }}</a></li>
                <li class="breadcrumb-item active">Request Plan Change</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        @if($approvedNotEffective)
        <div class="alert alert-info mb-4">
            <h6 class="alert-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                </svg>
                Plan Change Already Approved
            </h6>
            <p class="mb-2">
                You have an approved plan change that will take effect on <strong>{{ \Carbon\Carbon::parse($approvedNotEffective->effective_from)->format('d/m/Y') }}</strong>.
            </p>
            <p class="mb-0">
                <strong>Change:</strong> {{ $approvedNotEffective->current_port_capacity }} → {{ $approvedNotEffective->new_port_capacity }} 
                ({{ strtoupper($approvedNotEffective->new_billing_plan) }})
            </p>
            <p class="mb-0 mt-2">
                <small>Your plan will be automatically updated on the effective date. You cannot request another change until then.</small>
            </p>
        </div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Current Plan Details</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Port Capacity:</dt>
                    <dd class="col-sm-8"><strong>{{ $application->assigned_port_capacity ?? ($portSelection['capacity'] ?? 'N/A') }}</strong></dd>
                    <dt class="col-sm-4">Billing Plan:</dt>
                    <dd class="col-sm-8">{{ strtoupper($portSelection['billing_plan'] ?? 'N/A') }}</dd>
                    <dt class="col-sm-4">Current Amount:</dt>
                    <dd class="col-sm-8">₹{{ number_format($portSelection['amount'] ?? 0, 2) }}</dd>
                </dl>
            </div>
        </div>

        @if($approvedNotEffective)
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Request New Plan</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Cannot Submit New Request:</strong> You have an approved plan change that will take effect on {{ \Carbon\Carbon::parse($approvedNotEffective->effective_from)->format('d/m/Y') }}. 
                    Please wait until that date to request another change.
                </div>
                <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-secondary">Back to Application</a>
            </div>
        </div>
        @else
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Request New Plan</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('user.plan-change.store', $application->id) }}">
                    @csrf
                    
                    <div class="mb-3">
                        <label for="new_port_capacity" class="form-label">New Port Capacity <span class="text-danger">*</span></label>
                        <select name="new_port_capacity" id="new_port_capacity" class="form-select @error('new_port_capacity') is-invalid @enderror" required>
                            <option value="">Select Port Capacity</option>
                            @foreach($availablePorts as $port)
                            <option value="{{ $port->port_capacity }}" 
                                data-arc="{{ $port->price_arc }}" 
                                data-mrc="{{ $port->price_mrc }}" 
                                data-quarterly="{{ $port->price_quarterly }}"
                                {{ old('new_port_capacity') === $port->port_capacity ? 'selected' : '' }}>
                                {{ $port->port_capacity }}
                            </option>
                            @endforeach
                        </select>
                        @error('new_port_capacity')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="new_billing_plan" class="form-label">New Billing Plan <span class="text-danger">*</span></label>
                        <select name="new_billing_plan" id="new_billing_plan" class="form-select @error('new_billing_plan') is-invalid @enderror" required>
                            <option value="">Select Billing Plan</option>
                            <option value="arc" {{ old('new_billing_plan') === 'arc' ? 'selected' : '' }}>Annual (ARC)</option>
                            <option value="mrc" {{ old('new_billing_plan') === 'mrc' ? 'selected' : '' }}>Monthly (MRC)</option>
                            <option value="quarterly" {{ old('new_billing_plan') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                        @error('new_billing_plan')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Amount:</label>
                        <div class="alert alert-info" id="new_amount_display">
                            Select port capacity and billing plan to see the amount.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Adjustment Amount:</label>
                        <div class="alert" id="adjustment_amount_display">
                            Select port capacity and billing plan to see the adjustment amount.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason for Change <span class="text-danger">*</span></label>
                        <textarea name="reason" id="reason" rows="4" class="form-control @error('reason') is-invalid @enderror" required placeholder="Please provide a reason for this plan change (minimum 10 characters)">{{ old('reason') }}</textarea>
                        @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 10 characters required.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Submit Request</button>
                        <a href="{{ route('user.applications.show', $application->id) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const portSelect = document.getElementById('new_port_capacity');
    const planSelect = document.getElementById('new_billing_plan');
    const newAmountDisplay = document.getElementById('new_amount_display');
    const adjustmentDisplay = document.getElementById('adjustment_amount_display');
    
    const currentAmount = {{ $portSelection['amount'] ?? 0 }};
    
    function updateAmounts() {
        const selectedOption = portSelect.options[portSelect.selectedIndex];
        const plan = planSelect.value;
        
        if (!selectedOption.value || !plan) {
            newAmountDisplay.textContent = 'Select port capacity and billing plan to see the amount.';
            newAmountDisplay.className = 'alert alert-info';
            adjustmentDisplay.textContent = 'Select port capacity and billing plan to see the adjustment amount.';
            adjustmentDisplay.className = 'alert';
            return;
        }
        
        const amount = parseFloat(selectedOption.getAttribute(`data-${plan}`)) || 0;
        const adjustment = amount - currentAmount;
        
        newAmountDisplay.textContent = `₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        newAmountDisplay.className = 'alert alert-success';
        
        if (adjustment > 0) {
            adjustmentDisplay.textContent = `+₹${adjustment.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (Upgrade - Additional payment required)`;
            adjustmentDisplay.className = 'alert alert-warning';
        } else if (adjustment < 0) {
            adjustmentDisplay.textContent = `₹${Math.abs(adjustment).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})} (Downgrade - Credit will be applied)`;
            adjustmentDisplay.className = 'alert alert-info';
        } else {
            adjustmentDisplay.textContent = '₹0.00 (No change)';
            adjustmentDisplay.className = 'alert alert-secondary';
        }
    }
    
    portSelect.addEventListener('change', updateAmounts);
    planSelect.addEventListener('change', updateAmounts);
    
    // Initial update if values are pre-selected
    if (portSelect.value && planSelect.value) {
        updateAmounts();
    }
});
</script>
@endpush
@endsection
