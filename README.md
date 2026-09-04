# Inertia.js Wire Protocol Engine for PHP

A framework-agnostic, zero-dependency PHP 8.2+ library implementing the complete [Inertia.js Wire Protocol specification](https://inertiajs.com).

Designed to power any PHP framework adapter (CodeIgniter 4, Symfony, Slim, RoadRunner, Swoole, Workerman, Laravel, or bespoke architectures) with 100% spec-compliant Inertia responses.

---

## Features

- 🚀 **Zero Dependencies**: Pure modern PHP 8.2+ with strict typing.
- 📦 **Complete Page Object Schema**: Strict compliance with `component`, `props` (with always-present `errors: {}`), `url`, `version`, and conditional metadata.
- 🔄 **Advanced Prop Evaluation Model**:
  - `Inertia::always(...)`: Always resolved across full visits and partial reloads.
  - `Inertia::lazy(...)`: Skipped on full visits, resolved only on partial reloads.
  - `Inertia::defer(...)`: Grouped deferred props for follow-up loading with automatic rescue support.
  - `Inertia::once(...)`: Evaluated once and cached client-side with optional expiration timestamps.
  - `Inertia::merge(...)`, `Inertia::prepend(...)`, `Inertia::deepMerge(...)`: Client-side prop merging with `matchPropsOn` identification keys.
  - `Inertia::scroll(...)`: Infinite scroll pagination cursor and metadata.
- ⚡ **Automated Protocol Handlers**:
  - **Asset Versioning**: Automatically returns `409 Conflict` with `X-Inertia-Location` and `X-Inertia-Version` on GET mismatches.
  - **External Visits**: `Inertia::location($url)` generates `409 Conflict` with `X-Inertia-Location`.
  - **Fragment Redirects**: `Inertia::redirectWithFragment($url)` generates `409 Conflict` with `X-Inertia-Redirect`.
  - **Precognition**: Returns `204 No Content` with `Precognition: true` and `Vary: Precognition`.
  - **Partial Reload Filtering**: Respects `X-Inertia-Partial-Data`, `X-Inertia-Partial-Except`, `X-Inertia-Reset`, and `X-Inertia-Except-Once-Props`.

---

## Installation

```bash
composer require gilads-otiannoh254/inertia-protocol
```

---

## Usage

### 1. Basic Response Evaluation

```php
use Inertia\Protocol\Inertia;
use Inertia\Protocol\Support\NativeRequest;

// 1. Build an Inertia response
$response = Inertia::render('Users/Index', [
    'users' => fn() => UserModel::paginate(),
    'siteName' => Inertia::always('My App'),
    'analytics' => Inertia::defer(fn() => Analytics::get(), group: 'analytics'),
]);

// 2. Evaluate against incoming request
$decision = $response->toDecision(NativeRequest::fromGlobals());

if ($decision->isJson()) {
    // Send HTTP JSON response (status: 200, headers: decision->headers, body: decision->content)
    http_response_code($decision->statusCode);
    foreach ($decision->headers as $name => $value) {
        header("{$name}: {$value}");
    }
    echo json_encode($decision->content);
} else {
    // Render full HTML root template with embedded page object
    echo view('app', ['page' => $decision->pageObject->toArray()]);
}
```

### 2. External & Fragment Redirects

```php
// External URL visit
$decision = Inertia::location('https://external-payment.com/checkout');

// URL Fragment Redirect
$decision = Inertia::redirectWithFragment('https://my-app.com/posts#comment-42');
```

### 3. Precognition Validation

```php
$decision = Inertia::precognitionSuccess();
// Emits 204 No Content with Precognition-Success headers
```

---

## License

MIT License. See [LICENSE](LICENSE) for details.
