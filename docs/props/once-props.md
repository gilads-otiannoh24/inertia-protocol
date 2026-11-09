# Once Props

Once props are evaluated on their first visit, then remembered and cached by the client across subsequent page navigations.

---

## Why Once Props?

Applications frequently use data that is static or expensive to compute across multiple pages (e.g. lists of countries, tax rates, billing plans, or categories). Without once props, this data is repeatedly re-queried and sent on every navigation.

With once props:
1. On the first page visit, the server evaluates the prop and sends it.
2. The client caches the data.
3. On subsequent page visits, the client sends `X-Inertia-Except-Once-Props: countries`.
4. The server skips resolving the closure and leaves it out of the JSON response.
5. The client reuses its cached copy.

---

## Usage

Use `InertiaProtocol::once()`:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Checkout/Index', [
    // Cached indefinitely on the client
    'countries' => InertiaProtocol::once(fn() => Country::all()),

    // Cached with an expiration timestamp (in milliseconds)
    'exchangeRates' => InertiaProtocol::once(
        fn() => Currency::getRates(),
        expiresAt: (time() + 3600) * 1000 // Expires in 1 hour
    ),
]);
```

---

## Page Object Metadata

The page object announces once props under `onceProps`:

```json
{
  "component": "Checkout/Index",
  "props": { ... },
  "onceProps": {
    "countries": {
      "prop": "countries",
      "expiresAt": null
    },
    "exchangeRates": {
      "prop": "exchangeRates",
      "expiresAt": 1770000000000
    }
  }
}
```

---

## Force-Fresh & Partial Reloads

The server will automatically resolve a once prop even if the client has it cached in two scenarios:
1. **Explicit Partial Reload**: If a user refreshes or a partial reload specifically requests `'countries'` in `X-Inertia-Partial-Data`.
2. **Expired Timestamp**: When the current wall-clock time passes `expiresAt`, the client omits the key from `X-Inertia-Except-Once-Props`.
