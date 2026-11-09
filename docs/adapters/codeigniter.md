# CodeIgniter 4 Adapter Guide

This guide shows how to build an Inertia adapter for **CodeIgniter 4**.

---

## 1. CodeIgniter 4 Request Bridge

```php
namespace App\Libraries\Inertia;

use CodeIgniter\HTTP\IncomingRequest;
use Inertia\Protocol\Contracts\RequestInterface;

class CodeIgniterRequestBridge implements RequestInterface
{
    public function __construct(protected IncomingRequest $request) {}

    public function getHeaderLine(string $name): string
    {
        $header = $this->request->header($name);
        return $header ? $header->getValueLine() : '';
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    public function getMethod(): string
    {
        return strtoupper($this->request->getMethod());
    }

    public function getUrl(): string
    {
        return (string) $this->request->getUri();
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->request->getGet($name) ?? $default;
    }
}
```

---

## 2. Converting `ResponseDecision` to CodeIgniter Response

```php
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Inertia\Protocol\ResponseDecision;

class CodeIgniterResponseConverter
{
    public function toResponse(ResponseDecision $decision): ResponseInterface
    {
        $response = Services::response();
        $response->setStatusCode($decision->statusCode);

        foreach ($decision->headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        if ($decision->isJson()) {
            return $response->setJSON($decision->content);
        }

        if ($decision->isConflict() || $decision->isPrecognition()) {
            return $response;
        }

        $html = view('app', [
            'pageHtml' => $decision->toHtml('app'),
        ]);

        return $response->setBody($html);
    }
}
```
