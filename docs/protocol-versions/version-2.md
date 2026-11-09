# Version 2 Protocol

The **Version 2** protocol specification corresponds to the classic Inertia wire format used prior to the release of Inertia v3.

---

## 1. HTML Responses with `data-page` on `<div>`

On initial full-page visits in Version 2, the JSON-encoded page object is stored directly in the `data-page` attribute of the root `<div>`:

```html
<div id="app" data-page='{"component":"Events/Index","props":{"errors":{},"events":[]},"url":"/events","version":"hash123","encryptHistory":false,"clearHistory":false}'></div>
```

The attribute is single-quoted (`data-page='...'`), and internal quotes and entities are escaped (`&apos;` / `htmlspecialchars`).

When the client-side app boots up, the Inertia v2 client reads `element.dataset.page`, which automatically unescapes the HTML entities into valid JSON.

---

## 2. Page Object Metadata Rules

In Version 2 mode, the engine strictly suppresses v3-only metadata fields to prevent passing unexpected properties to older clients:

* **Omitted in v2**:
  * `liveProps`
  * `preserveBigIntegers`
  * `rescuedProps`
  * `preserveFragment`
  * `sharedProps`
  * `flash`
* **Supported in v2**:
  * `deferredProps`
  * `mergeProps`, `prependProps`, `deepMergeProps`, `matchPropsOn`
  * `scrollProps`
  * `onceProps`
  * `encryptHistory`, `clearHistory`

---

## 3. Redirect Handling in Version 2

In Version 2, automatic conversion from `302 Found` to `303 See Other` is applied strictly to `PUT`, `PATCH`, and `DELETE` requests. Other request methods (such as `QUERY`) retain `302 Found`.
