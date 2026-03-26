@extends('superadmin.layout')

@section('title', 'Add New Admin')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-1 border-0">Add New Admin</h2>
            <p class="text-muted mb-1">Create a new admin account</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.admins') }}" class="btn btn-primary px-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Back to Admins
            </a>
        </div>
    </div>
    <div class="accent-line mb-4"></div>

    <div class="row">
        <div class="col-md-12 col-lg-9">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Admin Information</h5>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger border-0" style="border-radius: 12px;">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('superadmin.admins.store') }}" class="theme-forms">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control @error('employee_id') is-invalid @enderror" 
                                   id="employee_id" 
                                   name="employee_id" 
                                   value="{{ old('employee_id') }}" 
                                   required
                                   placeholder="Enter Employee ID">
                            <small class="form-text text-muted">This Employee ID will be used as the admin identifier</small>
                            <div id="employeeIdError" class="text-danger small mt-1" style="display: none;"></div>
                            @error('employee_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   id="password" 
                                   name="password" 
                                   required>
                            <small class="form-text text-muted">Minimum 8 characters</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password_confirmation" 
                                   name="password_confirmation" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assign Roles</label>
                            <div class="row g-3">
                                @foreach($roles as $role)
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded d-flex align-items-start">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="roles[]" 
                                               value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}">
                                        <div class="flex-grow-1">
                                            <label class="form-check-label d-block" for="role_{{ $role->id }}">
                                                {{ $role->name }}
                                            </label>
                                            @if($role->description)
                                                <small class="d-block text-muted mt-1">{{ $role->description }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <small class="form-text text-muted mt-2">You can select multiple roles or none. Admin can have any combination of roles.</small>
                        </div>

                        <div class="mb-3 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary px-4 py-2" id="submitBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M12.5 16a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm.5-5v1h1a.5.5 0 0 1 0 1h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0Zm-2-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    <path d="M2 13c0 1 1 1 1 1h5.256A4.493 4.493 0 0 1 8 12.5a4.49 4.49 0 0 1 1.544-3.393C9.077 9.038 8.564 9 8 9c-5 0-6 3-6 4Z"/>
                                </svg>
                                Create Admin
                            </button>
                            <a href="{{ route('superadmin.admins') }}" class="btn btn-danger px-4 py-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const employeeIdInput = document.getElementById('employee_id');
    const employeeIdError = document.getElementById('employeeIdError');
    const submitBtn = document.getElementById('submitBtn');
    const form = employeeIdInput.closest('form');
    let checkTimeout;

    function checkEmployeeId(employeeId) {
        if (!employeeId || employeeId.trim() === '') {
            employeeIdError.style.display = 'none';
            employeeIdInput.classList.remove('is-invalid');
            submitBtn.disabled = false;
            return;
        }

        fetch('{{ route("superadmin.admins.check-employee-id") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ employee_id: employeeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                employeeIdError.textContent = 'This Employee ID is already registered. Please use a different Employee ID.';
                employeeIdError.style.display = 'block';
                employeeIdInput.classList.add('is-invalid');
                submitBtn.disabled = true;
            } else {
                employeeIdError.style.display = 'none';
                employeeIdInput.classList.remove('is-invalid');
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error checking employee ID:', error);
        });
    }

    employeeIdInput.addEventListener('input', function() {
        clearTimeout(checkTimeout);
        const employeeId = this.value.trim();
        
        checkTimeout = setTimeout(function() {
            checkEmployeeId(employeeId);
        }, 500);
    });

    form.addEventListener('submit', function(e) {
        const employeeId = employeeIdInput.value.trim();
        if (!employeeId) {
            e.preventDefault();
            alert('Please enter an Employee ID.');
            employeeIdInput.focus();
            return false;
        }

        // Final check before submit - synchronous check
        e.preventDefault();
        
        fetch('{{ route("superadmin.admins.check-employee-id") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ employee_id: employeeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.exists) {
                alert('This Employee ID is already registered. Please use a different Employee ID.');
                employeeIdError.textContent = 'This Employee ID is already registered. Please use a different Employee ID.';
                employeeIdError.style.display = 'block';
                employeeIdInput.classList.add('is-invalid');
                employeeIdInput.focus();
            } else {
                // If employee ID is available, submit the form
                form.submit();
            }
        })
        .catch(error => {
            console.error('Error checking employee ID:', error);
            alert('Error checking Employee ID. Please try again.');
        });
    });
});
</script>
@endsection
