<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <meta http-equiv="refresh" content="2;url={{ $redirectUrl }}">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .container {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 1rem;
        }
        p {
            color: #6b7280;
            margin-bottom: 1.5rem;
        }
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #ef4444;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .redirect-link {
            display: inline-block;
            margin-top: 1rem;
            color: #f5576c;
            text-decoration: none;
            font-weight: 500;
        }
        .redirect-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-icon">✗</div>
        <h1>Payment Failed</h1>
        <p>{{ $message }}</p>
        <p>Redirecting you back...</p>
        <div class="spinner"></div>
        <a href="{{ $redirectUrl }}" class="redirect-link">Click here if you are not redirected automatically</a>
    </div>
    
    <script>
        // Redirect immediately to login-from-cookie route which will set session
        // No delay needed - the login route will handle session setup
        window.location.href = '{{ $redirectUrl }}';
    </script>
</body>
</html>

