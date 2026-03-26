{{-- Create Transfer Rule Modal --}}
<div class="modal fade" id="createTransferRuleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #ffffff !important;">Create Transfer Rule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.grievance-management.transfer-rules.store') }}">
                @csrf
                <div class="modal-body theme-forms">
                    <div class="mb-3">
                        <label class="form-label">From Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="from_role" id="transfer_from_role" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('from_role') == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="to_role" required>
                            <option value="">Select Role</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('to_role') == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category (optional - leave empty for all categories)</label>
                        <select class="form-select" name="category_id" id="transfer_category_id">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subcategory (optional - leave empty for all subcategories)</label>
                        <select class="form-select" name="subcategory_id" id="transfer_subcategory_id">
                            <option value="">All Subcategories</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="is_active">
                            <option value="1" {{ old('is_active', true) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') === false ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Transfer Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Transfer Rule Modals --}}
@foreach($transferRules as $rule)
<div class="modal fade" id="editTransferRuleModal{{ $rule->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #ffffff !important;">Edit Transfer Rule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.grievance-management.transfer-rules.update', $rule) }}">
                @csrf
                @method('POST')
                <div class="modal-body theme-forms">
                    <div class="mb-3">
                        <label class="form-label">From Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="from_role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('from_role', $rule->from_role) == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">To Role <span class="text-danger">*</span></label>
                        <select class="form-select" name="to_role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->slug }}" {{ old('to_role', $rule->to_role) == $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category (optional)</label>
                        <select class="form-select" name="category_id" id="edit_transfer_category_id{{ $rule->id }}">
                            <option value="">All Categories</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $rule->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subcategory (optional)</label>
                        <select class="form-select" name="subcategory_id" id="edit_transfer_subcategory_id{{ $rule->id }}">
                            <option value="">All Subcategories</option>
                            @if($rule->category)
                                @foreach($rule->category->subcategories as $subcat)
                                    <option value="{{ $subcat->id }}" {{ old('subcategory_id', $rule->subcategory_id) == $subcat->id ? 'selected' : '' }}>{{ $subcat->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="is_active">
                            <option value="1" {{ old('is_active', $rule->is_active) ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active', $rule->is_active) === false ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Transfer Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle subcategory dropdown update when category changes in create modal
    const transferCategorySelect = document.getElementById('transfer_category_id');
    const transferSubcategorySelect = document.getElementById('transfer_subcategory_id');
    
    if (transferCategorySelect) {
        transferCategorySelect.addEventListener('change', function() {
            const categoryId = this.value;
            transferSubcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
            
            if (categoryId) {
                @foreach($categories as $cat)
                    if (categoryId == '{{ $cat->id }}') {
                        @foreach($cat->subcategories as $subcat)
                            const option{{ $subcat->id }} = document.createElement('option');
                            option{{ $subcat->id }}.value = '{{ $subcat->id }}';
                            option{{ $subcat->id }}.textContent = '{{ $subcat->name }}';
                            transferSubcategorySelect.appendChild(option{{ $subcat->id }});
                        @endforeach
                    }
                @endforeach
            }
        });
    }
});
</script>
@endpush

