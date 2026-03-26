{{-- Create Assignment Modal --}}
<div class="modal fade" id="createAssignmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #ffffff !important;">Create Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.grievance-management.assignments.store') }}">
                @csrf
                <div class="modal-body theme-forms">
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" id="assignment_category_id" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subcategory (optional - leave empty for all subcategories)</label>
                        <select class="form-select" name="subcategory_id" id="assignment_subcategory_id">
                            <option value="">All Subcategories</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assigned Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="assigned_role" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('assigned_role') == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <input type="number" class="form-control" name="priority" value="{{ old('priority', 0) }}" min="0">
                            <small class="text-muted">Lower number = higher priority</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="is_active">
                                <option value="1" {{ old('is_active', true) ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active') === false ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Assignment Modals --}}
@foreach($categories as $category)
    @foreach($category->assignments as $assignment)
    <div class="modal fade" id="editAssignmentModal{{ $assignment->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-c-blue">
                <div class="modal-header theme-bg-blue text-white">
                    <h5 class="modal-title text-white" style="color: #ffffff !important;">Edit Assignment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.grievance-management.assignments.update', $assignment) }}">
                    @csrf
                    @method('POST')
                    <div class="modal-body theme-forms">
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="edit_assignment_category_id{{ $assignment->id }}" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $assignment->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subcategory (optional)</label>
                            <select class="form-select" name="subcategory_id" id="edit_assignment_subcategory_id{{ $assignment->id }}">
                                <option value="">All Subcategories</option>
                                @foreach($category->subcategories as $subcat)
                                    <option value="{{ $subcat->id }}" {{ old('subcategory_id', $assignment->subcategory_id) == $subcat->id ? 'selected' : '' }}>{{ $subcat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Assigned Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="assigned_role" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->slug }}" {{ old('assigned_role', $assignment->assigned_role) == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Priority</label>
                                <input type="number" class="form-control" name="priority" value="{{ old('priority', $assignment->priority) }}" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', $assignment->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $assignment->is_active) === false ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
@endforeach

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle subcategory dropdown update when category changes in create modal
    const categorySelect = document.getElementById('assignment_category_id');
    const subcategorySelect = document.getElementById('assignment_subcategory_id');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
            
            if (categoryId) {
                @foreach($categories as $cat)
                    if (categoryId == '{{ $cat->id }}') {
                        @foreach($cat->subcategories as $subcat)
                            const option{{ $subcat->id }} = document.createElement('option');
                            option{{ $subcat->id }}.value = '{{ $subcat->id }}';
                            option{{ $subcat->id }}.textContent = '{{ $subcat->name }}';
                            subcategorySelect.appendChild(option{{ $subcat->id }});
                        @endforeach
                    }
                @endforeach
            }
        });
    }
});
</script>
@endpush

