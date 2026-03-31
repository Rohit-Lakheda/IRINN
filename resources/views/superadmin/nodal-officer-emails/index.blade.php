@extends('superadmin.layout')

@section('title', 'Nodal Officer Emails')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1" style="color: #2c3e50; font-weight: 600;">Nodal Officer Emails</h2>
            <p class="text-muted mb-0">Manage nodal officer emails for BCC in overdue invoice reminder emails.</p>
            <div class="accent-line"></div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold">Add New Nodal Officer Email</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('superadmin.nodal-officer-emails.store') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nodal Officer Name <span class="text-danger">*</span></label>
                                <select name="name" class="form-select" id="nodalOfficerName" required>
                                    <option value="">Select Nodal Officer</option>
                                    @foreach($nodalOfficerNames as $officerName)
                                        <option value="{{ $officerName }}" {{ old('name') === $officerName ? 'selected' : '' }}>
                                            {{ $officerName }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select from locations</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required placeholder="e.g., chirag.vasani@nixi.in">
                                @error('email')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Email address for BCC</small>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Order</label>
                                <input type="number" name="order" class="form-control" value="{{ old('order', 0) }}" min="0" placeholder="0">
                                @error('order')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active_new" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active_new">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Add Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h6 class="mb-0 fw-semibold">Nodal Officer Emails List</h6>
                </div>
                <div class="card-body p-4">
                    @if($nodalOfficerEmails->isEmpty())
                        <div class="text-center py-5">
                            <p class="text-muted mb-0">No nodal officer emails configured yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($nodalOfficerEmails as $email)
                                        <tr>
                                            <td>{{ $email->order }}</td>
                                            <td>{{ $email->name }}</td>
                                            <td>{{ $email->email }}</td>
                                            <td>
                                                @if($email->is_active)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $email->id }}">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <form action="{{ route('superadmin.nodal-officer-emails.toggle-status', $email->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-{{ $email->is_active ? 'warning' : 'success' }}"
                                                        data-confirm-text="{{ $email->is_active ? 'Deactivate' : 'Activate' }} this email?"
                                                        onclick="return confirm(this.dataset.confirmText)"
                                                    >
                                                        <i class="bi bi-{{ $email->is_active ? 'x-circle' : 'check-circle' }}"></i> {{ $email->is_active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                                <form action="{{ route('superadmin.nodal-officer-emails.destroy', $email->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this email?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal{{ $email->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Nodal Officer Email</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST" action="{{ route('superadmin.nodal-officer-emails.update', $email->id) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Nodal Officer Name <span class="text-danger">*</span></label>
                                                                <select name="name" class="form-select" required>
                                                                    <option value="">Select Nodal Officer</option>
                                                                    @foreach($nodalOfficerNames as $officerName)
                                                                        <option value="{{ $officerName }}" {{ old('name', $email->name) === $officerName ? 'selected' : '' }}>
                                                                            {{ $officerName }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                @error('name')
                                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                                @enderror
                                                                <small class="text-muted">Select from locations</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                                                <input type="email" name="email" class="form-control" value="{{ old('email', $email->email) }}" required>
                                                                @error('email')
                                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                                @enderror
                                                                <small class="text-muted">Email address for BCC</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label fw-semibold">Order</label>
                                                                <input type="number" name="order" class="form-control" value="{{ old('order', $email->order) }}" min="0">
                                                                @error('order')
                                                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                            <div class="mb-3">
                                                                <div class="form-check form-switch">
                                                                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active{{ $email->id }}" value="1" {{ old('is_active', $email->is_active) ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="is_active{{ $email->id }}">Active</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
