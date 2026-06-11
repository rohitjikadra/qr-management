<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>@yield('title') — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #f4f4f5; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 16px;
        }
        .card {
            background: #fff; border-radius: 16px; padding: 40px 32px;
            max-width: 420px; width: 100%; text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 20px; color: #18181b; margin-bottom: 8px; }
        p { font-size: 14px; color: #71717a; line-height: 1.6; }
        .report { margin-top: 24px; }
        .report a { font-size: 12px; color: #a1a1aa; text-decoration: underline; }
        .brand { margin-top: 16px; font-size: 12px; color: #d4d4d8; }
        form { margin-top: 16px; text-align: left; }
        label { font-size: 13px; color: #3f3f46; display: block; margin-bottom: 6px; }
        textarea {
            width: 100%; border: 1px solid #e4e4e7; border-radius: 8px;
            padding: 10px; font-size: 14px; font-family: inherit; min-height: 90px;
        }
        button {
            margin-top: 12px; width: 100%; background: #18181b; color: #fff;
            border: 0; border-radius: 8px; padding: 10px; font-size: 14px; cursor: pointer;
        }
        .success { color: #16a34a; font-weight: 500; margin-top: 12px; }
        .error { color: #dc2626; font-size: 12px; margin-top: 6px; }
    </style>
</head>
<body>
    <div class="card">
        @yield('content')
        <div class="brand">Powered by {{ config('app.name') }}</div>
    </div>
</body>
</html>
