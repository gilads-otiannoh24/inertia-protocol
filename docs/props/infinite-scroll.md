# Infinite Scroll

Infinite scroll builds on merge props to provide built-in cursor and page pagination metadata.

---

## Usage

Use `InertiaProtocol::scroll()`:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Feed/Index', [
    'posts' => InertiaProtocol::scroll(
        data: $paginator->items(),
        pageName: 'page',
        previousPage: $paginator->previousPage(),
        nextPage: $paginator->nextPage(),
        currentPage: $paginator->currentPage()
    ),
]);
```

---

## Page Object Metadata

The page object automatically registers both the merge target (`<prop>.data`) and the pagination cursor state under `scrollProps`:

```json
{
  "component": "Feed/Index",
  "props": {
    "posts": {
      "data": [
        { "id": 1, "title": "Post 1" },
        { "id": 2, "title": "Post 2" }
      ]
    }
  },
  "mergeProps": ["posts.data"],
  "scrollProps": {
    "posts": {
      "pageName": "page",
      "previousPage": null,
      "nextPage": 2,
      "currentPage": 1,
      "reset": false
    }
  }
}
```

---

## Directional Scrolling (`Merge Intent`)

Inertia supports bidirectional infinite scrolling (e.g. scrolling down to load next posts vs scrolling up to load previous chat history).

The client sends the `X-Inertia-Infinite-Scroll-Merge-Intent` header:
* `append`: Data is placed in `mergeProps` (appended to the bottom).
* `prepend`: Data is placed in `prependProps` (prepended to the top).

The engine inspects this header automatically and routes `<prop>.data` to the correct merge array.

---

## Resetting Infinite Scroll

When the client sends `X-Inertia-Reset: posts`:
1. The engine omits `mergeProps: ["posts.data"]`.
2. Sets `scrollProps.posts.reset: true`.
3. The frontend `<InfiniteScroll>` component resets its scroll position and replaces the dataset.
