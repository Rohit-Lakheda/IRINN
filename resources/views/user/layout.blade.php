<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Applicant Dashboard')</title>

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

    <!-- User Sidebar Theme (purple) -->
    <link rel="stylesheet" href="{{ asset('css/user-sidebar.css') }}">

    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="user-panel user-sidebar-layout">
    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>

    <!-- Left Sidebar -->
    <aside class="user-sidebar" id="userSidebar">
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

        <nav class="sidebar-nav">
            <ul class="sidebar-nav-list">
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ route('user.dashboard') }}">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l1.146 1.147a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5ZM13 7.207V13.5a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5V7.207l5-5 5 5Z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Dashboard</span>
                    </a>
                </li>
                @php
                    $currentUser = \App\Models\Registration::find(session('user_id'));
                    $unreadCount = $currentUser ? $currentUser->unreadMessagesCount() : 0;
                @endphp
                @if($currentUser && ($currentUser->status === 'approved' || $currentUser->status === 'active'))
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ route('user.applications.index') }}">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11 2a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3zM5 1a4 4 0 0 0-4 4v6a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4V5a4 4 0 0 0-4-4z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Applications</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ route('user.invoices.index') }}">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Invoices</span>
                    </a>
                </li>
                @endif
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ route('user.grievance.index') }}">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Grievance</span>
                    </a>
                </li>
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link position-relative" href="{{ route('user.messages.index') }}">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4 4 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4 4 0 0 0-3.203-3.92zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5 5 0 0 1 13 6c0 .88.32 4.2 1.22 6"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Notifications</span>
                        @if($unreadCount > 0)
                            <span class="sidebar-nav-badge" id="messageBadge">{{ $unreadCount }}</span>
                        @endif
                    </a>
                </li>
                @php
                    $wallet = $currentUser ? $currentUser->wallet : null;
                @endphp
                <li class="sidebar-nav-item">
                    <a class="sidebar-nav-link" href="{{ $wallet ? route('user.wallet.index') : route('user.wallet.create') }}" title="Wallet">
                        <span class="sidebar-nav-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M12.136.326A1.5 1.5 0 0 1 14 1.78V3h.5A1.5 1.5 0 0 1 16 4.5v9a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 13.5v-9a1.5 1.5 0 0 1 1.432-1.499zM5.562 3H13V1.78a.5.5 0 0 0-.621-.484zM1.5 4a.5.5 0 0 0-.5.5v9a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5z"/>
                            </svg>
                        </span>
                        <span class="sidebar-nav-text">Wallet</span>
                        @if($wallet && $wallet->balance > 0)
                            <span class="sidebar-wallet-badge">₹{{ number_format($wallet->balance, 0) }}</span>
                        @endif
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="dropdown dropup">
                <a class="sidebar-nav-link sidebar-user-toggle dropdown-toggle" href="{{ route('user.profile') }}" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
                    <span class="sidebar-nav-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                        </svg>
                    </span>
                    <span class="sidebar-nav-text">Account</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-start sidebar-dropdown">
                    <li>
                        <a class="dropdown-item" href="{{ route('user.profile') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                            </svg>
                            Profile
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ asset('docs/user-guide.pdf') }}" download>
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                                <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                            </svg>
                            User Guide
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('login.logout') }}" id="logoutForm">
                            @csrf
                            <a href="#" class="dropdown-item text-danger" onclick="event.preventDefault(); if(confirm('Are you sure you want to logout?')) { document.getElementById('logoutForm').submit(); }">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
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
    <div class="user-main">
        <!-- Top bar (mobile menu + optional breadcrumb) -->
        <header class="user-topbar">
            <button type="button" class="sidebar-toggle d-lg-none" id="sidebarToggle" aria-label="Open menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>
            <div class="user-topbar-spacer"></div>
        </header>

        <main class="user-content">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show user-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 4.384 6.323a.75.75 0 0 0-1.06 1.061L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                        </svg>
                        <div class="flex-grow-1 fw-medium">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show user-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
                        </svg>
                        <div class="flex-grow-1 fw-medium">{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @if (session('info'))
                <div class="alert alert-info alert-dismissible fade show user-alert" role="alert">
                    <div class="d-flex align-items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                        <div class="flex-grow-1 fw-medium">{{ session('info') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('userSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            const toggle = document.getElementById('sidebarToggle');
            const closeBtn = document.getElementById('sidebarClose');

            function openSidebar() {
                sidebar.classList.add('sidebar-open');
                overlay.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            function closeSidebar() {
                sidebar.classList.remove('sidebar-open');
                overlay.classList.remove('show');
                document.body.style.overflow = '';
            }

            if (toggle) toggle.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            // Active link
            const currentUrl = window.location.href;
            document.querySelectorAll('.sidebar-nav-link').forEach(function(link) {
                if (link.href && link.href === currentUrl) {
                    link.classList.add('active');
                }
            });
        });
    </script>

    <script>
        function updateUnreadCount() {
            fetch('{{ route("user.messages.unread.count") }}')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('messageBadge');
                    if (data.count > 0) {
                        if (badge) {
                            badge.textContent = data.count;
                        } else {
                            const messageLink = document.querySelector('a[href="{{ route("user.messages.index") }}"]');
                            if (messageLink) {
                                const newBadge = document.createElement('span');
                                newBadge.className = 'sidebar-nav-badge';
                                newBadge.id = 'messageBadge';
                                newBadge.textContent = data.count;
                                messageLink.appendChild(newBadge);
                            }
                        }
                    } else {
                        if (badge) badge.remove();
                    }
                })
                .catch(error => console.error('Error updating unread count:', error));
        }
        setInterval(updateUnreadCount, 30000);
    </script>

    @stack('scripts')
</body>
</html>
