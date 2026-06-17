<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance — {{ config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: #fafafa; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 16px;
            color: #18181b;
        }
        .card {
            background: #fff; border-radius: 16px; padding: 48px 32px;
            max-width: 420px; width: 100%; text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,.06);
        }
        .code { font-size: 64px; font-weight: 700; color: #f59e0b; line-height: 1; }
        h1 { font-size: 22px; margin: 16px 0 8px; }
        p { font-size: 14px; color: #71717a; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="card">
        <div class="code">503</div>
        <h1>We'll be right back</h1>
        <p>{{ $message ?? 'QR Manager is temporarily down for maintenance. Please check back shortly.' }}</p>
    </div>
</body>
</html>
