@extends('superadmin.layout')

@section('title', 'Edit Admin Type')

@section('content')
<div class="container-fluid px-2 py-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
        <div>
            <h2 class="mb-1 border-0">Edit Admin Type</h2>
            <p class="text-muted mb-1">{{ $admin->name }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('superadmin.admins') }}" class="btn btn-primary">
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
                <div class="card-header bg-warning text-dark" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Admin Roles / Types</h5>
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

                    <form method="POST" action="{{ route('superadmin.admins.update-type', $admin->id) }}" class="theme-forms">
                        @csrf
                        
                        <div class="mb-4">
                            <label class="form-label">Assign Roles</label>
                            <div class="row g-3">
                                @foreach($roles as $role)
                                <div class="col-md-6">
                                    <div class="form-check p-3 border rounded d-flex align-items-start">
                                        <input class="form-check-input mt-1 me-2" 
                                               type="checkbox" 
                                               name="roles[]" 
                                               value="{{ $role->id }}" 
                                               id="role_{{ $role->id }}"
                                               {{ $admin->roles->contains($role->id) ? 'checked' : '' }}>
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
                            <small class="form-text text-muted mt-2">You can select multiple roles or none. Admin can have any combination of roles (Processor, Finance, Technical).</small>
                        </div>

                        <div class="mb-3 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                                </svg>
                                Update Roles
                            </button>
                            <a href="{{ route('superadmin.admins') }}" class="btn btn-danger">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

