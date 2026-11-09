# Quick Start

This guide demonstrates how to evaluate an Inertia request and emit the appropriate response in plain PHP or bespoke architectures.

---

## 1. Creating a Response

Use the canonical entrypoint `InertiaProtocol` to define your page component and its props:

```php
use Inertia\Protocol\InertiaProtocol;

$response = InertiaProtocol::render('Users/Index', [
    'users'    => fn() => UserModel::paginate(),
    'appName'  => InertiaProtocol::always('My App'),
    'metrics'  => InertiaProtocol::defer(fn() => Analytics::getMonthlyMetrics(), group: 'analytics'),
]);
```

---

## 2. Evaluating the Response

Pass the incoming request to the `toDecision()` method. You can use the built-in `NativeRequest::fromGlobals()` for standard PHP environments:

```php
use Inertia\Protocol\Support\NativeRequest;

$request = NativeRequest::fromGlobals();
$decision = $response->toDecision($request);
```

---

## 3. Emitting the HTTP Output

The resulting `ResponseDecision` tells your application exactly what status code, headers, and body to send:

```php
if ($decision->isJson()) {
    // 1. Subsequent Inertia visit (XHR): Send JSON response
    http_response_code($decision->statusCode); // 200
    foreach ($decision->headers as $name => $value) {
        header("{$name}: {$value}");
    }
    echo json_encode($decision->content);
    exit;
}

if ($decision->isConflict()) {
    // 2. Control response: Version mismatch, External redirect, or Fragment redirect
    http_response_code($decision->statusCode); // 409
    foreach ($decision->headers as $name => $value) {
        header("{$name}: {$value}");
    }
    exit;
}

// 3. Initial full-page visit: Render your application's root HTML layout
http_response_code($decision->statusCode); // 200
foreach ($decision->headers as $name => $value) {
    header("{$name}: {$value}");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My App</title>
    <link rel="stylesheet" href="/css/app.css">
    <script src="/js/app.js" defer></script>
</head>
<body>
    <?php echo $decision->toHtml('app'); ?>
</body>
</html>
```

In Version 3 (the default), `$decision->toHtml('app')` automatically outputs:
```html
<script data-page="app" type="application/json">{"component":"Users\/Index",...}</script>
<div id="app"></div>
```
