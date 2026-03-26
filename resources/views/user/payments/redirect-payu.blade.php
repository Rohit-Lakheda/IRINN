<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment Gateway...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1.5rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h2 {
            color: #333;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            margin-bottom: 2rem;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>Redirecting to Payment Gateway...</h2>
        <p>Please wait while we redirect you to PayU payment gateway.</p>
        <p class="text-muted small">If you are not redirected automatically, click the button below.</p>
        
        <form name="payu" id="payuForm" method="POST" action="{{ $paymentUrl }}">
            @foreach($paymentData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <button type="submit" class="btn">Proceed to Payment</button>
        </form>
    </div>

    <script>
        // Auto-submit form after 1 second
        window.onload = function() {
            setTimeout(function() {
                document.getElementById('payuForm').submit();
            }, 1000);
        };
    </script>
</body>
</html>
