# PSR-7 & PSR-15 Adapter Guide

This guide shows how to build an adapter for PSR-7 / PSR-15 applications (e.g. **Slim 4**, **Mezzio**, or **Nyholm**).

---

## 1. The Request Bridge

```php
namespace App\Inertia\Adapters;

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

---

## 2. Converting `ResponseDecision` to PSR-7 Response

```php
use Inertia\Protocol\ResponseDecision;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class Psr7ResponseConverter
{
    public function __construct(
        protected ResponseFactoryInterface $responseFactory,
        protected mixed $viewRenderer
    ) {}

    public function convert(ResponseDecision $decision): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($decision->statusCode);

        foreach ($decision->headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if ($decision->isJson()) {
            $response->getBody()->write(json_encode($decision->content));
            return $response;
        }

        if ($decision->isConflict() || $decision->isPrecognition()) {
            return $response;
        }

        // Render HTML view
        $html = $this->viewRenderer->render('app.php', [
            'page' => $decision->pageObject,
            'pageHtml' => $decision->toHtml('app'),
        ]);

        $response->getBody()->write($html);
        return $response;
    }
}
```
