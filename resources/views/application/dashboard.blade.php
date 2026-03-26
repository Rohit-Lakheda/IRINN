@extends('application.layout')

@section('title', 'Application Dashboard')

@section('content')
<div class="row">
    <div class="col-12">
        <h1 class="mb-4">Application Dashboard</h1>
        <div class="card">
            <div class="card-header text-white" style="background-color: #2ecc71;">
                <h5 class="mb-0">Welcome to Application Portal</h5>
            </div>
            <div class="card-body">
                <p>This is the Application dashboard. Manage your applications here.</p>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card border-success mb-3">
                            <div class="card-header bg-success text-white">My Applications</div>
                            <div class="card-body">
                                <h5 class="card-title">View Applications</h5>
                                <p class="card-text">View and manage all your submitted applications.</p>
                                <a href="#" class="btn btn-success">View Applications</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">New Application</div>
                            <div class="card-body">
                                <h5 class="card-title">Submit Application</h5>
                                <p class="card-text">Create and submit a new application.</p>
                                <a href="#" class="btn btn-primary">New Application</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="card border-info mb-3">
                            <div class="card-header bg-info text-white">Status</div>
                            <div class="card-body">
                                <h5 class="card-title">Application Status</h5>
                                <p class="card-text">Check the status of your applications.</p>
                                <a href="#" class="btn btn-info">Check Status</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning mb-3">
                            <div class="card-header bg-warning text-white">Profile</div>
                            <div class="card-body">
                                <h5 class="card-title">My Profile</h5>
                                <p class="card-text">Update your profile information.</p>
                                <a href="#" class="btn btn-warning">Edit Profile</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

