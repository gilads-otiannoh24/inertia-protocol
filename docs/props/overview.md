# Prop Evaluation Model

Every prop passed to `InertiaProtocol::render()` belongs to one or more categories. The category decides:
1. Whether the server resolves the prop for a full page visit or partial reload.
2. Which metadata fields describe it in the page object.

---

## Prop Evaluation Matrix

### Full Visit

| Prop Category | Resolved on Full Visit? | Metadata Emitted |
| :--- | :--- | :--- |
| **Regular** | Yes | None |
| **Always** (`always`) | Yes | None |
| **Optional / Lazy** (`optional`, `lazy`) | No (skipped) | None |
| **Deferred** (`defer`) | No (skipped) | `deferredProps` |
| **Merge / Prepend / DeepMerge** | Yes | `mergeProps`, `prependProps`, `deepMergeProps`, `matchPropsOn` |
| **Once** (`once`) | Yes (unless already cached by client) | `onceProps` |
| **Scroll** (`scroll`) | Yes | `scrollProps`, `mergeProps` (or `prependProps`) |
| **Live** (`live`) | Yes | `liveProps` (v3 only) |

### Partial Reload

| Prop Category | Resolved on Partial Reload? | Metadata Emitted |
| :--- | :--- | :--- |
| **Regular** | Only when selected by `only` or `except` | None |
| **Always** (`always`) | Yes (ignores `only` and `except`) | None |
| **Optional / Lazy** (`optional`, `lazy`) | Only when selected by `only` or `except` | None |
| **Deferred** (`defer`) | Only when selected by `only` or `except` | `rescuedProps` (when rescued) |
| **Merge / Prepend / DeepMerge** | When resolved | `mergeProps`, `prependProps`, `deepMergeProps`, `matchPropsOn` |
| **Once** (`once`) | When requested (ignores except-once) | `onceProps` |
| **Scroll** (`scroll`) | When resolved | `scrollProps`, `mergeProps` (or `prependProps`) |
| **Live** (`live`) | When resolved | `liveProps` (v3 only) |

---

## Composing Prop Categories

A prop may belong to several categories at once. For example, a deferred prop can also be mergeable:

```php
// Composing Defer with Merge
$prop = InertiaProtocol::defer(fn() => PostModel::getLatest())->merge('id');

// Composing Scroll with Defer
$prop = InertiaProtocol::scroll(fn() => PostModel::paginate())->defer();

// Composing Lazy with Once
$prop = InertiaProtocol::optional(fn() => Category::all())->once();
```

When composed:
* On full visits, `defer` prevents the query from executing and registers the group in `deferredProps`.
* On partial reload, the query runs, and the `mergeProps` / `scrollProps` metadata is added to the response.
