# Live Props (Version 3)

Live props allow specific page properties to automatically refresh whenever a real-time broadcast event arrives on the client.

---

## Why Live Props?

Instead of manually configuring WebSocket listeners on the frontend and dispatching manual router reloads, Live Props declare the real-time event mapping directly alongside the prop in your PHP controller.

The Inertia client establishes the subscription, throttles updates, and triggers a partial reload to refresh the prop when an event occurs.

---

## Usage

Use `InertiaProtocol::live()`:

```php
use Inertia\Protocol\InertiaProtocol;

return InertiaProtocol::render('Orders/Show', [
    'order' => InertiaProtocol::live(fn() => Order::find($id))
        ->listen('orders.' . $id, ['App\\Events\\OrderUpdated'], type: 'private')
        ->throttle(5000), // Max 1 refresh every 5 seconds
]);
```

### Channel Types
* `'private'` (default)
* `'public'`
* `'presence'`

### Listening to Multiple Channels / Events
```php
$prop = InertiaProtocol::live($data)
    ->listen('orders.' . $id, ['OrderUpdated', 'PaymentCaptured'], type: 'private')
    ->listen('announcements', ['SystemAlert'], type: 'public');
```

---

## Page Object Metadata

Live props emit metadata in the `liveProps` object:

```json
{
  "component": "Orders/Show",
  "props": { ... },
  "liveProps": {
    "order": {
      "listeners": [
        {
          "channel": { "name": "orders.10", "type": "private" },
          "events": ["App\\Events\\OrderUpdated"]
        }
      ],
      "throttle": 5000
    }
  }
}
```

---

## Skipping the Broadcaster Connection

When the client performs an action that triggers a server broadcast, it sends the `X-Socket-Id` request header. Your broadcasting service (e.g. Pusher, Soketi, Reverb) can exclude the broadcaster's socket ID so the user who initiated the action does not receive redundant events.
