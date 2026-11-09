# History Encryption

Inertia supports encrypting the browser's history state to prevent sensitive page data from being read from the browser's history cache (e.g. on shared computers or after logout).

---

## Encrypting History

Use `$response->encryptHistory()`:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Bank/Statement', [
    'account' => $bankAccountData,
])->encryptHistory();
```

* Emits `"encryptHistory": true` in the Page Object.
* Instructs the client adapter to encrypt the history state before calling `history.pushState`.

---

## Clearing History

When a user logs out or completes a sensitive transaction, you can instruct the browser to clear all previously encrypted history states:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Auth/Login', [
    'loggedOut' => true,
])->clearHistory();
```

* Emits `"clearHistory": true` in the Page Object.
* Instructs the client adapter to purge all saved encrypted history entries.

---

## Preserving Fragments

To retain the current URL's hash fragment after a server-side redirect:

```php
return InertiaProtocol::render('Posts/Show')
    ->preserveFragment();
```

* Emits `"preserveFragment": true` in the Page Object.
