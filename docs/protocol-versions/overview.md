# Protocol Versions Overview

Inertia.js has evolved across multiple iterations. **Inertia Protocol Engine for PHP** supports both **Version 2** and **Version 3** specifications out-of-the-box.

---

## Comparison Table

| Protocol Specification | Version 2 (`ProtocolVersion::V2`) | Version 3 (`ProtocolVersion::V3` - Default) |
| :--- | :--- | :--- |
| **Initial HTML Response** | `<div id="app" data-page="..."></div>` | `<script data-page="app" type="application/json">` + `<div id="app"></div>` |
| **Script Tag Safety** | Not applicable (stored in HTML attribute) | Encodes forward slashes (`\/`) and `<` (`\u003C`) via `JSON_HEX_TAG` |
| **Live Props** | ❌ Not supported | ✅ Supported via `liveProps` metadata |
| **BigInt Support** | ❌ Not supported | ✅ Supported via `preserveBigIntegers` and `{"$bigint": "..."}` |
| **Deferred Props Rescue** | ❌ Not in v2 spec | ✅ Supported via `rescuedProps` metadata |
| **Preserve Fragment** | ❌ Not in v2 spec | ✅ Supported via `preserveFragment` metadata |
| **Shared Props Metadata** | ❌ Not in v2 spec | ✅ Emitted in `sharedProps` for instant visits |
| **Flash Data Metadata** | ❌ Handled via session reflash | ✅ Exposed in page object `flash` payload |
| **303 Redirect Methods** | `PUT`, `PATCH`, `DELETE` | `PUT`, `PATCH`, `DELETE`, **`QUERY`** |
| **Infinite Scroll Merge Intent** | Query / header convention | Standardized `X-Inertia-Infinite-Scroll-Merge-Intent: prepend\|append` |

---

## Why Support Both Versions?

1. **Legacy Compatibility**: Many production applications continue using `@inertiajs/vue3@^1.0` or `@inertiajs/react@^1.2` (Inertia v2 wire protocol).
2. **Seamless Upgrade Path**: Framework adapters can allow developers to switch protocol versions on a per-response or global basis without altering their controller code.
3. **Prevention of Client Parsing Failures**:
   * The **Inertia v3 client explicitly no longer reads the `data-page` attribute on the root `<div>`**.
   * The **Inertia v2 client expects the `data-page` attribute on the root `<div>`** and does not read `<script data-page="app">`.
