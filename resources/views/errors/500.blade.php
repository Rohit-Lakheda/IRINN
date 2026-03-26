<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Internal Server Error | {{ config('app.name', 'Laravel') }}</title>
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
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        
        .error-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%);
        }
        
        .animation-container {
            max-width: 500px;
            width: 100%;
            margin-bottom: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-left: auto;
            margin-right: auto;
        }
        
        .error-animation {
            width: 100%;
            max-width: 400px;
            height: 300px;
            display: block;
            margin: 0 auto;
            vertical-align: middle;
        }
        
        /* Broken connection animation */
        .broken-line {
            stroke-dasharray: 10, 5;
            animation: dash 1s linear infinite;
        }
        
        @keyframes dash {
            to { stroke-dashoffset: -15; }
        }
        
        /* Warning triangle animation */
        .warning-triangle {
            animation: wobble 2s ease-in-out infinite;
            transform-origin: center;
        }
        
        @keyframes wobble {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-3deg); }
            75% { transform: rotate(3deg); }
        }
        
        /* Exclamation mark pulse */
        .exclamation {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
        }
        
        /* Floating particles */
        .particle {
            animation: float 3s ease-in-out infinite;
            opacity: 0.6;
        }
        
        .particle:nth-child(1) { animation-delay: 0s; }
        .particle:nth-child(2) { animation-delay: 0.5s; }
        .particle:nth-child(3) { animation-delay: 1s; }
        .particle:nth-child(4) { animation-delay: 1.5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.6; }
            50% { transform: translateY(-20px) translateX(10px); opacity: 0.3; }
        }
        
        .error-message {
            text-align: center;
            margin-top: 2rem;
        }
        
        .error-message h1 {
            font-size: 1.5rem;
            color: #C62828;
            margin-bottom: 0.75rem;
            font-weight: 500;
        }
        
        .error-message p {
            font-size: var(--font-size-base);
            color: #B71C1C;
            margin-bottom: 1.5rem;
        }
        
        .error-message .info-text {
            color: #D32F2F;
            font-weight: 500;
            display: block;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="animation-container">
            <svg class="error-animation" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid meet" style="margin: 0 auto; display: block;" xmlns="http://www.w3.org/2000/svg">
                <!-- Warning triangle -->
                <g class="warning-triangle">
                    <path d="M 200,50 L 100,250 L 300,250 Z" fill="#FF5252" stroke="#C62828" stroke-width="4"/>
                    <path d="M 200,50 L 100,250 L 300,250 Z" fill="#FFCDD2" opacity="0.3"/>
                </g>
                
                <!-- Exclamation mark -->
                <g class="exclamation">
                    <rect x="185" y="110" width="30" height="90" rx="15" fill="#FFF"/>
                    <circle cx="200" cy="225" r="14" fill="#FFF"/>
                </g>
                
                <!-- Broken connection lines -->
                <g class="broken-line-group">
                    <line class="broken-line" x1="50" y1="150" x2="130" y2="180" stroke="#C62828" stroke-width="3" stroke-linecap="round"/>
                    <line class="broken-line" x1="350" y1="150" x2="270" y2="180" stroke="#C62828" stroke-width="3" stroke-linecap="round"/>
                    <line class="broken-line" x1="50" y1="200" x2="130" y2="220" stroke="#C62828" stroke-width="3" stroke-linecap="round"/>
                    <line class="broken-line" x1="350" y1="200" x2="270" y2="220" stroke="#C62828" stroke-width="3" stroke-linecap="round"/>
                </g>
                
                <!-- Floating particles -->
                <circle class="particle" cx="80" cy="100" r="4" fill="#FF5252"/>
                <circle class="particle" cx="320" cy="120" r="5" fill="#E53935"/>
                <circle class="particle" cx="90" cy="220" r="3" fill="#EF5350"/>
                <circle class="particle" cx="310" cy="240" r="4" fill="#FF5252"/>
            </svg>
        </div>
        
        <div class="error-message">
            <h1>Temporary Issue</h1>
            <p>We're experiencing a temporary connection issue.</p>
            <span class="info-text">Please try again in a few moments.</span>
        </div>
    </div>
</body>
</html>

