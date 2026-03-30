@extends('user.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Application Details')

@section('content')
@include('user.applications.partials.application-detail-body', [
    'application' => $application,
    'applicationDocumentRoute' => 'user.applications.document',
    'hideUserOnlyActions' => false,
])
@endsection

@push('scripts')
@include('user.applications.partials.application-detail-scripts')
@endpush
