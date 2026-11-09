# Optional & Lazy Props

Optional props (also known as Lazy props) are **completely omitted from the initial full-page visit**. They are only executed and returned when explicitly requested by a partial reload.

---

## Why Optional Props?

Some page data is expensive to compute or rarely viewed immediately upon page mount (e.g. secondary tabs, slide-over modals, or detailed analytics).

By marking data as optional, the initial page render remains fast, and the frontend can fetch the data later on-demand:

```javascript
// Client-side on-demand fetch:
router.reload({ only: ['analytics'] });
```

---

## Usage

Use `InertiaProtocol::optional()` (or its synonym `InertiaProtocol::lazy()`):

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Users/Show', [
    'user' => $user,

    // Excluded on full visits; evaluated only when 'analytics' is requested
    'analytics' => InertiaProtocol::optional(fn() => Analytics::forUser($user->id)),

    // Using lazy() synonym
    'permissions' => InertiaProtocol::lazy(fn() => $user->allPermissions()),
]);
```

---

## Behavior Differences

* **Full Visit**: The prop is skipped. It does not appear in `props` and is not announced in any metadata.
* **Partial Reload**: If `X-Inertia-Partial-Data` includes `analytics`, the closure executes and the resolved data is returned in `props`.
