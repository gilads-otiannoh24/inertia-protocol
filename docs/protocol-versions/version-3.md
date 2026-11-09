# Version 3 Protocol

The **Version 3** protocol specification represents the current standard of Inertia.js.

---

## 1. HTML Responses with Script Tags

Inertia v3 modernizes how the initial page object is sent from the server. Instead of embedding JSON inside an HTML attribute on a `<div>`, it embeds a `<script>` tag:

```html
<script data-page="app" type="application/json">{"component":"Events\/Index",...}</script>
<div id="app"></div>
```

### Script Tag Escaping Rules
The v3 specification mandates that the serialized page object **must not contain a literal `</script>` sequence**, as this closes the script tag prematurely and breaks the page.

`Inertia Protocol` enforces this by serializing with:
```php
JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
```
* Forward slashes (`/`) become `\/`.
* Less-than (`<`) becomes `\u003C`.
* HTML entity encoding is strictly avoided because browsers do not decode HTML entities inside `<script>` elements.

---

## 2. Live Props (`liveProps`)

Live props allow page properties to auto-refresh whenever a broadcast event arrives:

```json
{
  "component": "Orders/Show",
  "props": { ... },
  "url": "/orders/1",
  "version": "hash123",
  "liveProps": {
    "order": {
      "listeners": [
        {
          "channel": { "name": "orders.1", "type": "private" },
          "events": ["App\\Events\\OrderUpdated"]
        }
      ],
      "throttle": 5000
    }
  }
}
```

---

## 3. JavaScript BigInt Preservation

PHP integers on 64-bit platforms can reach `9223372036854775807`, whereas JavaScript's safe integer limit is `9007199254740991` (`2^53 - 1`).

Under v3, enabling `preserveBigIntegers` instructs the engine to transform integers outside JavaScript's safe range into `{"$bigint": "<digits>"}` and marks the page object with `"preserveBigIntegers": true`:

```php
InertiaProtocol::preserveBigIntegers();
```

---

## 4. Automatic 303 Redirects

Inertia v3 converts a `302 Found` response into a `303 See Other` for `PUT`, `PATCH`, `DELETE`, and **`QUERY`** requests:

```php
$decision = InertiaProtocol::redirect('/dashboard', $request);
// If method is QUERY, status code is 303
```
