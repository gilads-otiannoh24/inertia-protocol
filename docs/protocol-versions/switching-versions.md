# Switching Protocol Versions

You can configure the active wire protocol version globally for your entire application or individually on specific responses.

---

## 1. Global Version Configuration

Use `InertiaProtocol::setProtocolVersion()` during your application's bootstrap phase (e.g. in a service provider or middleware):

```php
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\ProtocolVersion;

// Target Inertia v2 globally:
InertiaProtocol::setProtocolVersion(ProtocolVersion::V2);

// Or target Inertia v3 globally (the default):
InertiaProtocol::setProtocolVersion(ProtocolVersion::V3);
```

You can also pass strings or integers:
```php
InertiaProtocol::setProtocolVersion('2.0');
InertiaProtocol::setProtocolVersion('v2');
InertiaProtocol::setProtocolVersion(2);
```

---

## 2. Per-Response Overrides

To target a specific protocol version for a single response without affecting other routes:

```php
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\ProtocolVersion;

$response = InertiaProtocol::render('Legacy/Page', [
    'data' => $legacyData,
])->withProtocolVersion(ProtocolVersion::V2);

$decision = $response->toDecision($request);

echo $decision->toHtml('app');
// Outputs: <div id="app" data-page="..."></div>
```

---

## 3. Inspecting the Configured Version

```php
use Inertia\Protocol\InertiaProtocol;

$currentVersion = InertiaProtocol::getProtocolVersion();

if ($currentVersion->isV2()) {
    // Application is running in Version 2 mode
}

if ($currentVersion->isV3()) {
    // Application is running in Version 3 mode
}
```
