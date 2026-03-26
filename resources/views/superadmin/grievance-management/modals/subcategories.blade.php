{{-- Create Subcategory Modal --}}
<div class="modal fade" id="createSubcategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-c-blue">
            <div class="modal-header theme-bg-blue text-white">
                <h5 class="modal-title text-white" style="color: #ffffff !important;">Create Subcategory</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('superadmin.grievance-management.subcategories.store') }}">
                @csrf
                <div class="modal-body theme-forms">
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (auto-generated if empty)</label>
                        <input type="text" class="form-control" name="slug" value="{{ old('slug') }}" placeholder="e.g., link_down">
                        <small class="text-muted">Leave empty to auto-generate from name</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Order</label>
                            <input type="number" class="form-control" name="order" value="{{ old('order', 0) }}" min="0">
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
                    <button type="submit" class="btn btn-primary">Create Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Subcategory Modals --}}
@foreach($categories as $category)
    @foreach($category->subcategories as $subcategory)
    <div class="modal fade" id="editSubcategoryModal{{ $subcategory->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-c-blue">
                <div class="modal-header theme-bg-blue text-white">
                    <h5 class="modal-title text-white" style="color: #ffffff !important;">Edit Subcategory: {{ $subcategory->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('superadmin.grievance-management.subcategories.update', $subcategory) }}">
                    @csrf
                    @method('POST')
                    <div class="modal-body theme-forms">
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $subcategory->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $subcategory->name) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" name="slug" value="{{ old('slug', $subcategory->slug) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3">{{ old('description', $subcategory->description) }}</textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Order</label>
                                <input type="number" class="form-control" name="order" value="{{ old('order', $subcategory->order) }}" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', $subcategory->is_active) ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('is_active', $subcategory->is_active) === false ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Subcategory</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
@endforeach

