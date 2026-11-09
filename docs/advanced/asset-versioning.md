# Asset Versioning

One common challenge in single-page apps is stale client assets after a new deployment. Inertia tracks asset versions to force a hard page refresh when assets change.

---

## Configuring the Asset Version

Set the version globally or dynamically:

```php
use Inertia\Protocol\InertiaProtocol;

// 1. Static version string
InertiaProtocol::version('v1.2.0');

// 2. Closure (e.g. hashing build manifest)
InertiaProtocol::version(function () {
    return md5_file(public_path('mix-manifest.json'));
});
```

You can also override the version per response:
```php
$response = InertiaProtocol::render('Users/Index')
    ->withVersion('custom_hash_123');
```

---

## Conflict Negotiation (409 Conflict)

Whenever an Inertia request is made, the client sends its current asset version in the `X-Inertia-Version` header.

### Specification Rules:
1. **GET Requests**:
   * If `X-Inertia-Version` does not match the server's current version, the engine immediately terminates prop resolution and returns a **`409 Conflict`** response decision.
   * Attaches `X-Inertia-Location: <current_url>` and `X-Inertia-Version: <new_version>`.
   * The client intercepts the 409 and executes `window.location.href = location`, triggering a clean browser reload with fresh CSS/JS bundles.
2. **Non-GET Requests** (`POST`, `PUT`, `PATCH`, `DELETE`):
   * Version mismatches are **NOT** triggered on mutation requests (to prevent interrupting in-flight form submissions).
   * The subsequent redirect to a `GET` route will trigger the 409 conflict appropriately.
