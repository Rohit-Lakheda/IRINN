@extends('admin.layout')

@section('title', 'Application Details')

@section('content')
<div class="container-fluid px-0 px-md-3 py-3">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h1 class="page-title mb-1">Application Details</h1>
            <p class="page-subtitle mb-0 text-muted">Same layout as the applicant &ldquo;View details&rdquo; page (read-only for admin).</p>
        </div>
        <a href="{{ route('admin.applications.show', $application->id) }}" class="btn btn-outline-primary irin-btn-rounded">
            Back to admin summary
        </a>
    </div>
</div>

@include('user.applications.partials.application-detail-body', [
    'application' => $application,
    'applicationDocumentRoute' => 'admin.applications.document',
    'hideUserOnlyActions' => true,
])
@endsection

@push('scripts')
@include('user.applications.partials.application-detail-scripts')
@endpush
