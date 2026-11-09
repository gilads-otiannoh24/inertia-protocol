# Asynchronous Runtimes Guide

Because `inertia-protocol` is stateless and zero-dependency, it is naturally compatible with long-running, asynchronous PHP execution environments like **RoadRunner**, **Swoole**, **Workerman**, and **FrankenPHP**.

---

## 1. Preventing State Bleed Between Worker Requests

In standard PHP-FPM, memory is discarded at the end of each HTTP request. In worker-based environments, static memory persists across requests.

To avoid shared props leaking across requests in long-running processes:

```php
use Inertia\Protocol\InertiaProtocol;

// In your worker request loop:
try {
    // 1. Flush any shared state from prior request
    InertiaProtocol::flushShared();

    // 2. Share per-request context (e.g. current tenant or user)
    InertiaProtocol::share('auth', fn() => $requestUser);

    // 3. Process the response
    $decision = $inertiaResponse->toDecision($requestBridge);

    // 4. Send response to client
    $worker->respond($decision);
} finally {
    // 5. Always clean up
    InertiaProtocol::flushShared();
}
```

---

## 2. RoadRunner PSR-7 Worker Example

```php
use Inertia\Protocol\InertiaProtocol;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

$worker = new PSR7Worker(
    Worker::create(),
    new Psr17Factory(),
    new Psr17Factory(),
    new Psr17Factory()
);

while ($req = $worker->waitRequest()) {
    try {
        InertiaProtocol::flushShared();

        $requestBridge = new Psr7RequestBridge($req);
        $decision = InertiaProtocol::render('Home', ['time' => time()])
            ->toDecision($requestBridge);

        $response = new Response($decision->statusCode, $decision->headers);
        if ($decision->isJson()) {
            $response->getBody()->write(json_encode($decision->content));
        } else {
            $response->getBody()->write("<html><body>{$decision->toHtml()}</body></html>");
        }

        $worker->respond($response);
    } catch (\Throwable $e) {
        $worker->getWorker()->error((string)$e);
    }
}
```
