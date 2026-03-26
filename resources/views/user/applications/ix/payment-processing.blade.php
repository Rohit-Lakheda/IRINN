<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .spinner-container {
            margin: 2rem 0;
        }
        .spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        p {
            color: #6b7280;
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        .warning {
            color: #f59e0b;
            font-weight: 500;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner-container">
            <div class="spinner"></div>
        </div>
        <h1>{{ $message ?? 'Processing...' }}</h1>
        <p>{{ $submessage ?? 'Please wait while we process your request.' }}</p>
        <p class="warning">⚠️ Do not refresh or go back</p>
    </div>
    
    <script>
        @if(isset($autoRefresh) && $autoRefresh)
        // Auto-refresh like view-logs page - refresh immediately to establish session
        // This will trigger the second visit where session count = 1
        location.reload();
        @else
        // Redirect to the same URL after a brief delay to establish session
        setTimeout(function() {
            window.location.href = '{{ $redirectUrl }}';
        }, 500); // Small delay to ensure session is set
        @endif
    </script>
</body>
</html>

