# Reserved Props Protection

To protect the integrity of the Inertia wire protocol and prevent subtle client-side bugs, `inertia-protocol` guards against user-defined props accidentally overwriting keys required by the frontend client.

If an incoming prop attempts to overwrite a reserved key, the engine throws an explicit `Inertia\Protocol\Exceptions\ReservedPropException`.

---

## Reserved Keys by Protocol Version

| Protocol Version | Reserved Prop Keys | Reason & Frontend Purpose |
| :--- | :--- | :--- |
| **Version 2 (`2.0`)** | `errors`<br>`flash` | • `errors`: Required by the Inertia v2 client to be an error object (`{}`) for form helpers (`form.errors`).<br>• `flash`: Reserved for session flash messaging. Use `withFlash()` instead. |
| **Version 3 (`3.0`)** | `errors`<br>`flash`<br>`__inertia` | • `errors`: Required by Inertia v3 for validation bags.<br>• `flash`: Reserved in v3 for the first-class page object `flash` payload and `inertia:flash` event.<br>• `__inertia`: Reserved for protocol internal event markers (`__inertia.props`). |

---

## The `ReservedPropException`

When an incoming prop uses a reserved key:

```php
use Inertia\Protocol\InertiaProtocol;

// Throws ReservedPropException!
InertiaProtocol::render('Users/Create', [
    'errors' => ['email' => 'Invalid email address.'],
]);
```

### Exception Details:
```php
try {
    InertiaProtocol::render('Users/Create', ['errors' => ...]);
} catch (\Inertia\Protocol\Exceptions\ReservedPropException $e) {
    echo $e->key;     // "errors"
    echo $e->version; // "3.0"
    echo $e->getMessage();
    // Cannot use reserved prop key "errors". This key is required and reserved by the Inertia.js (Protocol 3.0) frontend for internal operation. Use withErrors() on the response to attach validation errors.
}
```

---

## How to Correctly Pass Errors and Flash Data

Instead of defining `errors` or `flash` as raw props, use the dedicated methods provided on `InertiaResponse`:

### 1. Attaching Validation Errors (`withErrors`)
```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Users/Create', [
    'name' => 'Ian',
])->withErrors([
    'email' => 'The email field is required.',
]);
```

### 2. Attaching Flash Data (`withFlash`)
```php
return InertiaProtocol::render('Dashboard', [
    'stats' => $stats,
])->withFlash([
    'success' => 'Profile updated successfully!',
]);
```

---

## Configuring Reserved Props Protection

You can customize, extend, or disable protection if needed:

```php
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\Support\ReservedProps;

// 1. Temporarily disable protection globally:
InertiaProtocol::protectReservedProps(false);

// 2. Re-enable protection:
InertiaProtocol::protectReservedProps(true);

// 3. Register custom reserved keys (e.g. if your frontend template reserves 'page'):
ReservedProps::addReservedKey('page');

// 4. Check if a key is reserved:
if (ReservedProps::isReserved('errors')) {
    // ...
}
```
