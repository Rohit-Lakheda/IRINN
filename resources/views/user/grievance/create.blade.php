@extends('user.layout')

@section('title', 'Submit Grievance')

@section('content')
<div class="row mt-md-4">
    <div class="col-md-10 col-lg-8 mx-auto">
        <div class="card border-c-blue shadow-sm">
            <div class="card-header theme-bg-blue">
                <h4 class="mb-0">Submit Grievance / Feedback</h4>
            </div>
            <div class="card-body theme-forms">
                <form method="POST" action="{{ route('user.grievance.store') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6" id="sub_category_container" style="display: none;">
                            <label for="sub_category" class="form-label">Sub Category <span class="text-danger">*</span></label>
                            <select name="sub_category" id="sub_category" class="form-select @error('sub_category') is-invalid @enderror">
                                <option value="">Select Sub Category</option>
                            </select>
                            @error('sub_category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror" 
                                   value="{{ old('subject') }}" placeholder="Brief subject (optional)">
                            @error('subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label for="priority" class="form-label">Priority</label>
                            <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                            @error('priority')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" rows="6" 
                                  class="form-control @error('description') is-invalid @enderror" 
                                  required placeholder="Please describe your grievance, feedback, or enquiry in detail...">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Minimum 10 characters required.</small>
                    </div>

                    <div class="mb-3">
                        <label for="attachments" class="form-label">Attachments</label>
                        <input type="file" name="attachments[]" id="attachments" 
                               class="form-control @error('attachments.*') is-invalid @enderror" 
                               multiple accept="image/*,.pdf,.doc,.docx">
                        @error('attachments.*')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">You can attach images, PDFs, or documents. Maximum file size: 10MB per file.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                                <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576 6.636 10.07Zm-1.138-1.138L13.713 1.5 5.498 8.932Z"/>
                            </svg>
                            Submit
                        </button>
                        <a href="{{ route('user.grievance.index') }}" class="btn btn-danger">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category');
    const subCategoryContainer = document.getElementById('sub_category_container');
    const subCategorySelect = document.getElementById('sub_category');

    // Sub-categories mapping (dynamically loaded from database)
    const subCategories = @json($categoriesWithSubcategories ?? []);

    // Old value for restoration
    const oldSubCategory = '{{ old('sub_category') }}';

    categorySelect.addEventListener('change', function() {
        const selectedCategory = this.value;
        subCategorySelect.innerHTML = '<option value="">Select Sub Category</option>';

        if (selectedCategory && subCategories[selectedCategory] && Object.keys(subCategories[selectedCategory]).length > 0) {
            subCategoryContainer.style.display = 'block';
            subCategorySelect.required = true;

            for (const [key, label] of Object.entries(subCategories[selectedCategory])) {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = label;
                if (oldSubCategory === key) {
                    option.selected = true;
                }
                subCategorySelect.appendChild(option);
            }
        } else {
            subCategoryContainer.style.display = 'none';
            subCategorySelect.required = false;
        }
    });

    // Trigger on page load if category is already selected
    if (categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush
@endsection

