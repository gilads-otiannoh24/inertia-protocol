# Partial Reloads

Partial reloads allow the frontend to request a specific subset of props from the server on subsequent visits to the same page component.

---

## Request Headers

A partial reload is identified by the following HTTP request headers:

* **`X-Inertia-Partial-Component`**: The name of the JavaScript component currently mounted. Partial reloads only execute if this header matches the response component. If the destination component differs (e.g. session expired, redirected to `Auth/Login`), the engine automatically falls back to a **full visit**.
* **`X-Inertia-Partial-Data`**: Comma-separated list of props to include.
* **`X-Inertia-Partial-Except`**: Comma-separated list of props to exclude.
* **`X-Inertia-Reset`**: Comma-separated list of props whose client-side state should be replaced rather than merged.

---

## Dot-Notation Filtering

Inertia Protocol supports **dot-notated paths** in `X-Inertia-Partial-Data` and `X-Inertia-Partial-Except`:

```http
X-Inertia-Partial-Data: user.address.city
```

### Protocol Rules for Dot Paths:
1. **Plain Arrays**: Dot-notated paths are applied inside plain associative arrays. In the example above, the `user` array is filtered down to only `['address' => ['city' => ...]]`.
2. **Closures**: If `user` is resolved by a top-level closure, the protocol specification dictates that the closure is resolved in full, ensuring developer-defined closure contracts remain intact.
3. **Exclusion**: `X-Inertia-Partial-Except: auth.token` strips only `'token'` while returning all other keys in `'auth'`.
