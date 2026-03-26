<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Session Expired | {{ config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}?v={{ time() }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}?v={{ time() }}">
    
    <!-- Local Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    
    <!-- Custom Theme CSS -->
    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    
    <!-- Styles / Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
  /* ===== Reset and base ===== */
  * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
  }
  body {
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    background: #e8f9f0;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* ===== Card ===== */
  .card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0, 128, 64, 0.15);
    text-align: center;
    padding: 40px 30px;
    width: 400px;
    max-width: 90vw;
    border: 2px solid #c5f2d2;
    animation: fadeIn 0.8s ease;
  }

  /* ===== Icon (CSS only) ===== */
  .icon {
    width: 90px;
    height: 90px;
    margin: 0 auto 20px;
    position: relative;
    border-radius: 16px;
    background: #ecfdf3;
    border: 2px solid #b4f0c8;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: float 3s ease-in-out infinite;
  }

  .face {
    width: 50px;
    height: 32px;
    background: #b7f3c9;
    border-radius: 6px;
    position: relative;
  }
  .face::before,
  .face::after {
    content: "";
    position: absolute;
    background: #00994f;
    border-radius: 50%;
    width: 6px;
    height: 6px;
    top: 8px;
  }
  .face::before { left: 10px; }
  .face::after { right: 10px; }

  .smile {
    position: absolute;
    left: 50%;
    bottom: 6px;
    transform: translateX(-50%);
    width: 18px;
    height: 8px;
    border-bottom: 2px solid #00994f;
    border-radius: 0 0 10px 10px;
  }

  /* Refresh arrow */
  .refresh {
    position: absolute;
    top: -12px;
    right: -12px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid #00b359;
    color: #00b359;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: rotate 3s linear infinite;
  }

  /* ===== Text ===== */
  h1 {
    font-size: 1.5rem;
    color: #1a3b2f;
    margin-bottom: 10px;
  }
  p {
    font-size: 0.95rem;
    color: #4a665a;
    margin-bottom: 24px;
    line-height: 1.4;
  }

  /* ===== Button ===== */
  .btn {
    display: inline-block;
    padding: 10px 28px;
    background: #00b359;
    color: white;
    font-weight: 600;
    border-radius: 24px;
    text-decoration: none;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(0,179,89,0.25);
  }
  .btn:hover {
    background: #00a652;
    box-shadow: 0 6px 16px rgba(0,179,89,0.3);
    transform: translateY(-2px);
  }

  /* ===== Animations ===== */
  @keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
  }

  @keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }

</style>
</head>
<body>

  <div class="card">
    <div class="icon">
      <div class="face">
        <div class="smile"></div>
      </div>
      <div class="refresh">⟳</div>
    </div>
    <h1>Your session has expired</h1>
    <p>Please refresh the page.
  </div>

</body>
</html>
