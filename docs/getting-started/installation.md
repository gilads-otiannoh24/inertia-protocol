# Installation

## Requirements

* **PHP**: `>= 8.2`
* **JSON Extension**: `ext-json` (enabled by default in modern PHP builds)
* **Zero External Dependencies**: Does not require any external Composer packages for runtime execution.

---

## Installing via Composer

Install the package using Composer:

```bash
composer require gilads-otiannoh254/inertia-protocol
```

---

## Autoloading

The package adheres to **PSR-4** autoloading under the `Inertia\Protocol\` namespace:

```json
{
    "autoload": {
        "psr-4": {
            "Inertia\\Protocol\\": "src/"
        },
        "files": [
            "src/Support/helpers.php"
        ]
    }
}
```

The helper functions (`inertia_render`, `inertia_location`, `inertia_redirect`) are automatically registered in the global namespace via `src/Support/helpers.php`.
