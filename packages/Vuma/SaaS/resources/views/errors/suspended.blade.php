<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store Suspended</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .box { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.1); text-align: center; max-width: 480px; }
        h1 { color: #e44; }
        p { color: #666; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Store Temporarily Unavailable</h1>
        <p>This store has been suspended. If you are the store owner, please log in to your admin panel to resolve any outstanding invoices.</p>
        <p><small>Store: {{ $tenant->name ?? '' }}</small></p>
    </div>
</body>
</html>
