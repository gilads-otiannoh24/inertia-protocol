# The Page Object

The `PageObject` (`Inertia\Protocol\PageObject`) is the central data contract between the server and the Inertia frontend client.

---

## Schema Overview

Every Inertia response contains four mandatory properties, plus optional conditional metadata fields that are omitted when empty:

```json
{
  "component": "Events/Index",
  "props": {
    "events": [],
    "errors": {}
  },
  "url": "/events",
  "version": "c32b8e4965f418ad16eaebba1d4e960f"
}
```

---

## Property Specifications

| Property | Type | Supported Versions | Description |
| :--- | :--- | :--- | :--- |
| **`component`** | `string` | v2, v3 | Name of the frontend JavaScript component (e.g. `'Users/Show'`). |
| **`props`** | `object` | v2, v3 | Resolved page properties. Always contains an `errors` object (`{}`) unless overridden. |
| **`url`** | `string` | v2, v3 | Current page URL path and query string. |
| **`version`** | `string` | v2, v3 | Server's current asset version identifier. |
| **`encryptHistory`** | `boolean` | v2, v3 | Emitted when `true`. Instructs client to encrypt history state. |
| **`clearHistory`** | `boolean` | v2, v3 | Emitted when `true`. Instructs client to clear saved encrypted history. |
| **`deferredProps`** | `object` | v2, v3 | Map of request groups to deferred prop names (e.g. `{"default": ["comments"]}`). |
| **`mergeProps`** | `array` | v2, v3 | Array of prop keys to append during navigation. |
| **`prependProps`** | `array` | v2, v3 | Array of prop keys to prepend during navigation. |
| **`deepMergeProps`** | `array` | v2, v3 | Array of prop keys to deep-merge during navigation. |
| **`matchPropsOn`** | `array` | v2, v3 | Fields used to identify items when merging arrays (e.g. `['posts.id']`). |
| **`scrollProps`** | `object` | v2, v3 | Pagination state for infinite scroll components. |
| **`onceProps`** | `object` | v2, v3 | Map of cached props and their expiration timestamps in milliseconds. |
| **`liveProps`** | `object` | **v3 only** | Broadcast channels, events, and throttle settings for live refreshing props. |
| **`preserveBigIntegers`** | `boolean` | **v3 only** | Emitted when `true`. Indicates that large integers are wrapped as `{"$bigint": "<digits>"}`. |
| **`rescuedProps`** | `array` | **v3 only** | Keys of deferred props that threw exceptions and were rescued. |
| **`preserveFragment`** | `boolean` | **v3 only** | Emitted when `true`. Keeps URL fragment across redirects. |
| **`sharedProps`** | `array` | **v3 only** | Top-level keys of shared props carried over during instant visits. |
| **`flash`** | `object` | **v3 only** | Flash session data exposed via the `inertia:flash` client event. |

---

## PHP Methods on `PageObject`

```php
use Inertia\Protocol\PageObject;

$page = new PageObject(
    component: 'Users/Index',
    props: ['users' => []],
    url: '/users',
    version: '1.0.0'
);

// 1. Convert to spec-compliant array (conditional metadata omitted when empty)
$array = $page->toArray();

// 2. Serialize to JSON
$json = $page->toJson();

// 3. Render HTML according to configured ProtocolVersion
echo $page->toHtml('app');

// 4. Render explicit HTML formats
echo $page->toHtmlScript('app'); // v3 <script data-page="app" type="application/json">...</script>
echo $page->toHtmlTag('app');    // <div id="app"></div>
echo $page->toHtmlDiv('app');    // v2 <div id="app" data-page="..."></div>
```
