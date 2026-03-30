<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $message ?? 'Processing…' }}</title>
    <meta http-equiv="refresh" content="{{ ! empty($autoRefresh) && ! empty($redirectUrl) ? '2;url=' . $redirectUrl : '' }}">
    <style>
        body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f4f4f5; }
        .box { background: #fff; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); max-width: 420px; text-align: center; }
        h1 { font-size: 1.125rem; margin: 0 0 .5rem; }
        p { color: #52525b; margin: 0; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{{ $message ?? 'Processing…' }}</h1>
        @isset($submessage)
            <p>{{ $submessage }}</p>
        @endisset
    </div>
</body>
</html>
