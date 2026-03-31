@extends('superadmin.layout')

@section('title', 'Grievance Management')

@section('content')
<div class="container-fluid px-2 py-0">
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-0 border-0">Grievance Management</h2>
            <p class="text-muted mb-1">Manage grievance categories, subcategories, assignments, and transfer rules for IRINN roles (Helpdesk, Hostmaster, Billing).</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.dashboard') }}" class="btn btn-primary text-white">
                <i class="bi bi-arrow-left fs-6"></i> Back to Dashboard
            </a>
        </div>
    </div>
    <div class="accent-line"></div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <ul class="nav nav-pills theme-nav-pills mb-4 mt-4" id="grievanceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                Categories
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="subcategories-tab" data-bs-toggle="tab" data-bs-target="#subcategories" type="button" role="tab">
                Subcategories
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                Assignments
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="transfer-rules-tab" data-bs-toggle="tab" data-bs-target="#transfer-rules" type="button" role="tab">
                Transfer Rules
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="escalation-settings-tab" data-bs-toggle="tab" data-bs-target="#escalation-settings" type="button" role="tab">
                Escalation Settings
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="workflow-setup-tab" data-bs-toggle="tab" data-bs-target="#workflow-setup" type="button" role="tab">
                Workflow Setup
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content" id="grievanceTabsContent">
        <!-- Categories Tab -->
        <div class="tab-pane fade show active" id="categories" role="tabpanel">
            <div class="card border-c-blue shadow-sm">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">Categories</h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                        + Add Category
                    </button>
                </div>
                <div class="card-body py-2">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th>Subcategories</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                <tr>
                                    <td>{{ $category->name }}</td>
                                    <td><code>{{ $category->slug }}</code></td>
                                    <td>{{ Str::limit($category->description ?? '—', 50) }}</td>
                                    <td class="text-center">{{ $category->order }}</td>
                                    <td>
                                        <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ $category->subcategories->count() }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm gap-2">
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" action="{{ route('superadmin.grievance-management.categories.delete', $category) }}" onsubmit="return confirm('Delete this category? All subcategories and assignments will also be deleted.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No categories found. Create one to get started.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subcategories Tab -->
        <div class="tab-pane fade" id="subcategories" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Subcategories</h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createSubcategoryModal">
                        + Add Subcategory
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Description</th>
                                    <th>Order</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    @foreach($category->subcategories as $subcategory)
                                    <tr>
                                        <td>{{ $category->name }}</td>
                                        <td>{{ $subcategory->name }}</td>
                                        <td><code>{{ $subcategory->slug }}</code></td>
                                        <td>{{ Str::limit($subcategory->description ?? '—', 50) }}</td>
                                        <td>{{ $subcategory->order }}</td>
                                        <td>
                                            <span class="badge {{ $subcategory->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $subcategory->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm gap-2">
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal{{ $subcategory->id }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form method="POST" action="{{ route('superadmin.grievance-management.subcategories.delete', $subcategory) }}" onsubmit="return confirm('Delete this subcategory?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No subcategories found. Create one to get started.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div class="tab-pane fade" id="assignments" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Assignments</h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                        + Add Assignment
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Category</th>
                                    <th>Subcategory</th>
                                    <th>Assigned Role</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                    @foreach($category->assignments as $assignment)
                                    <tr>
                                        <td>{{ $category->name }}</td>
                                        <td>{{ $assignment->subcategory ? $assignment->subcategory->name : 'All Subcategories' }}</td>
                                        <td><code>{{ $assignment->assigned_role }}</code></td>
                                        <td>{{ $assignment->priority }}</td>
                                        <td>
                                            <span class="badge {{ $assignment->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm gap-2">
                                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editAssignmentModal{{ $assignment->id }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <form method="POST" action="{{ route('superadmin.grievance-management.assignments.delete', $assignment) }}" onsubmit="return confirm('Delete this assignment?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No assignments found. Create one to get started.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transfer Rules Tab -->
        <div class="tab-pane fade" id="transfer-rules" role="tabpanel">
            <div class="card border-0 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Transfer Rules</h5>
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createTransferRuleModal">
                        + Add Transfer Rule
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>From Role</th>
                                    <th>To Role</th>
                                    <th>Category</th>
                                    <th>Subcategory</th>
                                    <th>Status</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transferRules as $rule)
                                <tr>
                                    <td><code>{{ $rule->from_role }}</code></td>
                                    <td><code>{{ $rule->to_role }}</code></td>
                                    <td>{{ $rule->category ? $rule->category->name : 'All Categories' }}</td>
                                    <td>{{ $rule->subcategory ? $rule->subcategory->name : ($rule->category ? 'All Subcategories' : '—') }}</td>
                                    <td>
                                        <span class="badge {{ $rule->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $rule->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm gap-2">
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editTransferRuleModal{{ $rule->id }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <form method="POST" action="{{ route('superadmin.grievance-management.transfer-rules.delete', $rule) }}" onsubmit="return confirm('Delete this transfer rule?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No transfer rules found. Create one to get started.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Escalation Settings Tab -->
        <div class="tab-pane fade" id="escalation-settings" role="tabpanel">
            <div class="card border-c-blue shadow-sm">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">Auto Escalation Settings</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('superadmin.grievance-management.escalation.update') }}" class="row g-3">
                        @csrf

                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="is_enabled" value="0">
                                <input class="form-check-input" type="checkbox" name="is_enabled" id="is_enabled" value="1" {{ ($escalationSetting->is_enabled ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_enabled">
                                    Enable auto escalation
                                </label>
                            </div>
                            <div class="small text-muted mt-1">
                                Applies to tickets with status: open / assigned / in_progress.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Escalate to Level 1 role after (hours)</label>
                            <input type="number" min="0" max="720" class="form-control" name="ix_head_after_hours" value="{{ old('ix_head_after_hours', $escalationSetting->ix_head_after_hours ?? 6) }}" required>
                            <div class="small text-muted">Example: 6 hours</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Escalate to Level 2 role after (hours)</label>
                            <input type="number" min="0" max="720" class="form-control" name="ceo_after_hours" value="{{ old('ceo_after_hours', $escalationSetting->ceo_after_hours ?? 24) }}" required>
                            <div class="small text-muted">Must be greater than or equal to Level 1 hours.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Level 1 Escalation Role</label>
                            <select class="form-select" name="level_1_role_slug" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" {{ old('level_1_role_slug', $escalationSetting->level_1_role_slug ?? 'ix_head') === $role->slug ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ $role->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted">Tickets move to this role after the Level 1 hours.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Level 2 Escalation Role</label>
                            <select class="form-select" name="level_2_role_slug" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" {{ old('level_2_role_slug', $escalationSetting->level_2_role_slug ?? 'ceo') === $role->slug ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ $role->slug }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted">Tickets move to this role after the Level 2 hours.</div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-success">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Workflow Setup Tab -->
        <div class="tab-pane fade" id="workflow-setup" role="tabpanel">
            <div class="card border-c-blue shadow-sm">
                <div class="card-header bg-primary d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">Quick Workflow Builder</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>How it works:</strong> Select a Category/Subcategory, choose the <strong>initial assigned role</strong>, then define the <strong>forwarding chain</strong>.
                        This will automatically create/update the Assignment and Transfer Rules for you.
                    </div>

                    <form method="POST" action="{{ route('superadmin.grievance-management.workflow.quick-setup') }}" class="row g-3">
                        @csrf

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Category</label>
                            <select class="form-select" name="category_id" required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subcategory (optional)</label>
                            <select class="form-select" name="subcategory_id">
                                <option value="">All Subcategories</option>
                                @foreach($categories as $category)
                                    @foreach($category->subcategories as $subcategory)
                                        <option value="{{ $subcategory->id }}" {{ (string) old('subcategory_id') === (string) $subcategory->id ? 'selected' : '' }}>
                                            {{ $category->name }} → {{ $subcategory->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                            <div class="small text-muted">If not selected, this workflow applies to the whole category.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Initial Assigned Role</label>
                            <select class="form-select" name="initial_role" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" {{ old('initial_role') === $role->slug ? 'selected' : '' }}>
                                        {{ $role->name }} ({{ $role->slug }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Replace existing rules?</label>
                            <div class="form-check mt-2">
                                <input type="hidden" name="replace_existing" value="0">
                                <input class="form-check-input" type="checkbox" name="replace_existing" id="replace_existing" value="1" {{ old('replace_existing', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="replace_existing">
                                    Yes (recommended)
                                </label>
                            </div>
                            <div class="small text-muted">If enabled, existing Assignment and Transfer Rules for this selection will be replaced.</div>
                        </div>

                        <div class="col-12">
                            <div class="fw-semibold mb-2">Forwarding Chain (who to forward next)</div>
                            <div class="row g-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <div class="col-md-4">
                                        <select class="form-select" name="forward_role_{{ $i }}">
                                            <option value="">(none)</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->slug }}" {{ old('forward_role_'.$i) === $role->slug ? 'selected' : '' }}>
                                                    {{ $role->name }} ({{ $role->slug }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endfor
                            </div>
                            <div class="small text-muted mt-2">
                                Example: Helpdesk → Hostmaster → Billing. (Leave empty if no further forwarding needed.)
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="submit" class="btn btn-success">Save Workflow</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@include('superadmin.grievance-management.modals.categories')
@include('superadmin.grievance-management.modals.subcategories')
@include('superadmin.grievance-management.modals.assignments')
@include('superadmin.grievance-management.modals.transfer-rules')
@endsection

