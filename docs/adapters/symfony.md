# Symfony Adapter Guide

This guide shows how to build an Inertia adapter for **Symfony** using `HttpFoundation`.

---

## 1. Symfony Request Bridge

```php
namespace App\Inertia\Adapters;

use Inertia\Protocol\Contracts\RequestInterface;
use Symfony\Component\HttpFoundation\Request;

class SymfonyRequestBridge implements RequestInterface
{
    public function __construct(protected Request $request) {}

    public function getHeaderLine(string $name): string
    {
        return $this->request->headers->get($name, '');
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->headers->has($name);
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getUrl(): string
    {
        return $this->request->getRequestUri();
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->request->query->get($name, $default);
    }
}
```

---

## 2. Converting `ResponseDecision` to Symfony Response

```php
use Inertia\Protocol\ResponseDecision;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class SymfonyResponseConverter
{
    public function __construct(protected Environment $twig) {}

    public function toResponse(ResponseDecision $decision): Response
    {
        if ($decision->isJson()) {
            return new JsonResponse($decision->content, $decision->statusCode, $decision->headers);
        }

        if ($decision->isConflict() || $decision->isPrecognition()) {
            return new Response('', $decision->statusCode, $decision->headers);
        }

        $html = $this->twig->render('app.html.twig', [
            'pageHtml' => $decision->toHtml('app'),
        ]);

        return new Response($html, $decision->statusCode, $decision->headers);
    }
}
```
