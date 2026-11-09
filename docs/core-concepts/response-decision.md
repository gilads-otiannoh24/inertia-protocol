# Response Decision

The `ResponseDecision` (`Inertia\Protocol\ResponseDecision`) object represents the engine's evaluated verdict on how the HTTP server should respond to an incoming request.

---

## Why ResponseDecision?

Inertia requires different responses depending on the nature of the request:
* A full-page browser navigation needs an **HTML document**.
* An internal Inertia visit needs a **JSON page object**.
* A version mismatch needs a **409 Conflict**.
* A form submission needs a **302 Found** or **303 See Other**.
* A precognition validation pass needs a **204 No Content**.

By returning a `ResponseDecision`, this library remains 100% framework-agnostic. Your framework adapter does not have to reimplement protocol logic—it merely inspects the decision and maps it to the framework's native response object.

---

## Decision Properties

```php
namespace Inertia\Protocol;

class ResponseDecision
{
    public string $type;          // One of TYPE_* constants
    public int $statusCode;       // HTTP status code (200, 302, 303, 409, 204, etc.)
    public array $headers;        // Associative array of HTTP headers to send
    public ?PageObject $pageObject; // Evaluated PageObject (if a page response)
    public mixed $content;        // Array, string, or null
}
```

---

## Decision Types

| Constant | Description |
| :--- | :--- |
| `TYPE_PAGE_JSON` | An internal Inertia request (XHR) returning the JSON page object. |
| `TYPE_PAGE_HTML` | An initial browser navigation returning the root HTML template. |
| `TYPE_CONFLICT_VERSION` | Asset version mismatch on a `GET` request (status 409). |
| `TYPE_CONFLICT_LOCATION` | External location visit (status 409). |
| `TYPE_CONFLICT_REDIRECT` | URL fragment redirect (status 409). |
| `TYPE_REDIRECT` | Standard redirect (status 302 or 303). |
| `TYPE_PRECOGNITION_SUCCESS` | Precognition validation passed (status 204). |

---

## Helper Methods

```php
$decision = $response->toDecision($request);

if ($decision->isJson()) {
    // 200 OK JSON response
}

if ($decision->isHtml()) {
    // 200 OK HTML response
    echo $decision->toHtml('app');
}

if ($decision->isConflict()) {
    // 409 Conflict (version mismatch, external location, or fragment redirect)
}

if ($decision->isRedirect()) {
    // 302 or 303 Redirect
}

if ($decision->isPrecognition()) {
    // 204 No Content Precognition response
}
```
