# Shared Props

Shared props are global properties available on **every page** in your application (e.g. authenticated user, application name, navigation menus, flash messages).

---

## Defining Shared Props

Register shared data globally using `InertiaProtocol::share()`:

```php
use Inertia\Protocol\InertiaProtocol;

// 1. Share a single key
InertiaProtocol::share('appName', 'Acme App');

// 2. Share multiple keys at once
InertiaProtocol::share([
    'currentLocale' => 'en',
    'auth' => [
        'user' => fn() => CurrentUser::get(),
    ],
]);
```

---

## Lazy Evaluation on Partial Reloads

Inertia Protocol ensures that shared closures are **evaluated lazily**.

If a shared prop is defined as a closure (e.g. `fn() => Database::queryUser()`), and the client performs a partial reload requesting only a specific page prop:
* The shared closure **is NOT executed**, saving database queries and memory.
* It is only invoked if the prop is requested or during a full page visit.

---

## Managing Shared Props

```php
use Inertia\Protocol\InertiaProtocol;

// Retrieve all shared props
$allShared = InertiaProtocol::getShared();

// Retrieve a specific key with fallback
$user = InertiaProtocol::getShared('auth.user', default: null);

// Clear all shared props (useful in long-running processes like RoadRunner/Swoole)
InertiaProtocol::flushShared();
```
