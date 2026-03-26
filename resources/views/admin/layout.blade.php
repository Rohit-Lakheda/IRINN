<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard')</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    <!-- Admin Sidebar Theme -->
    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">

    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="admin-panel admin-sidebar-layout">
    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    @php
        // Get admin from shared variable or fetch it
        $adminForRoles = isset($currentAdmin) ? $currentAdmin : \App\Models\Admin::with('roles')->find(session('admin_id'));

        // Get selected role from session (should be set by middleware)
        $selectedRole = session('admin_selected_role', null);

        // Get all roles assigned to admin
        $allRoles = $adminForRoles ? $adminForRoles->roles : collect();

        // Filter only active roles, but if none are active, show all roles
        $activeRoles = $allRoles->where('is_active', true);
        if ($activeRoles->count() === 0 && $allRoles->count() > 0) {
            $activeRoles = $allRoles;
        }

        // Validate selected role - if it doesn't exist in active roles, reset it
        if ($selectedRole) {
            $roleExists = $activeRoles->contains(function($role) use ($selectedRole) {
                return $role->slug === $selectedRole;
            });
            if (! $roleExists) {
                $selectedRole = null;
                session()->forget('admin_selected_role');
            }
        }

        // Auto-select role if not selected (fallback)
        if ($adminForRoles && $activeRoles->count() > 0 && ! $selectedRole) {
            if ($activeRoles->count() === 1) {
                // Single role - auto-select it
                $selectedRole = $activeRoles->first()->slug;
            } else {
                // Multiple roles - select based on priority
                $priorityOrder = ['processor', 'finance', 'technical'];
                foreach ($priorityOrder as $priorityRole) {
                    $role = $activeRoles->firstWhere('slug', $priorityRole);
                    if ($role) {
                        $selectedRole = $priorityRole;
                        break;
                    }
                }
                // If no priority role found, select first one
                if (! $selectedRole) {
                    $selectedRole = $activeRoles->first()->slug;
                }
            }
            // Save to session
            if ($selectedRole) {
                session(['admin_selected_role' => $selectedRole]);
            }
        }
    @endphp

    <!-- Left Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="sidebar-brand">
                @include('partials.logo')
            </div>
            <button type="button" class="sidebar-close d-lg-none" id="sidebarClose" aria-label="Close menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.146 2.854a.5.5 0 1 1 .708-.708L8 7.293l5.146-5.147a.5.5 0 0 1 .708.708L8.707 8l5.147 5.146a.5.5 0 0 1-.708.708L8 8.707l-5.146 5.147a.5.5 0 0 1-.708-.708L7.293 8 2.146 2.854Z"/>
                </svg>
            </button>
        </div>

        @if($adminForRoles && $activeRoles->count() > 0)
        <div class="px-3 pt-2 pb-2">
            <div class="role-selector-wrapper">
                <button class="role-selector-btn"
                        type="button"
                        id="roleDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false">
                    <div class="role-selector-content">
                        <div class="role-selector-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/>
                            </svg>
                        </div>
                        <div class="role-selector-text">
                            <div class="role-selector-label">Current Role</div>
                            <div class="role-selector-value">{{ ucfirst($selectedRole ?? $activeRoles->first()->slug) }}</div>
                        </div>
                        @if($activeRoles->count() > 1)
                            <div class="role-selector-badge">{{ $activeRoles->count() }}</div>
                        @endif
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="role-selector-arrow">
                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </div>
                </button>
                <ul class="dropdown-menu role-dropdown-menu" aria-labelledby="roleDropdown">
                    @foreach($activeRoles as $role)
                        @php
                            $currentPath = request()->path();
                            $queryParams = request()->query();
                            $queryParams['role'] = $role->slug;
                            $url = url($currentPath) . '?' . http_build_query($queryParams);
                            $isSelected = ($selectedRole === $role->slug);
                        @endphp
                        <li>
                            <a class="role-dropdown-item {{ $isSelected ? 'active' : '' }}" href="{{ $url }}">
                                <span class="role-item-icon">
                                    @if($isSelected)
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                        </svg>
                                    @endif
                                </span>
                                <span>{{ $role->name }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @php
            $adminId = session('admin_id');
            $roleSlug = $selectedRole ?? null;

            if ($adminId && $roleSlug) {
                $unreadApplicationCount = \App\Models\Application::where('application_type', 'IX')
                    ->whereNotNull('submitted_at')
                    ->whereDoesntHave('readByAdmins', function ($query) use ($adminId, $roleSlug) {
                        $query->where('admins.id', $adminId)
                            ->where(function ($q) use ($roleSlug) {
                                $q->where('admin_application_reads.role', $roleSlug)
                                    ->orWhereNull('admin_application_reads.role');
                            });
                    })
                    ->count();
            } else {
                $unreadApplicationCount = 0;
            }
        @endphp

        <nav class="sidebar-nav">
            <ul class="sidebar-nav-list">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ route('admin.dashboard') }}">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l1.146 1.147a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Dashboard</span>
                    </a>
                </li>

                <li class="sidebar-nav-item sidebar-nav-group">
                    <button class="sidebar-nav-link sidebar-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#detailsMenu" aria-expanded="false">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M1.5 0A1.5 1.5 0 0 0 0 1.5v13A1.5 1.5 0 0 0 1.5 16h13a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 14.5 0h-13zm1 2h5v4h-5V2zm6 0h5v4h-5V2zm-6 6h5v4h-5V8zm6 0h5v4h-5V8z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Details</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="sidebar-nav-chevron">
                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                    <ul class="sidebar-nav-submenu collapse" id="detailsMenu">
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.users') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Z"/>
                                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Registrations</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.applications', ['role' => $selectedRole ?? null]) }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v11.5L12.5 12 10 13.5 7.5 12 5 13.5 2.5 12 1 13.5V2a1 1 0 0 1 1-1h12Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">
                                    Applications
                                    @if($unreadApplicationCount > 0)
                                        <span class="sidebar-nav-badge ms-2">{{ $unreadApplicationCount }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.invoices.index') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M14 1a1 1 0 0 1 1 1v11.5L12.5 12 10 13.5 7.5 12 5 13.5 2.5 12 1 13.5V2a1 1 0 0 1 1-1h12Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Invoices</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-nav-item sidebar-nav-group">
                    <button class="sidebar-nav-link sidebar-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#requestsMenu" aria-expanded="false">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16Zm.93-9.412-1 4.706a.55.55 0 0 1-1.09-.082L6.5 6.5a1 1 0 1 1 1.93.088ZM8 5a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 8 5Z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Requests & Grievance</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="sidebar-nav-chevron">
                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                    <ul class="sidebar-nav-submenu collapse" id="requestsMenu">
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.grievance.index') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16Zm.93-9.412-1 4.706a.55.55 0 0 1-1.09-.082L6.5 6.5a1 1 0 1 1 1.93.088ZM8 5a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 8 5Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Grievance</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.plan-change.index') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.418A6 6 0 1 1 8 2v1Z"/>
                                        <path d="M8 0a.5.5 0 0 1 .5.5v3a.5.5 0 1 1-1 0v-3A.5.5 0 0 1 8 0Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Plan Changes</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.profile-update-requests') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                        <path d="M14 14s-1 0-1-1-1-4-5-4-5 3-5 4-1 1-1 1h12Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Profile Updates</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.gst-update-requests') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2Zm15 2h-4v3h4V4Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">GST Updates</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.reactivation-requests.index') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.418A6 6 0 1 1 8 2v1Z"/>
                                        <path d="M8 0a.5.5 0 0 1 .5.5v3a.5.5 0 1 1-1 0v-3A.5.5 0 0 1 8 0Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Reactivation</span>
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="sidebar-nav-item sidebar-nav-group">
                    <button class="sidebar-nav-link sidebar-nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#communicationMenu" aria-expanded="false">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Z"/>
                                <path d="M.05 4.555 7.482 8.854a.5.5 0 0 0 .536 0L15.95 4.555 8.5.25a.5.5 0 0 0-.5 0L.05 4.555Z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Communication</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="sidebar-nav-chevron">
                            <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                    <ul class="sidebar-nav-submenu collapse" id="communicationMenu">
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink" href="{{ route('admin.bulk-notification') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Z"/>
                                        <path d="M.05 4.555 7.482 8.854a.5.5 0 0 0 .536 0L15.95 4.555 8.5.25a.5.5 0 0 0-.5 0L.05 4.555Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">Bulk Notification</span>
                            </a>
                        </li>
                        <li class="sidebar-nav-item">
                            <a class="sidebar-nav-link sidebar-nav-sublink position-relative" href="{{ route('admin.messages') }}">
                                <span class="sidebar-nav-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2Z"/>
                                        <path d="M8 1.918 7.203 2.08A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92Z"/>
                                    </svg>
                                </span>
                                <span class="sidebar-nav-text">
                                    Notifications
                                    @php
                                        $adminMessageIds = $adminId ? \App\Models\AdminAction::where('admin_id', $adminId)
                                            ->where('action_type', 'sent_message')
                                            ->where('actionable_type', \App\Models\Message::class)
                                            ->pluck('actionable_id')
                                            ->toArray() : [];
                                        $unreadCount = count($adminMessageIds) > 0
                                            ? \App\Models\Message::whereIn('id', $adminMessageIds)
                                                ->where('admin_read', false)
                                                ->count()
                                            : 0;
                                    @endphp
                                    @if($unreadCount > 0)
                                        <span class="sidebar-nav-badge ms-2" id="messageBadge">{{ $unreadCount }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="dropdown dropup">
                <a class="sidebar-nav-link sidebar-user-toggle dropdown-toggle" href="#" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                    <span class="sidebar-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1Z"/>
                        </svg>
                    </span>
                    <span class="sidebar-nav-text">Admin</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-start sidebar-dropdown">
                    <li>
                        <form method="POST" action="{{ route('admin.logout') }}" class="d-inline" id="logoutForm">
                            @csrf
                            <a href="#" class="dropdown-item text-danger"
                               onclick="event.preventDefault(); if(confirm('Are you sure you want to logout?')) { document.getElementById('logoutForm').submit(); }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2Z"/>
                                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3Z"/>
                                </svg>
                                Logout
                            </a>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Main content area -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5Z"/>
                </svg>
            </button>
            <div class="admin-topbar-spacer"></div>
        </header>

        <main class="admin-content">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show user-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0Zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05Z"/>
                        </svg>
                        <div class="flex-grow-1 fw-medium">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show user-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0Z"/>
                            <path d="M5.354 4.646a.5.5 0 1 0-.708.708L7.293 8 4.646 10.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646Z"/>
                        </svg>
                        <div class="flex-grow-1 fw-medium">{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show user-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16Zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287Z"/>
                            <path d="M8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                        </svg>
                        <div class="flex-grow-1 fw-medium">{{ session('info') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="opacity: 1;"></button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery (only if needed elsewhere) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous"></script>

    <!-- Sidebar behavior & active link highlighting -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const body = document.body;
            const sidebar = document.getElementById('adminSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggleBtn = document.getElementById('sidebarToggle');
            const closeBtn = document.getElementById('sidebarClose');

            function openSidebar() {
                sidebar.classList.add('sidebar-open');
                if (overlay) {
                    overlay.classList.add('show');
                }
                body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('sidebar-open');
                if (overlay) {
                    overlay.classList.remove('show');
                }
                body.style.overflow = '';
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', openSidebar);
            }
            if (closeBtn) {
                closeBtn.addEventListener('click', closeSidebar);
            }
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            // Close drawer on resize to desktop
            window.addEventListener('resize', function () {
                if (window.innerWidth >= 992) {
                    closeSidebar();
                }
            });

            // Active link highlighting
            const currentUrl = window.location.href.split('?')[0];
            const navLinks = document.querySelectorAll('.sidebar-nav-link:not(.sidebar-nav-toggle)');
            
            navLinks.forEach(link => {
                try {
                    const linkUrl = link.href ? link.href.split('?')[0] : '';
                    if (linkUrl === currentUrl) {
                        navLinks.forEach(l => l.classList.remove('active'));
                        link.classList.add('active');
                        
                        // Expand parent menu if link is in submenu
                        const submenu = link.closest('.sidebar-nav-submenu');
                        if (submenu) {
                            const toggleBtn = document.querySelector(`[data-bs-target="#${submenu.id}"]`);
                            if (toggleBtn && !submenu.classList.contains('show')) {
                                const bsCollapse = new bootstrap.Collapse(submenu, { toggle: false });
                                bsCollapse.show();
                            }
                        }
                    }
                } catch (e) {
                    // Ignore malformed hrefs
                }
            });
            
            // Handle chevron rotation on toggle
            const toggleButtons = document.querySelectorAll('.sidebar-nav-toggle');
            toggleButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-bs-target');
                    const target = document.querySelector(targetId);
                    if (target) {
                        target.addEventListener('shown.bs.collapse', function() {
                            btn.setAttribute('aria-expanded', 'true');
                        });
                        target.addEventListener('hidden.bs.collapse', function() {
                            btn.setAttribute('aria-expanded', 'false');
                        });
                    }
                });
            });
        });
    </script>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
