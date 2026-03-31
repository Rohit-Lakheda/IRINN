@extends('superadmin.layout')

@section('title', 'Super Admin Dashboard')

@section('content')
<div class="py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div class="flex-wrap">
                    <h2 class="mb-1 border-0">Super Admin Dashboard</h2>
                    <p class="mb-2">Welcome back, <strong>{{ $superAdmin->name ?? 'Super Admin' }}</strong>!</p>
                </div>
                <div>
                    <a href="{{ route('superadmin.applications.index') }}" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                        </svg>
                        Backend Data Entry
                    </a>
                </div>
            </div>
            <div class="accent-line"></div>
        </div>
    </div>

    <!-- Global Search (visual parity with Admin) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card theme-bg-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('superadmin.users') }}" class="d-flex gap-2 theme-forms">
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search anywhere (registrations, applications, members, invoices, grievances...)"
                               value="{{ request('search') }}"
                               autocomplete="off">
                        <button type="submit" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                            </svg>
                            Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- IX Points Visibility Section (removed: IX is no longer part of this portal)
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">IX Points Visibility</h5>
                </div>
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('superadmin.ip-pricing.index') }}" class="text-decoration-none">
                                <div class="card border-c-blue shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Total IX Points</h6>
                                                <h2 class="mb-0 text-blue fw-bold border-0">{{ $totalIxPoints }}</h2>
                                            </div>
                                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" class="bi bi-globe" viewBox="0 0 16 16">
                                                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7.03 7.03 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99a12.5 12.5 0 0 0-.337-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5zM5.145 12q.208.58.468 1.068c.552 1.035 1.218 1.65 1.887 1.855V12zm.182 2.472a7 7 0 0 1-.597-.933A9.3 9.3 0 0 1 4.09 12H2.255a7 7 0 0 0 3.072 2.472M3.82 11a13.7 13.7 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5zm6.853 3.472A7 7 0 0 0 13.745 12H11.91a9.3 9.3 0 0 1-.64 1.539 7 7 0 0 1-.597.933M8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855q.26-.487.468-1.068zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.7 13.7 0 0 1-.312 2.5m2.802-3.5a7 7 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7 7 0 0 0-3.072-2.472c.218.284.418.598.597.933M10.855 4a8 8 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('superadmin.ip-pricing.index') }}?node_type=edge" class="text-decoration-none">
                                <div class="card border-c-blue shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Edge IX Points</h6>
                                                <h2 class="mb-0 text-blue fw-bold border-0">{{ $edgeIxPoints }}</h2>
                                            </div>
                                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" class="bi bi-globe" viewBox="0 0 16 16">
                                                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7.03 7.03 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99a12.5 12.5 0 0 0-.337-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5zM5.145 12q.208.58.468 1.068c.552 1.035 1.218 1.65 1.887 1.855V12zm.182 2.472a7 7 0 0 1-.597-.933A9.3 9.3 0 0 1 4.09 12H2.255a7 7 0 0 0 3.072 2.472M3.82 11a13.7 13.7 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5zm6.853 3.472A7 7 0 0 0 13.745 12H11.91a9.3 9.3 0 0 1-.64 1.539 7 7 0 0 1-.597.933M8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855q.26-.487.468-1.068zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.7 13.7 0 0 1-.312 2.5m2.802-3.5a7 7 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7 7 0 0 0-3.072-2.472c.218.284.418.598.597.933M10.855 4a8 8 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('superadmin.ip-pricing.index') }}?node_type=metro" class="text-decoration-none">
                                <div class="card border-c-blue shadow-sm" style="border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" 
                                     onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)'"
                                     onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="text-muted mb-2" style="font-size: 0.875rem; font-weight: 500;">Metro IX Points</h6>
                                                <h2 class="mb-0 text-blue fw-bold border-0">{{ $metroIxPoints }}</h2>
                                            </div>
                                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" class="bi bi-globe" viewBox="0 0 16 16">
                                                    <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m7.5-6.923c-.67.204-1.335.82-1.887 1.855A8 8 0 0 0 5.145 4H7.5zM4.09 4a9.3 9.3 0 0 1 .64-1.539 7 7 0 0 1 .597-.933A7.03 7.03 0 0 0 2.255 4zm-.582 3.5c.03-.877.138-1.718.312-2.5H1.674a7 7 0 0 0-.656 2.5zM4.847 5a12.5 12.5 0 0 0-.338 2.5H7.5V5zM8.5 5v2.5h2.99a12.5 12.5 0 0 0-.337-2.5zM4.51 8.5a12.5 12.5 0 0 0 .337 2.5H7.5V8.5zm3.99 0V11h2.653c.187-.765.306-1.608.338-2.5zM5.145 12q.208.58.468 1.068c.552 1.035 1.218 1.65 1.887 1.855V12zm.182 2.472a7 7 0 0 1-.597-.933A9.3 9.3 0 0 1 4.09 12H2.255a7 7 0 0 0 3.072 2.472M3.82 11a13.7 13.7 0 0 1-.312-2.5h-2.49c.062.89.291 1.733.656 2.5zm6.853 3.472A7 7 0 0 0 13.745 12H11.91a9.3 9.3 0 0 1-.64 1.539 7 7 0 0 1-.597.933M8.5 12v2.923c.67-.204 1.335-.82 1.887-1.855q.26-.487.468-1.068zm3.68-1h2.146c.365-.767.594-1.61.656-2.5h-2.49a13.7 13.7 0 0 1-.312 2.5m2.802-3.5a7 7 0 0 0-.656-2.5H12.18c.174.782.282 1.623.312 2.5zM11.27 2.461c.247.464.462.98.64 1.539h1.835a7 7 0 0 0-3.072-2.472c.218.284.418.598.597.933M10.855 4a8 8 0 0 0-.468-1.068C9.835 1.897 9.17 1.282 8.5 1.077V4z"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    --}}

    <!-- Payment Summary Section -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold d-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                            <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2zm13 2.383-4.708 2.825L15 11.105V5.383zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                        </svg>
                        Payment Summary - {{ now('Asia/Kolkata')->format('F Y') }}
                    </h5>
                </div>
                <div class="card-body p-3">
                    <div class="row justify-content-center justify-content-xl-start g-3 mb-4">
                        <!-- Invoices Generated This Month -->
                        <div class="col-md-6 col-lg-3 col-xl-2">
                            <div class="card border-c-blue shadow-sm bg-primary bg-opacity-10 mb-0 h-100" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#0d6efd" class="bi bi-receipt" viewBox="0 0 16 16">
                                            <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27m.217 1.338L2 2.118v11.764l.137.274.51-.51a.5.5 0 0 1 .707 0l.646.647.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.646.646.646-.646a.5.5 0 0 1 .708 0l.509.509.137-.274V2.118l-.137-.274-.51.51a.5.5 0 0 1-.707 0L12 1.707l-.646.647a.5.5 0 0 1-.708 0L10 1.707l-.646.647a.5.5 0 0 1-.708 0L8 1.707l-.646.647a.5.5 0 0 1-.708 0L6 1.707l-.646.647a.5.5 0 0 1-.708 0L4 1.707l-.646.647a.5.5 0 0 1-.708 0z"/>
                                            <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5"/>
                                        </svg>
                                    </div>
                                    <h3 class="mb-2 fs-5 fw-bold" style="color: #0d6efd;">{{ number_format($invoicesThisMonth) }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500; font-size: 0.875rem;">Bills Generated</p>
                                    <small class="text-muted d-block mt-1">
                                        Service: {{ number_format($serviceInvoicesThisMonth ?? 0) }} |
                                        Reactivation: {{ number_format($reactivationInvoicesThisMonth ?? 0) }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <!-- Total Invoiced This Month -->
                        <div class="col-md-6 col-lg-3 col-xl-2">
                            <div class="card border-c-blue shadow-sm bg-info bg-opacity-10 mb-0 h-100" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#198754" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                        </svg>
                                    </div>
                                    <h3 class="mb-2 border-0 fs-5 fw-bold" style="color: #0dcaf0;">₹{{ number_format($totalInvoicedThisMonth, 2) }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500; font-size: 0.875rem;">Total Invoiced</p>
                                    <small class="text-muted d-block mt-1">
                                        Service: ₹{{ number_format($serviceInvoicedThisMonth ?? 0, 2) }} |
                                        Reactivation: ₹{{ number_format($reactivationInvoicedThisMonth ?? 0, 2) }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <!-- Amount Collected from Invoices This Month -->
                        <div class="col-md-6 col-lg-3 col-xl-2">
                            <div class="card border-c-blue shadow-sm bg-success bg-opacity-10 mb-0 h-100" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#0dcaf0" class="bi bi-currency-rupee" viewBox="0 0 16 16">
                                            <path d="M4 3.06h2.726c1.22 0 2.12.575 2.325 1.724H4v1.051h5.051C8.855 7.001 8 7.558 6.788 7.558H4v1.317L8.437 14h2.11L6.095 8.884h.855c2.316-.018 3.465-1.476 3.688-3.049H12V4.784h-1.345c-.08-.778-.357-1.335-.793-1.732H12V2H4z"/>
                                        </svg>
                                    </div>
                                    <h3 class="mb-2 border-0 fs-5 fw-bold" style="color: #198754;">₹{{ number_format($totalCollectedThisMonth, 2) }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500; font-size: 0.875rem;">Invoice Payments</p>
                                    <small class="text-muted d-block mt-1">
                                        Service: ₹{{ number_format($serviceCollectedThisMonth ?? 0, 2) }} |
                                        Reactivation: ₹{{ number_format($reactivationCollectedThisMonth ?? 0, 2) }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <!-- Application Payments This Month -->
                        <div class="col-md-6 col-lg-3 col-xl-2">
                            <div class="card border-c-blue shadow-sm bg-secondary bg-opacity-10 mb-0 h-100" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#2B2F6C" class="bi bi-app" viewBox="0 0 16 16">
                                            <path d="M11 2a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3zM5 1a4 4 0 0 0-4 4v6a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4V5a4 4 0 0 0-4-4z"/>
                                        </svg>
                                    </div>
                                    <h3 class="mb-2 border-0 fs-5 fw-bold" style="color: #6c757d;">₹{{ number_format($applicationPaymentsThisMonth, 2) }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500; font-size: 0.875rem;">Application Payments</p>
                                </div>
                            </div>
                        </div>
                        <!-- Pending Amount -->
                        <div class="col-md-6 col-lg-3 col-xl-2">
                            <div class="card border-c-blue shadow-sm bg-warning bg-opacity-10 mb-0 h-100" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <div class="mb-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#ffc107" viewBox="0 0 16 16">
                                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                            <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                        </svg>
                                    </div>
                                    <h3 class="mb-2 border-0 fs-5 fw-bold" style="color: #ffc107;">₹{{ number_format($totalPendingAmount, 2) }}</h3>
                                    <p class="mb-0 text-muted" style="font-weight: 500; font-size: 0.875rem;">Pending Amount</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accent-line"></div>

                    <!-- Detailed Statistics -->
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header bg-primary" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 text-blue fw-semibold">Invoice Status Breakdown (All Time)</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted fw-semibold">Total Invoices:</span>
                                        <strong style="color: #2c3e50;">{{ number_format($totalInvoices) }}</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-success fw-semibold">Paid:</span>
                                        <strong class="text-success">{{ number_format($paidInvoices) }}</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-gold fw-semibold">Pending:</span>
                                        <strong class="text-gold">{{ number_format($pendingInvoices) }}</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-info fw-semibold">Partial:</span>
                                        <strong class="text-info">{{ number_format($partialInvoices) }}</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-danger fw-semibold">Overdue:</span>
                                        <strong class="text-danger">{{ number_format($overdueInvoices) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 12px;">
                                <div class="card-header bg-primary" style="border-radius: 12px 12px 0 0;">
                                    <h6 class="mb-0 text-blue fw-semibold">Additional Information</h6>
                                </div>
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted fw-semibold">Partial Payments (This Month):</span>
                                        <strong style="color: #2c3e50;">{{ number_format($partialPaymentsThisMonth) }}</strong>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-danger fw-semibold">Overdue Amount:</span>
                                        <strong class="text-danger">₹{{ number_format($totalOverdueAmount, 2) }}</strong>
                                    </div>
                                    <hr>
                                    @if($totalInvoicedThisMonth > 0)
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fw-semibold">Collection Rate:</span>
                                        <strong style="color: #2c3e50;">
                                            {{ number_format(($totalCollectedThisMonth / $totalInvoicedThisMonth) * 100, 2) }}%
                                        </strong>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Applications & Member Statistics Row -->
    <div class="row g-4 mb-4">
        <!-- Approved Applications -->
        <div class="col-md-6">
            <div class="card border-c-green shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header theme-bg-green text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Approved Applications</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2 fw-semibold">Total Approved</h6>
                            <h2 class="mb-0 border-0 fw-bold">{{ $approvedApplications }}</h2>
                            @if($approvedApplicationsWithPayment > 0)
                            <small class="text-green">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#3B823E" viewBox="0 0 16 16" style="display: inline-block; vertical-align: middle;">
                                    <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                                </svg>
                                {{ $approvedApplicationsWithPayment }} verified payments
                            </small>
                            @endif
                        </div>
                        <div class="theme-bg-green bg-opacity-10 rounded-circle p-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                <path d="M10.97 4.97a.75.75 0 0 1 1.07 1.05l-3.99 4.99a.75.75 0 0 1-1.08.02L4.324 8.384a.75.75 0 1 1 1.06-1.06l2.094 2.093 3.473-4.425a.267.267 0 0 1 .02-.022z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Member Statistics -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm h-100" style="border-radius: 16px;">
                <div class="card-header theme-bg-blue text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold border-0">Member Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="{{ route('superadmin.users') }}" class="text-decoration-none">
                                <div class="text-center p-3 rounded bg-primary h-100" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                    <h6 class="mb-2 border-0 fw-normal" style="color: #fff !important;">Total</h6>
                                    <h4 class="mb-0 fw-bold border-0" style="color: #fff !important;">{{ $totalMembers }}</h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('superadmin.users', ['filter' => 'active']) }}" class="text-decoration-none">
                                <div class="text-center p-3 rounded bg-success h-100" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                    <h6 class="mb-2 border-0 fw-normal" style="color: #fff !important;">Live</h6>
                                    <h4 class="mb-0 fw-bold border-0" style="color: #fff !important;">{{ $activeMembers }}</h4>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('superadmin.users', ['filter' => 'disconnected']) }}" class="text-decoration-none">
                                <div class="text-center p-3 rounded bg-danger h-100" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='transparent'">
                                    <h6 class="mb-2 border-0 fw-normal" style="color: #fff !important;">Not Live</h6>
                                    <h4 class="mb-0 fw-bold border-0" style="color: #fff !important;">{{ $disconnectedMembers }}</h4>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Live Members & Grievance Tracking Row -->
    <div class="row g-4 mb-4">
        <!-- Recent Live Members -->
        @if($recentLiveMembers->count() > 0)
        <div class="col-md-8">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Recent Live Members</h5>
                    <a href="{{ route('superadmin.users', ['filter' => 'active']) }}" class="btn btn-sm border-white">
                        View All Live Members
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="text-nowrap">
                                <tr>
                                    <th style="color: #2c3e50; font-weight: 600;">Application ID</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Membership ID</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Member Name</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Application Status</th>
                                    <th style="color: #2c3e50; font-weight: 600;">Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentLiveMembers as $application)
                                <tr class="align-middle">
                                    <td><a href="{{ route('superadmin.users.show', $application->user_id) }}" style="color: #0d6efd; text-decoration: none;">{{ $application->application_id }}</a></td>
                                    <td><strong>{{ $application->membership_id }}</strong></td>
                                    <td>{{ $application->user->fullname ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge rounded-pill px-3 py-1
                                            @if($application->status === 'approved' || $application->status === 'payment_verified') bg-success
                                            @elseif(in_array($application->status, ['ip_assigned', 'invoice_pending'])) bg-info
                                            @else bg-secondary @endif">
                                            {{ $application->status_display }}
                                        </span>
                                    </td>
                                    <td>{{ $application->updated_at->format('M d, Y h:i A') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <!-- Grievance Tracking -->
        <div class="col-md-4">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Grievance Tracking</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <a href="{{ route('superadmin.grievance.index') }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between p-3 border  border-c-blue rounded shadow-sm">
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Open Grievances</h6>
                                    <h3 class="mb-0 border-0" style="color: #2c3e50; font-weight: 700;">{{ $openGrievances }}</h3>
                                </div>
                                <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-check-circle" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                                        <path d="m10.97 4.97-.02.022-3.473 4.425-2.093-2.094a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="mb-3">
                        <a href="{{ route('superadmin.grievance.index', ['status' => 'assigned']) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between p-3 border  border-c-blue rounded shadow-sm">
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Pending Requests</h6>
                                    <h3 class="mb-0 border-0" style="color: #2c3e50; font-weight: 700;">{{ $pendingGrievances }}</h3>
                                </div>
                                <div class="theme-bg-gold bg-opacity-10 rounded-circle p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" class="bi bi-clock-history" viewBox="0 0 16 16">
                                        <path d="M8.515 1.019A7 7 0 0 0 8 1V0a8 8 0 0 1 .589.022zm2.004.45a7 7 0 0 0-.985-.299l.219-.976q.576.129 1.126.342zm1.37.71a7 7 0 0 0-.439-.27l.493-.87a8 8 0 0 1 .979.654l-.615.789a7 7 0 0 0-.418-.302zm1.834 1.79a7 7 0 0 0-.653-.796l.724-.69q.406.429.747.91zm.744 1.352a7 7 0 0 0-.214-.468l.893-.45a8 8 0 0 1 .45 1.088l-.95.313a7 7 0 0 0-.179-.483m.53 2.507a7 7 0 0 0-.1-1.025l.985-.17q.1.58.116 1.17zm-.131 1.538q.05-.254.081-.51l.993.123a8 8 0 0 1-.23 1.155l-.964-.267q.069-.247.12-.501m-.952 2.379q.276-.436.486-.908l.914.405q-.24.54-.555 1.038zm-.964 1.205q.183-.183.35-.378l.758.653a8 8 0 0 1-.401.432z"/>
                                        <path d="M8 1a7 7 0 1 0 4.95 11.95l.707.707A8.001 8.001 0 1 1 8 0z"/>
                                        <path d="M7.5 3a.5.5 0 0 1 .5.5v5.21l3.248 1.856a.5.5 0 0 1-.496.868l-3.5-2A.5.5 0 0 1 7 9V3.5a.5.5 0 0 1 .5-.5"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div>
                        <a href="{{ route('superadmin.grievance.index', ['status' => 'closed']) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center justify-content-between p-3 border  border-c-blue rounded shadow-sm">
                                <div>
                                    <h6 class="text-muted mb-1" style="font-size: 0.875rem; font-weight: 500;">Closed Grievances</h6>
                                    <h3 class="mb-0 border-0" style="color: #2c3e50; font-weight: 700;">{{ $closedGrievances }}</h3>
                                </div>
                                <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="#ffffff" viewBox="0 0 16 16">
                                        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                        <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- SuperAdmin Details -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="mb-0">SuperAdmin Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Name</label>
                                <p class="mb-0 fw-semibold fs-6 text-black">{{ $superAdmin->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">Email</label>
                                <p class="mb-0 fw-semibold fs-6 text-black">{{ $superAdmin->email }}</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="text-muted small mb-1">User ID</label>
                                <p class="mb-0 fw-semibold fs-6 text-black">{{ $superAdmin->userid }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-3">
                        <a href="{{ route('superadmin.applications.index') }}" class="btn btn-primary fw-semibold text-white" style="border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/>
                            </svg>
                            Backend Data Entry
                        </a>
                        <a href="{{ route('superadmin.users') }}" class="btn btn-info fw-semibold text-white" style="border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                            </svg>
                            View All Users
                        </a>
                        <a href="{{ route('superadmin.admins') }}" class="btn btn-success1 theme-bg-green fw-semibold text-white" style="border-radius: 10px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="me-2" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                            </svg>
                            View All Admins
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Admin and Roles Chart -->
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="mb-0">Admin and Roles</h5>
                </div>
                <div class="card-body">
                    @if($adminsWithRoles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0" style="border-radius: 8px; overflow: hidden;">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th style="min-width: 200px;">Admin Name</th>
                                        @foreach($roleSlugs as $roleSlug)
                                            <th style="font-weight: 600; padding: 12px; text-align: center; min-width: 120px;">
                                                {{ ucfirst($roles[$roleSlug]->name ?? $roleSlug) }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($adminsWithRoles as $admin)
                                        <tr class="align-middle">
                                            <td>
                                                <span class="fw-semibold">{{ $admin->name }}</span>
                                                @if(!$admin->is_active)
                                                    <span class="badge bg-secondary ms-2 text-capitalize">Inactive</span>
                                                @endif
                                            </td>
                                            @foreach($roleSlugs as $roleSlug)
                                                <td class="text-center">
                                                    @php
                                                        $hasRole = $admin->roles->contains(function($role) use ($roleSlug) {
                                                            return $role->slug === $roleSlug;
                                                        });
                                                    @endphp
                                                    @if($hasRole)
                                                        <i class="bi bi-check-circle-fill text-success fs-5 opacity-75"></i>
                                                    @else
                                                        <i class="bi bi-x-circle-fill text-danger fs-5 opacity-75"></i>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#2B2F6C" class="mb-2" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                            </svg>
                            <p class="text-muted mb-0">No admins found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Application Details Chart -->
        <div class="col-md-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white" style="border-radius: 16px 16px 0 0;">
                    <h5 class="mb-0 fw-semibold">Application Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Total Applications -->
                        <div class="col col-md-3">
                            <div class="card border-c-blue shadow-sm bg-primary bg-opacity-10" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <h3 class="mb-2 fw-bold">{{ $totalApplications }}</h3>
                                    <p class="mb-0 text-muted fw-medium">Total Applications</p>
                                </div>
                            </div>
                        </div>
                        <!-- Fully Approved -->
                        <div class="col col-md-3">
                            <div class="card border-c-blue shadow-sm bg-success bg-opacity-10" style="border-radius: 12px;">
                                <div class="card-body text-center p-4">
                                    <h3 class="mb-2 text-green border-0 fw-bold" style="color: #3B823E !important;">{{ $fullyApproved }}</h3>
                                    <p class="mb-0 text-green fe-medium">Fully Approved</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0" style="border-radius: 8px; overflow: hidden;">
                            <thead class="text-nowrap">
                                <tr>
                                    <th style="min-width: 180px;">IRINN Stage</th>
                                    <th class="text-center" style="min-width: 150px;">Applications</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="align-middle">
                                    <td class="fw-semibold text-black">Helpdesk</td>
                                    <td class="fw-semibold text-black text-center">
                                        <span class="text-success fs-6 fw-semibold justify-content-center">{{ $irinnHelpdeskCount ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr class="align-middle">
                                    <td class="fw-semibold text-black">Hostmaster</td>
                                    <td class="fw-semibold text-black text-center">
                                        <span class="text-primary fs-6 fw-semibold justify-content-center">{{ $irinnHostmasterCount ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr class="align-middle">
                                    <td class="fw-semibold text-black">Billing</td>
                                    <td class="fw-semibold text-black text-center">
                                        <span class="text-gold fs-6 fw-semibold justify-content-center">{{ $irinnBillingCount ?? 0 }}</span>
                                    </td>
                                </tr>
                                <tr class="align-middle">
                                    <td class="fw-semibold text-black">Billing Approved</td>
                                    <td class="fw-semibold text-black text-center">
                                        <span class="text-success fs-6 fw-semibold justify-content-center">{{ $irinnBillingApprovedCount ?? 0 }}</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Logged In Users -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapseRecentUsers" 
                     aria-expanded="false" 
                     aria-controls="collapseRecentUsers">
                    <h5 class="mb-0 fw-semibold">Recent Logged In Users</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="arrow-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <div id="collapseRecentUsers" class="collapse">
                    <div class="card-body">
                    @if($recentLoggedInUsers->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentLoggedInUsers as $user)
                            <div class="list-group-item px-0 pt-0 pb-3 mb-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start flex-wrap w-100">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 text-black fw-semibold">
                                            <a href="{{ route('superadmin.users.show', $user->id) }}" class="text-decoration-none text-blue fw-semibold"">
                                                {{ $user->fullname }}
                                            </a>
                                        </h6>
                                        <p class="mb-1 text-muted small text-break">{{ $user->email }}</p>
                                        <small class="text-muted mb-2 d-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            Last active: {{ $user->updated_at->diffForHumans() }}
                                        </small>
                                    </div>
                                    <span class="badge rounded-pill px-3 py-1 
                                        @if($user->status === 'approved' || $user->status === 'active') bg-success
                                        @elseif($user->status === 'pending') bg-warning text-dark
                                        @else bg-secondary @endif">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216Z"/>
                                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent user activity.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Admin Activities -->
        <div class="col-md-6">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapseAdminActivities" 
                     aria-expanded="false" 
                     aria-controls="collapseAdminActivities">
                    <h5 class="mb-0 fw-semibold">Recent Admin Activities</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="arrow-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <div id="collapseAdminActivities" class="collapse">
                    <div class="card-body">
                    @if($recentAdminActivities->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentAdminActivities as $activity)
                            <div class="list-group-item px-0 pt-0 pb-3 mb-4 border-bottom">
                                <div class="d-flex justify-content-between align-items-start flex-wrap w-100">
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <h6 class="mb-0 me-2 text-black fw-semibold">
                                                @if($activity->admin)
                                                    {{ $activity->admin->name }}
                                                @else
                                                    System
                                                @endif
                                            </h6>
                                        </div>
                                        @if($activity->description)
                                            <p class="mb-1 text-muted small">{{ $activity->description }}</p>
                                        @endif
                                        <small class="text-muted mb-2 d-block">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16">
                                                <path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/>
                                                <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                                            </svg>
                                            {{ $activity->created_at->format('M d, Y h:i A') }} ({{ $activity->created_at->diffForHumans() }})
                                        </small>
                                    </div>
                                    <span class="badge rounded-pill px-2 py-1 
                                        {{ $activity->action_type === 'admin_login' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $activity->action_type === 'admin_login' ? 'Logged In' : 'Logged Out' }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M7.002 11a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 4.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 4.995z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent admin activities.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="col-md-12">
            <div class="card border-c-blue shadow-sm" style="border-radius: 16px;">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" 
                     style="border-radius: 16px 16px 0 0; cursor: pointer;" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapseRecentMessages" 
                     aria-expanded="false" 
                     aria-controls="collapseRecentMessages">
                    <h5 class="mb-0 fw-semibold">Recent Messages</h5>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="arrow-icon" viewBox="0 0 16 16" style="transition: transform 0.3s; transform: rotate(180deg);">
                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                    </svg>
                </div>
                <div id="collapseRecentMessages" class="collapse">
                    <div class="card-body">
                    @if($recentMessages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="text-nowrap">
                                    <tr>
                                        <th style="color: #2c3e50; font-weight: 600;">User</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Subject</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Message</th>
                                        <th style="color: #2c3e50; font-weight: 600;">From</th>
                                        <th style="color: #2c3e50; font-weight: 600;">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentMessages as $message)
                                    <tr class="align-middle" style="cursor: pointer;" data-row-url="{{ route('superadmin.messages.show', $message->id) }}">
                                        <td>
                                            <a href="{{ route('superadmin.users.show', $message->user_id) }}" class="text-decoration-none" style="color: #2c3e50; font-weight: 500;" onclick="event.stopPropagation();">
                                                {{ $message->user->fullname }}
                                            </a>
                                            <br>
                                            <small class="text-muted">{{ $message->user->email }}</small>
                                        </td>
                                        <td style="color: #2c3e50;">{{ $message->subject }}</td>
                                        <td>
                                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ \Illuminate\Support\Str::limit($message->message, 100) }}
                                            </div>
                                            @if($message->user_reply)
                                                <div class="mt-2 p-2 bg-light rounded" style="max-width: 300px;">
                                                    <small class="text-muted d-block mb-1"><strong>User Reply:</strong></small>
                                                    <small>{{ \Illuminate\Support\Str::limit($message->user_reply, 80) }}</small>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($message->sent_by === 'admin')
                                                @php
                                                    $adminAction = $recentAdminActions[$message->id] ?? null;
                                                @endphp
                                                @if($adminAction && $adminAction->admin)
                                                    <span class="badge rounded-pill px-3 py-1 bg-primary">
                                                        {{ $adminAction->admin->name }}
                                                    </span>
                                                @else
                                                    <span class="badge rounded-pill px-3 py-1 bg-primary">
                                                        Admin
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge rounded-pill px-3 py-1 bg-info">
                                                    {{ ucfirst($message->sent_by) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $message->created_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="mb-2" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
                            </svg>
                            <p class="text-muted mb-0">No recent messages.</p>
                        </div>
                    @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle arrow rotation for collapsible cards
    const collapseElements = ['collapseRecentUsers', 'collapseAdminActivities', 'collapseRecentMessages'];
    
    collapseElements.forEach(function(collapseId) {
        const collapseElement = document.getElementById(collapseId);
        if (!collapseElement) return;
        
        const headerElement = collapseElement.previousElementSibling;
        if (!headerElement) return;
        
        const arrowIcon = headerElement.querySelector('.arrow-icon');
        if (!arrowIcon) return;
        
        // Initialize arrow based on current state (hidden by default, so arrow points down/180deg)
        if (collapseElement.classList.contains('show')) {
            arrowIcon.style.transform = 'rotate(0deg)';
        } else {
            arrowIcon.style.transform = 'rotate(180deg)';
        }
        
        // Listen for Bootstrap collapse events
        collapseElement.addEventListener('show.bs.collapse', function() {
            arrowIcon.style.transform = 'rotate(0deg)';
            headerElement.setAttribute('aria-expanded', 'true');
        });
        
        collapseElement.addEventListener('hide.bs.collapse', function() {
            arrowIcon.style.transform = 'rotate(180deg)';
            headerElement.setAttribute('aria-expanded', 'false');
        });
        
        collapseElement.addEventListener('shown.bs.collapse', function() {
            headerElement.setAttribute('aria-expanded', 'true');
        });
        
        collapseElement.addEventListener('hidden.bs.collapse', function() {
            headerElement.setAttribute('aria-expanded', 'false');
        });
    });

    // Make recent message rows clickable without embedding Blade inside JS.
    document.querySelectorAll('[data-row-url]').forEach(function(row) {
        row.addEventListener('click', function() {
            const url = row.getAttribute('data-row-url');
            if (!url) return;
            window.location = url;
        });
    });
});
</script>
@endpush
@endsection
