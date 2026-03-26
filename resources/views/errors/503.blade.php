<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Service Unavailable | {{ config('app.name', 'Laravel') }}</title>
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
  /* ===== Base styles ===== */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    background: #e7f4f1;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  /* ===== Card ===== */
  .card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 150, 100, 0.15);
    text-align: center;
    padding: 40px 30px;
    width: 420px;
    max-width: 92vw;
    border: 2px solid #c4f2e0;
    animation: fadeIn 0.8s ease;
  }

  /* ===== Gear animation section ===== */
  .gears {
    position: relative;
    width: 100px;
    height: 100px;
    margin: 0 auto 20px;
  }

  .gear {
    position: absolute;
    border-radius: 50%;
    border: 8px solid #00b37a;
    width: 60px;
    height: 60px;
    top: 20px;
    left: 20px;
    background: #dffcf1;
    animation: spin 4s linear infinite;
  }

  .gear::before,
  .gear::after {
    content: "";
    position: absolute;
    background: #00b37a;
    border-radius: 2px;
  }

  /* teeth */
  .gear::before {
    width: 12px;
    height: 4px;
    top: -8px;
    left: 24px;
    box-shadow: 0 64px #00b37a, -32px 32px #00b37a, 32px 32px #00b37a;
  }

  .gear::after {
    width: 4px;
    height: 12px;
    left: 28px;
    top: -8px;
    box-shadow: 0 64px #00b37a, -32px 32px #00b37a, 32px 32px #00b37a;
    transform: rotate(45deg);
  }

  /* second gear */
  .gear.small {
    width: 40px;
    height: 40px;
    top: 60px;
    left: 60px;
    border-width: 6px;
    background: #d7fcef;
    animation: spinReverse 3s linear infinite;
  }

  /* ===== Text ===== */
  h1 {
    font-size: 1.5rem;
    color: #134d3c;
    margin-bottom: 10px;
  }

  p {
    font-size: 0.95rem;
    color: #44685b;
    margin-bottom: 24px;
    line-height: 1.5;
  }

  /* ===== Button ===== */
  .btn {
    display: inline-block;
    padding: 10px 28px;
    background: #00b37a;
    color: white;
    font-weight: 600;
    border-radius: 24px;
    text-decoration: none;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(0, 179, 122, 0.25);
  }

  .btn:hover {
    background: #009e6b;
    box-shadow: 0 6px 16px rgba(0, 179, 122, 0.35);
    transform: translateY(-2px);
  }

  /* ===== Animations ===== */
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }

  @keyframes spinReverse {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(-360deg); }
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
  }

</style>
</head>
<body>

  <div class="card">
    <div class="gears">
      <div class="gear"></div>
      <div class="gear small"></div>
    </div>
    <h1>503 - Server Under Maintenance</h1>
    <p>Our servers are currently upgrading to serve you better.<br>
    Please check back in a few minutes.</p>
  </div>

</body>
</html>
