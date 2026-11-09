# Deferred Props

Deferred props allow you to split a slow or heavy controller action into two stages:
1. Fast data loads immediately on the initial render.
2. Heavy data is skipped initially and automatically fetched by the client via an asynchronous follow-up request right after the page mounts.

---

## Usage

Use `InertiaProtocol::defer()` to wrap your closure:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Dashboard', [
    // Fast data loaded immediately
    'user' => Auth::user(),

    // Slow data deferred into the default background group
    'recentActivity' => InertiaProtocol::defer(fn() => Activity::getLatest(), group: 'default'),

    // Grouping related deferred props together
    'revenueChart'  => InertiaProtocol::defer(fn() => Metrics::getRevenue(), group: 'charts'),
    'userGrowth'    => InertiaProtocol::defer(fn() => Metrics::getUserGrowth(), group: 'charts'),
]);
```

---

## Page Object Announcement

On a full page visit, deferred props are omitted from `props` and announced under `deferredProps`, grouped by group name:

```json
{
  "component": "Dashboard",
  "props": {
    "user": { "name": "Ian" },
    "errors": {}
  },
  "url": "/dashboard",
  "version": "hash123",
  "deferredProps": {
    "default": ["recentActivity"],
    "charts": ["revenueChart", "userGrowth"]
  }
}
```

The Inertia client reads `deferredProps` and immediately issues a parallel partial reload for each group:
`X-Inertia-Partial-Data: revenueChart,userGrowth`

---

## Error Handling & Rescued Props

If a deferred closure throws an unhandled exception during follow-up loading (e.g. an external API is down or database timeout occurs), it would normally crash the request.

Inertia supports **rescuing** deferred props by passing `rescue: true`:

```php
'externalFeed' => InertiaProtocol::defer(function () {
    return ExternalApi::fetchFeed();
}, rescue: true)
```

When an exception occurs:
1. The response remains a successful `200 OK`.
2. The failing prop is omitted from `props`.
3. In Version 3, the prop key is listed under `rescuedProps: ['externalFeed']`.
4. The client's `<Deferred>` component renders its `#rescue` fallback slot.
