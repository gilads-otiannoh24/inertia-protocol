# Server-Side Rendering (SSR)

Inertia client components are rendered on the client by default. For SEO or performance optimization, you can render initial HTML on the server using an Inertia Node SSR server.

---

## SSR Contract Overview

1. The PHP backend renders the `PageObject`.
2. The PHP backend sends a `POST` request with the JSON-encoded `PageObject` to the SSR server: `http://127.0.0.1:13714/render`.
3. The SSR server executes the JavaScript bundle via Node and responds with:
   ```json
   {
     "head": ["<title>My Page</title>", "<meta name=\"description\" content=\"...\">"],
     "body": "<script data-page=\"app\" type=\"application/json\">{...}</script><div data-server-rendered=\"true\" id=\"app\">...</div>"
   }
   ```
4. The PHP backend embeds `$ssr->head()` into `<head>` and `$ssr->body()` into `<body>`.

---

## Using `HttpSsrGateway`

Inertia Protocol includes a zero-dependency, pure PHP SSR gateway:

```php
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\Ssr\HttpSsrGateway;

$decision = InertiaProtocol::render('Events/Show', $props)->toDecision($request);

if ($decision->isHtml()) {
    $gateway = new HttpSsrGateway(
        url: 'http://127.0.0.1:13714',
        timeout: 1.0,          // 1-second timeout
        throwOnError: false    // Graceful fallback to client rendering
    );

    $ssr = $gateway->render($decision->pageObject);

    if ($ssr !== null) {
        // Render server-rendered HTML
        echo '<!DOCTYPE html><html><head>' . $ssr->head() . '</head><body>' . $ssr->body() . '</body></html>';
    } else {
        // Fallback to client-side hydration
        echo '<!DOCTYPE html><html><head><title>My App</title></head><body>' . $decision->toHtml('app') . '</body></html>';
    }
}
```

---

## Vite Dev Server Endpoint

During development with Vite, Vite registers a dev SSR endpoint at `/__inertia_ssr`:

```php
$gateway = new HttpSsrGateway('http://127.0.0.1:5173/__inertia_ssr');
```

---

## Health Check and Shutdown

```php
$gateway = new HttpSsrGateway('http://127.0.0.1:13714');

// Check if Node SSR process is healthy
if (!$gateway->health()) {
    // Process not running
}

// Gracefully shut down the SSR server (e.g. during deployments)
$gateway->shutdown();
```
