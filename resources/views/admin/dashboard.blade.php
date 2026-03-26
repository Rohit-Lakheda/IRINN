@extends('admin.layout')

@section('title', 'Admin Dashboard')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="flex-wrap">
                    <h2 class="mb-1 border-0">Admin Dashboard</h2>
                    <p class="mb-2">Welcome back, <strong>{{ $admin->name ?? 'Admin' }}</strong>!</p>
                </div>
            </div>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Global Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-search-card shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-3">
                    <form id="globalSearchForm" class="d-flex gap-2 theme-forms">
                        <input type="text" 
                               id="globalSearchInput" 
                               name="search" 
                               class="form-control" 
                               placeholder="Search applications, registrations, grievances..." 
                               value="{{ request('search') }}"
                               autocomplete="off">
                        <button type="submit" class="btn dashboard-search-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                            Search
                        </button>
                    </form>
                    <div id="searchResults" class="mt-3" style="display: none;">
                        <div class="list-group">
                            <div id="searchResultsList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Registration & Application Overview -->
        <div class="col-lg-6 col-md-6 col-12">
            <div class="dashboard-overview-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                        </svg>
                        Registration & Application Overview
                    </h5>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-stats-grid">
                        <a href="{{ route('admin.users') }}" class="dashboard-stat-card stat-primary">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Registrations</div>
                                <div class="stat-value">{{ $totalUsers }}</div>
                            </div>
                        </a>
                        
                        <a href="{{ route('admin.applications', ['role' => $selectedRole]) }}" class="dashboard-stat-card stat-info">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Applications</div>
                                <div class="stat-value">{{ $totalApplications }}</div>
                            </div>
                        </a>
                        
                        <a href="{{ route('admin.applications', ['role' => $selectedRole, 'approved' => 1]) }}" class="dashboard-stat-card stat-success">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Approved Applications</div>
                                <div class="stat-value">{{ $approvedApplications }}</div>
                            </div>
                        </a>
                        
                        <a href="{{ route('admin.applications', ['role' => $selectedRole, 'role_filter' => $roleToUse ?? $selectedRole]) }}" class="dashboard-stat-card stat-warning">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Pending Applications</div>
                                <div class="stat-value">{{ $pendingApplications }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grievance Overview -->
        <div class="col-lg-6 col-md-6 col-12">
            <div class="dashboard-overview-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16Zm.93-9.412-1 4.706a.55.55 0 0 1-1.09-.082L6.5 6.5a1 1 0 1 1 1.93.088ZM8 5a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 8 5Z"/>
                        </svg>
                        Grievance Overview
                    </h5>
                </div>
                <div class="dashboard-card-body">
                    <div class="dashboard-stats-grid">
                        <a href="{{ route('admin.grievance.index') }}" class="dashboard-stat-card stat-primary">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Total Grievances</div>
                                <div class="stat-value">{{ $totalGrievances }}</div>
                            </div>
                        </a>
                        
                        <a href="{{ route('admin.grievance.index', ['status' => 'open']) }}" class="dashboard-stat-card stat-warning">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Open Grievances</div>
                                <div class="stat-value">{{ $openGrievances }}</div>
                            </div>
                        </a>
                        
                        <a href="{{ route('admin.grievance.index', ['status' => 'closed']) }}" class="dashboard-stat-card stat-success">
                            <div class="stat-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 2.04 4.907a.75.75 0 0 0-1.06 1.061l5.523 5.524a.75.75 0 0 0 1.07-.01l5.99-5.99a.75.75 0 0 0-.022-1.08z"/>
                                </svg>
                            </div>
                            <div class="stat-content">
                                <div class="stat-label">Closed Grievances</div>
                                <div class="stat-value">{{ $closedGrievances }}</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Registrations & Recent Applications -->
    <div class="row g-3">
        <div class="col-md-12 col-lg-6">
            <div class="dashboard-recent-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                        </svg>
                        Recent Registrations
                    </h5>
                    <a href="{{ route('admin.users') }}" class="dashboard-view-all">View All →</a>
                </div>
                <div class="dashboard-card-body">
                    @if($recentUsers->count() > 0)
                        <div class="dashboard-recent-list">
                            @foreach($recentUsers->take(5) as $user)
                            <a href="{{ route('admin.users.show', $user->id) }}" class="dashboard-recent-item">
                                <div class="recent-item-avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                        <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1Z"/>
                                    </svg>
                                </div>
                                <div class="recent-item-content">
                                    <div class="recent-item-name">{{ $user->fullname }}</div>
                                    <div class="recent-item-meta">{{ $user->email }}</div>
                                </div>
                                <div class="recent-item-right">
                                    @if($user->status === 'approved')
                                        <span class="recent-item-badge badge-success">Registered</span>
                                    @elseif($user->status === 'pending')
                                        <span class="recent-item-badge badge-warning">Pending</span>
                                    @else
                                        <span class="recent-item-badge badge-secondary">Rejected</span>
                                    @endif
                                    <div class="recent-item-date">{{ $user->created_at->format('M d') }}</div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @else
                        <div class="dashboard-empty-state">
                            <p class="text-muted mb-0">No registrations yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-12 col-lg-6">
            <div class="dashboard-recent-card">
                <div class="dashboard-card-header">
                    <h5 class="dashboard-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="me-2">
                            <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                        </svg>
                        Recent Applications
                    </h5>
                    <a href="{{ route('admin.applications') }}" class="dashboard-view-all">View All →</a>
                </div>
                <div class="dashboard-card-body">
                    @if($recentMembers->count() > 0)
                        <div class="dashboard-recent-list">
                            @foreach($recentMembers->take(5) as $application)
                            <a href="{{ route('admin.applications.show', $application->id) }}" class="dashboard-recent-item">
                                <div class="recent-item-avatar">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                                    </svg>
                                </div>
                                <div class="recent-item-content">
                                    <div class="recent-item-name">{{ $application->application_id }}</div>
                                    <div class="recent-item-meta">{{ $application->user->fullname ?? 'N/A' }}</div>
                                </div>
                                <div class="recent-item-right">
                                    @if($application->is_active)
                                        <span class="recent-item-badge badge-success">LIVE</span>
                                    @else
                                        <span class="recent-item-badge badge-danger">NOT LIVE</span>
                                    @endif
                                    <div class="recent-item-date">{{ $application->updated_at->format('M d') }}</div>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @else
                        <div class="dashboard-empty-state">
                            <p class="text-muted mb-0">No applications found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    /* Dashboard: subtle, elegant theme (navy/blue, no strong gradients) */
    .dashboard-search-card {
        background: #ffffff;
        border: 1px solid rgba(44, 62, 80, 0.08);
    }
    .dashboard-search-card .form-control {
        border-color: rgba(44, 62, 80, 0.15);
    }
    .dashboard-search-card .form-control:focus {
        border-color: var(--theme-blue, #2B2F6C);
        box-shadow: 0 0 0 0.18rem rgba(43, 47, 108, 0.18);
    }
    .dashboard-search-btn {
        background: var(--theme-blue, #2B2F6C);
        color: #ffffff !important;
        border: none;
        font-weight: 600;
        padding: 0.5rem 1.25rem;
        border-radius: 999px;
        transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    }
    .dashboard-search-btn:hover {
        background: #4348a3;
        color: #ffffff !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }

    /* Dashboard Overview Cards */
    .dashboard-overview-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: box-shadow 0.25s ease;
        border: 1px solid rgba(44, 62, 80, 0.06);
    }
    
    .dashboard-overview-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .dashboard-overview-card .dashboard-card-header {
        background: linear-gradient(90deg, var(--theme-blue, #2B2F6C), #4b4fd1);
        padding: 1rem 1.25rem;
        border-bottom: none;
    }
    
    .dashboard-card-title {
        color: #ffffff;
        font-size: 0.95rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
    }
    
    .dashboard-card-body {
        padding: 1rem;
    }
    
    .dashboard-stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .dashboard-stat-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #fafbfc;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.25s ease;
        border: 1px solid rgba(44, 62, 80, 0.08);
        position: relative;
    }
    
    .dashboard-stat-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 10px rgba(44, 62, 80, 0.08);
        border-color: rgba(44, 62, 80, 0.12);
        background: #ffffff;
    }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        transition: transform 0.25s ease;
    }
    
    .stat-primary .stat-icon {
        background: rgba(43, 47, 108, 0.08);
        color: var(--theme-blue, #2B2F6C);
    }
    
    .stat-info .stat-icon {
        background: rgba(13, 202, 240, 0.10);
        color: #0d6efd;
    }
    
    .stat-success .stat-icon {
        background: rgba(25, 135, 84, 0.10);
        color: #198754;
    }
    
    .stat-warning .stat-icon {
        background: rgba(255, 193, 7, 0.14);
        color: #b8860b;
    }
    
    .dashboard-stat-card:hover .stat-icon {
        transform: scale(1.05);
    }
    
    .stat-content {
        flex: 1;
        min-width: 0;
    }
    
    .stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
        margin-bottom: 0.25rem;
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.2;
    }
    
    
    /* Recent Cards */
    .dashboard-recent-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: box-shadow 0.25s ease;
        border: 1px solid rgba(44, 62, 80, 0.06);
    }
    
    .dashboard-recent-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .dashboard-recent-card .dashboard-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        background: linear-gradient(90deg, var(--theme-blue, #2B2F6C), #4b4fd1);
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    /* View All: high-contrast, pill button on header */
    .dashboard-view-all {
        font-size: 0.8125rem;
        color: #ffffff !important;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.25s ease;
        padding: 0.35rem 0.9rem;
        border-radius: 999px;
        background: transparent;
        border: 1px solid rgba(255, 255, 255, 0.7);
        white-space: nowrap;
    }
    
    .dashboard-view-all:hover {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.9);
        transform: translateX(2px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.16);
    }
    
    .dashboard-recent-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .dashboard-recent-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.25s ease;
        background: #f8fafb;
        border: 1px solid transparent;
    }
    
    .dashboard-recent-item:hover {
        background: #ffffff;
        border-color: rgba(44, 62, 80, 0.1);
        transform: translateX(3px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }
    
    .recent-item-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(44, 62, 80, 0.08);
        color: var(--theme-navy, #2C3E50);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .recent-item-content {
        flex: 1;
        min-width: 0;
    }
    
    .recent-item-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.125rem;
    }
    
    .recent-item-meta {
        font-size: 0.75rem;
        color: #6b7280;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .recent-item-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.25rem;
    }
    
    .recent-item-badge {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 600;
    }
    
    .badge-success {
        background: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    
    .badge-warning {
        background: rgba(255, 193, 7, 0.1);
        color: #f59e0b;
    }
    
    .badge-danger {
        background: rgba(220, 38, 38, 0.1);
        color: #dc2626;
    }
    
    .badge-secondary {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }
    
    .recent-item-date {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    
    .dashboard-empty-state {
        padding: 2rem;
        text-align: center;
    }
    
    @media (max-width: 768px) {
        .dashboard-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Global Search Functionality
    const searchInput = document.getElementById('globalSearchInput');
    const searchForm = document.getElementById('globalSearchForm');
    const searchResults = document.getElementById('searchResults');
    const searchResultsList = document.getElementById('searchResultsList');
    let searchTimeout;

    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });

    function performSearch(query) {
        // Build search links based on query
        const results = [];
        
        // Applications search
        results.push({
            title: `Search Applications: "${query}"`,
            url: `{{ route('admin.applications') }}?search=${encodeURIComponent(query)}`,
            icon: '📄'
        });
        
        // Users/Registrations search
        results.push({
            title: `Search Registrations: "${query}"`,
            url: `{{ route('admin.users') }}?search=${encodeURIComponent(query)}`,
            icon: '👤'
        });
        
        // Grievances search
        results.push({
            title: `Search Grievances: "${query}"`,
            url: `{{ route('admin.grievance.index') }}?search=${encodeURIComponent(query)}`,
            icon: '🎫'
        });

        displayResults(results);
    }

    function displayResults(results) {
        if (results.length === 0) {
            searchResults.style.display = 'none';
            return;
        }

        searchResultsList.innerHTML = results.map(result => `
            <a href="${result.url}" class="list-group-item list-group-item-action">
                <div class="d-flex align-items-center">
                    <span class="me-2">${result.icon}</span>
                    <span>${result.title}</span>
                </div>
            </a>
        `).join('');

        searchResults.style.display = 'block';
    }

    // Handle form submission - redirect to applications by default
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = `{{ route('admin.applications') }}?search=${encodeURIComponent(query)}`;
        }
    });

    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.style.display = 'none';
        }
    });
</script>
@endpush
@endsection
