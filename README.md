# Inertia Wire Protocol Engine for PHP

[![Latest Version](https://img.shields.io/badge/version-v0.1.0-blue.svg)](https://packagist.org/packages/gilads-otiannoh254/inertia-protocol)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A framework-agnostic, zero-dependency PHP 8.2+ engine implementing the complete [Inertia.js Wire Protocol specification](https://inertiajs.com) with native support for both **Version 2** and **Version 3** protocols.

Designed to power any PHP framework adapter (CodeIgniter 4, Symfony, Slim, RoadRunner, Swoole, Workerman, Laravel, or bespoke architectures) with 100% spec-compliant Inertia responses.

---

## Highlights

* **Zero Dependencies**: Pure PHP 8.2+ with strict typing.
* **Multi-Version Protocol**: Seamlessly target **Inertia v3** (`<script data-page>` tags, live props, BigInt preservation) or **Inertia v2** (`<div data-page>` attributes).
* **Advanced Prop Model**: Composable props (`always`, `optional`/`lazy`, `defer`, `once`, `live`, `merge`, `scroll`).
* **Decoupled Architecture**: Evaluates requests into clean `ResponseDecision` objects without binding to specific HTTP kernels.
* **SSR Ready**: Built-in `HttpSsrGateway` for official Node SSR server rendering and health checks.

---

## Installation

```bash
composer require gilads-otiannoh254/inertia-protocol
```

---

## Quick Example

```php
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\Support\NativeRequest;

// 1. Build an Inertia response with composable props
$response = InertiaProtocol::render('Users/Index', [
    'users'    => fn() => UserModel::paginate(),
    'appName'  => InertiaProtocol::always('My App'),
    'metrics'  => InertiaProtocol::defer(fn() => Analytics::getSummary(), group: 'analytics'),
    'feed'     => InertiaProtocol::defer(fn() => Feed::getLatest())->merge('id'),
]);

// 2. Evaluate against incoming request
$decision = $response->toDecision(NativeRequest::fromGlobals());

if ($decision->isJson()) {
    // XHR Navigation: Send 200 OK JSON response
    http_response_code($decision->statusCode);
    foreach ($decision->headers as $name => $value) {
        header("{$name}: {$value}");
    }
    echo json_encode($decision->content);
} else {
    // Initial Visit: Render HTML root template
    echo '<!DOCTYPE html><html><head><title>My App</title></head><body>';
    echo $decision->toHtml('app'); // Emits v3 script + root div (or v2 div)
    echo '</body></html>';
}
```

---

## Documentation

Comprehensive in-depth documentation is available in the [`docs/`](docs/index.md) directory:

| Topic | Description |
| :--- | :--- |
| [**Getting Started**](docs/getting-started/introduction.md) | [Introduction](docs/getting-started/introduction.md), [Installation](docs/getting-started/installation.md), and [Quick Start Guide](docs/getting-started/quick-start.md) |
| [**Core Concepts**](docs/core-concepts/the-protocol.md) | [The Protocol Lifecycle](docs/core-concepts/the-protocol.md), [Page Object Schema](docs/core-concepts/page-object.md), [Response Decisions](docs/core-concepts/response-decision.md), [Request Interface](docs/core-concepts/request-handling.md) |
| [**Protocol Versions**](docs/protocol-versions/overview.md) | [Version Overview](docs/protocol-versions/overview.md), [Version 3 Spec](docs/protocol-versions/version-3.md), [Version 2 Spec](docs/protocol-versions/version-2.md), [Version Switching](docs/protocol-versions/switching-versions.md) |
| [**Props Evaluation**](docs/props/overview.md) | [Evaluation Model](docs/props/overview.md), [Always](docs/props/always-props.md), [Optional/Lazy](docs/props/optional-lazy-props.md), [Deferred](docs/props/deferred-props.md), [Once](docs/props/once-props.md), [Live](docs/props/live-props.md), [Merging](docs/props/merging-props.md), [Scroll](docs/props/infinite-scroll.md), [Shared](docs/props/shared-props.md) |
| [**Advanced Features**](docs/advanced/partial-reloads.md) | [Partial Reloads](docs/advanced/partial-reloads.md), [Asset Versioning (409)](docs/advanced/asset-versioning.md), [Redirects (303)](docs/advanced/redirects.md), [Reserved Props](docs/advanced/reserved-props.md), [Error Bags](docs/advanced/validation-errors.md), [History Encryption](docs/advanced/history-encryption.md), [Big Integers](docs/advanced/big-integers.md), [SSR Gateway](docs/advanced/server-side-rendering.md) |
| [**Framework Adapters**](docs/adapters/overview.md) | [Building Adapters](docs/adapters/overview.md), [PSR-7 / Slim](docs/adapters/psr7.md), [Symfony](docs/adapters/symfony.md), [CodeIgniter 4](docs/adapters/codeigniter.md), [RoadRunner / Swoole](docs/adapters/async-runtimes.md) |

---

## License

MIT License. See [LICENSE](LICENSE) for details.
