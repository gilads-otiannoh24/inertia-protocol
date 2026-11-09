# Always Props

Always props are evaluated on **every single request**, including full page visits and partial reloads.

---

## Why Always Props?

During a partial reload, the client requests specific props using `X-Inertia-Partial-Data` (or excludes them using `X-Inertia-Partial-Except`). Unrequested props are normally omitted.

However, certain application data must **never** be omitted. Common examples include:
* Current authenticated user state.
* Global notification toasts / flash alerts.
* Validation errors (`errors`).

---

## Usage

Use `InertiaProtocol::always()` to wrap any value or closure:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Dashboard', [
    // Standard prop (skipped on partial reload unless requested)
    'stats' => fn() => Stats::calculate(),

    // Always prop (always evaluated and included in every response)
    'auth' => InertiaProtocol::always(fn() => [
        'user' => Auth::user(),
        'notifications_count' => Auth::unreadCount(),
    ]),

    // Always prop with static scalar value
    'site_status' => InertiaProtocol::always('online'),
]);
```

---

## Key Characteristics

1. **Ignores Filters**: Always props ignore `X-Inertia-Partial-Data` and `X-Inertia-Partial-Except`. Even if the client explicitly excludes the prop in `except`, the server will still evaluate and return it.
2. **No Metadata Emitted**: Always props carry no special metadata in the page object; they appear directly inside `props`.
