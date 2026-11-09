# Request Handling

To decouple the protocol engine from PHP globals (`$_SERVER`, `$_GET`, etc.), all request evaluation is performed against the `RequestInterface` contract.

---

## `RequestInterface` Contract

```php
namespace Inertia\Protocol\Contracts;

interface RequestInterface
{
    /**
     * Get a single header line by name (case-insensitive).
     */
    public function getHeaderLine(string $name): string;

    /**
     * Check if a header exists.
     */
    public function hasHeader(string $name): bool;

    /**
     * Get the HTTP request method (e.g. GET, POST, PUT, DELETE, QUERY).
     */
    public function getMethod(): string;

    /**
     * Get the request URL / path with query string and fragment if available.
     */
    public function getUrl(): string;

    /**
     * Get a query parameter value.
     */
    public function getQueryParam(string $name, mixed $default = null): mixed;
}
```

---

## Built-In `NativeRequest`

For standard PHP scripts or frameworks that populate superglobals (`$_SERVER`, `$_GET`), the engine provides `NativeRequest`:

```php
use Inertia\Protocol\Support\NativeRequest;

// 1. Automatically inspect $_SERVER and $_GET
$request = NativeRequest::fromGlobals();

// 2. Or manually construct for unit testing or custom environments
$customRequest = new NativeRequest(
    headers: [
        'x-inertia'                   => 'true',
        'x-inertia-partial-component' => 'Users/Index',
        'x-inertia-partial-data'      => 'users',
    ],
    method: 'GET',
    url: '/users?page=2',
    queryParams: ['page' => 2]
);
```

---

## Implementing `RequestInterface` for Your Framework

Creating an adapter for any framework requires only a few lines to implement `RequestInterface`:

### Example: PSR-7 ServerRequest Bridge
```php
use Inertia\Protocol\Contracts\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class Psr7RequestBridge implements RequestInterface
{
    public function __construct(protected ServerRequestInterface $request) {}

    public function getHeaderLine(string $name): string
    {
        return $this->request->getHeaderLine($name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getUrl(): string
    {
        return (string) $this->request->getUri();
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        $params = $this->request->getQueryParams();
        return $params[$name] ?? $default;
    }
}
```

See the [Adapters Documentation](../adapters/overview.md) for complete implementations for Symfony, CodeIgniter 4, and async runtimes.
