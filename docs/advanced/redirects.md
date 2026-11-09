# Redirects

Inertia uses standard HTTP redirects for post-action navigation, accompanied by special protocol conventions.

---

## 1. Automatic 303 Conversion (`InertiaProtocol::redirect`)

When an action executes via `PUT`, `PATCH`, `DELETE`, or `QUERY`, standard browser HTTP behavior would repeat the original HTTP method on the redirect target if answered with a `302 Found`.

`InertiaProtocol::redirect()` inspects the request method and converts `302 Found` to **`303 See Other`**, instructing the browser to follow the redirect with a safe `GET` request:

```php
use Inertia\Protocol\InertiaProtocol;

// In your controller update action:
return InertiaProtocol::redirect('/users', $request);
// Emits 303 See Other if $request was PUT or PATCH
```

---

## 2. External Redirects (`InertiaProtocol::location`)

If a form submission or link redirects outside the Inertia application (e.g. to a Stripe checkout page or OAuth provider), an XHR visit cannot follow it without CORS failures.

Use `InertiaProtocol::location()`:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::location('https://checkout.stripe.com/pay/cs_test_123');
```

* Emits a `409 Conflict`.
* Attaches `X-Inertia-Location: https://...`.
* The client performs a full `window.location` visit to the external destination.

---

## 3. Fragment Redirects (`InertiaProtocol::redirectWithFragment`)

When redirecting to a URL containing a hash fragment (e.g. `/posts#comment-42`), standard browser `fetch` and XHR drop the fragment.

Use `InertiaProtocol::redirectWithFragment()`:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::redirectWithFragment('https://example.com/posts/10#comment-42');
```

* Emits a `409 Conflict`.
* Attaches `X-Inertia-Redirect: https://example.com/posts/10#comment-42`.
* The client makes a new Inertia `GET` visit to that URL while preserving the target anchor.
