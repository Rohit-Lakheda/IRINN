<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer - ⚠️ TEMPORARY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .log-container {
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 15px;
            border-radius: 5px;
            max-height: 70vh;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .log-line {
            margin-bottom: 2px;
            padding: 2px 5px;
        }
        .log-line.error {
            background-color: #3d1a1a;
            color: #ff6b6b;
        }
        .log-line.warning {
            background-color: #3d3a1a;
            color: #ffd93d;
        }
        .log-line.success {
            background-color: #1a3d2a;
            color: #6bcf7f;
        }
        .log-line.info {
            color: #4dabf7;
        }
        .warning-banner {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .stats {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="warning-banner">
            <h4>⚠️ TEMPORARY LOG VIEWER</h4>
            <p class="mb-0"><strong>WARNING:</strong> This is a temporary debugging tool. Remove this route after debugging is complete!</p>
        </div>

        <div class="stats">
            <div class="row">
                <div class="col-md-3">
                    <strong>Total Lines:</strong> {{ number_format($totalLines) }}
                </div>
                <div class="col-md-3">
                    <strong>Showing:</strong> {{ number_format($showingLines) }} lines
                </div>
                <div class="col-md-3">
                    <strong>File Size:</strong> {{ number_format($fileSize / 1024, 2) }} KB
                </div>
                <div class="col-md-3">
                    <strong>Last Modified:</strong> {{ $lastModified }}
                </div>
            </div>
        </div>

        {{-- Payment Reset Section --}}
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Reset Payment Status (Debug Tool)</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.reset-payment-status') }}" id="resetPaymentForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Application ID or Application Number</label>
                            <input type="text" name="application_identifier" id="applicationIdentifier" 
                                class="form-control" placeholder="Enter Application ID (e.g., APP12345678) or Database ID" required>
                            <small class="text-muted">Enter either the application_id (e.g., APP12345678) or the database ID</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset Payment to Pending
                            </button>
                        </div>
                    </div>
                </form>
                @if(session('payment_reset_success'))
                    <div class="alert alert-success mt-3 mb-0">
                        {{ session('payment_reset_success') }}
                    </div>
                @endif
                @if(session('payment_reset_error'))
                    <div class="alert alert-danger mt-3 mb-0">
                        {{ session('payment_reset_error') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Laravel Logs</h5>
                <div>
                    <form method="GET" action="{{ route('admin.view-logs') }}" class="d-inline">
                        <div class="btn-group" role="group">
                            <select name="filter" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>All Logs</option>
                                <option value="gst" {{ $filter === 'gst' ? 'selected' : '' }}>GST Verification Only</option>
                                <option value="email" {{ $filter === 'email' ? 'selected' : '' }}>Email/Mail Only</option>
                                <option value="payu" {{ $filter === 'payu' ? 'selected' : '' }}>PayU/Payment Only</option>
                                <option value="errors" {{ $filter === 'errors' ? 'selected' : '' }}>Errors Only</option>
                            </select>
                            <select name="lines" class="form-select form-select-sm d-inline-block" style="width: auto;" onchange="this.form.submit()">
                                <option value="50" {{ $lines == 50 ? 'selected' : '' }}>Last 50</option>
                                <option value="100" {{ $lines == 100 ? 'selected' : '' }}>Last 100</option>
                                <option value="200" {{ $lines == 200 ? 'selected' : '' }}>Last 200</option>
                                <option value="500" {{ $lines == 500 ? 'selected' : '' }}>Last 500</option>
                                <option value="1000" {{ $lines == 1000 ? 'selected' : '' }}>Last 1000</option>
                            </select>
                            <button type="button" class="btn btn-sm btn-primary" onclick="location.reload()">Refresh</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="log-container">
                    @forelse($logs as $line)
                        @php
                            $lineClass = 'log-line';
                            if (stripos($line, 'ERROR') !== false || stripos($line, 'Exception') !== false || stripos($line, 'Failed') !== false) {
                                $lineClass .= ' error';
                            } elseif (stripos($line, 'Warning') !== false || stripos($line, 'WARNING') !== false) {
                                $lineClass .= ' warning';
                            } elseif (stripos($line, 'Success') !== false || stripos($line, 'SUCCESS') !== false) {
                                $lineClass .= ' success';
                            } else {
                                $lineClass .= ' info';
                            }
                        @endphp
                        <div class="{{ $lineClass }}">{{ htmlspecialchars($line) }}</div>
                    @empty
                        <div class="text-center text-muted py-5">No logs found with current filter</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-3 text-center">
            <small class="text-muted">
                Log File: <code>{{ $logFile }}</code>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll to bottom
        const logContainer = document.querySelector('.log-container');
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }
    </script>
</body>
</html>

