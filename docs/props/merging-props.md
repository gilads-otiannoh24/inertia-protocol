# Merging Props

A merge prop tells the client to combine new incoming data with existing data already on the page rather than overwriting it.

---

## Merge Types

Inertia supports three merge strategies:

### 1. Appending (`InertiaProtocol::merge`)
New items are appended to the end of the existing client-side array:
```php
'posts' => InertiaProtocol::merge(fn() => Post::paginate(10))
```

### 2. Prepending (`InertiaProtocol::prepend`)
New items are prepended to the beginning of the existing client-side array (commonly used for real-time notification feeds or reverse chat):
```php
'notifications' => InertiaProtocol::prepend(fn() => Notification::getUnread())
```

### 3. Deep Merging (`InertiaProtocol::deepMerge`)
Recursively merges nested associative arrays and objects:
```php
'settings' => InertiaProtocol::deepMerge(fn() => ['theme' => ['color' => 'dark']])
```

---

## Matching Items (`matchPropsOn`)

When merging lists of items, simply appending can cause duplicate entries if an item was updated rather than created.

You can specify the unique identification key using the `$matchOn` parameter:

```php
'posts' => InertiaProtocol::merge(fn() => Post::getLatest(), matchOn: 'id')
```

The page object registers this under `matchPropsOn`:
```json
{
  "mergeProps": ["posts"],
  "matchPropsOn": ["posts.id"]
}
```

When the client merges, it looks up the item by `item.id`. If an item with that ID already exists in the client array, it updates the existing item in place; otherwise, it appends it.

---

## Resetting Props

When navigating to a fresh search query or changing filters, you may want to reset merged props back to page 1 instead of continuing to merge.

When the client sends `X-Inertia-Reset: posts`, the engine resolves the prop and **omits** the key from `mergeProps`, instructing the client to replace the array entirely.
