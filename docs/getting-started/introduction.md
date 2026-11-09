# Introduction

## What is Inertia Protocol?

**`Inertia Protocol Engine for PHP`** is a standalone, framework-agnostic implementation of the [Inertia.js Wire Protocol](https://inertiajs.com).

When using Inertia.js, the client application expects the server to communicate using specific HTTP headers, status codes, query conventions, and a precise JSON page payload structure. Historically, PHP developers had to rely on framework-specific adapters (such as `inertia-laravel`) or re-implement protocol parsing manually.

`inertia-protocol` extracts the entire protocol logic into a **pure PHP 8.2+ engine with zero third-party dependencies**.

---

## Why Use This Package?

### 1. Framework Agnostic
Whether you are writing an adapter for **Symfony**, **CodeIgniter 4**, **Slim**, **Laravel**, or asynchronous runtimes like **RoadRunner**, **Swoole**, or **Workerman**, this engine does the heavy lifting. Your adapter only needs to translate incoming requests into a simple `RequestInterface` and convert the engine's `ResponseDecision` into your framework's native HTTP response object.

### 2. Dual Protocol Versioning (v2 & v3)
Inertia v3 introduced significant changes to how initial HTML responses are mounted (shifting from `data-page` attributes on the root `div` to secure `<script data-page="app" type="application/json">` tags), alongside new features like Live Props, JavaScript BigInt preservation, and 303 redirects for `QUERY` requests. 

This package supports both **Version 2** and **Version 3** out-of-the-box, allowing adapters to seamlessly support both modern and legacy frontend clients.

### 3. Complete Specification Compliance
* Page Object schema with conditional metadata omission.
* Full prop evaluation model: `always`, `optional`/`lazy`, `defer` (with automatic rescue support), `once` (with expiration), `live`, `merge`, `prepend`, `deepMerge`, and `scroll`.
* Composable prop categories (e.g. `defer()->merge()`, `scroll()->defer()`).
* Dynamic dot-notation filtering for nested props (`only: ['user.address']`).
* Asset version negotiation (409 Conflict with `X-Inertia-Location` on GET requests).
* Built-in Server-Side Rendering (SSR) Node gateway.

---

## Architectural Philosophy

* **Decoupled from HTTP Kernels**: The engine never reads `$_SERVER` directly unless explicitly asked via `NativeRequest::fromGlobals()`. It operates against a minimal [`RequestInterface`](../core-concepts/request-handling.md).
* **Decoupled from HTTP Responses**: Instead of emitting headers or outputting JSON, the engine returns a [`ResponseDecision`](../core-concepts/response-decision.md). The caller inspects `isJson()`, `isHtml()`, `isConflict()`, `isRedirect()`, or `isPrecognition()` and dispatches the native response.
* **Strict Typing & Zero Dependencies**: 100% PHP 8.2+ strict typing with no external runtime vendor packages required.
