# Inertia Wire Protocol Engine Documentation

Welcome to the comprehensive documentation for **Inertia Protocol Engine for PHP** (`gilads-otiannoh254/inertia-protocol`).

A framework-agnostic, zero-dependency PHP 8.2+ library implementing the complete [Inertia.js Wire Protocol specification](https://inertiajs.com) with native support for both **Version 2** and **Version 3** protocols.

---

## Documentation Structure

### 1. [Getting Started](getting-started/introduction.md)
* [Introduction](getting-started/introduction.md): Purpose, architecture, and philosophy.
* [Installation](getting-started/installation.md): Requirements and installation instructions.
* [Quick Start](getting-started/quick-start.md): Build your first response and handle decisions.

### 2. [Core Concepts](core-concepts/the-protocol.md)
* [The Protocol Lifecycle](core-concepts/the-protocol.md): Initial full-page visits vs. subsequent XHR visits.
* [The Page Object](core-concepts/page-object.md): Structure and schema of the page object payload.
* [Response Decisions](core-concepts/response-decision.md): The decoupled HTTP response decision abstraction.
* [Request Handling](core-concepts/request-handling.md): Working with `RequestInterface` and `NativeRequest`.

### 3. [Protocol Versions](protocol-versions/overview.md)
* [Version Overview](protocol-versions/overview.md): Side-by-side comparison between v2 and v3.
* [Version 3 Protocol](protocol-versions/version-3.md): Modern `<script>` embedding, live props, big integers, and 303 redirects.
* [Version 2 Protocol](protocol-versions/version-2.md): Legacy `<div data-page="...">` embedding and v2 payload rules.
* [Switching Versions](protocol-versions/switching-versions.md): Global and per-response protocol version configuration.

### 4. [Props Evaluation Model](props/overview.md)
* [Prop Model Overview](props/overview.md): How props are resolved across full visits and partial reloads.
* [Always Props](props/always-props.md): Props that are always evaluated (`InertiaProtocol::always`).
* [Optional & Lazy Props](props/optional-lazy-props.md): Props excluded on full visits (`InertiaProtocol::optional`, `InertiaProtocol::lazy`).
* [Deferred Props](props/deferred-props.md): Async background props, request grouping, and rescue support (`InertiaProtocol::defer`).
* [Once Props](props/once-props.md): Client-side cached props with expiry timestamps (`InertiaProtocol::once`).
* [Live Props](props/live-props.md): Broadcast event listeners and real-time refreshing (`InertiaProtocol::live`).
* [Merging Props](props/merging-props.md): Appending, prepending, deep merging, and matching keys (`InertiaProtocol::merge`).
* [Infinite Scroll](props/infinite-scroll.md): Cursor pagination metadata and bidirectional scrolling (`InertiaProtocol::scroll`).
* [Shared Props](props/shared-props.md): Global data and lazy evaluation on partial reloads (`InertiaProtocol::share`).

### 5. [Advanced Features](advanced/partial-reloads.md)
* [Partial Reloads](advanced/partial-reloads.md): Props filtering, dot-notation (`user.address`), and prop resetting.
* [Asset Versioning](advanced/asset-versioning.md): Tracking asset changes and handling 409 Conflict version mismatches.
* [Redirects](advanced/redirects.md): External visits (`location`), fragment redirects (`redirectWithFragment`), and automatic 303 conversion (`redirect`).
* [Validation Errors](advanced/validation-errors.md): Error bags and `X-Inertia-Error-Bag` scoping.
* [Reserved Props Protection](advanced/reserved-props.md): Protecting protocol integrity against accidental key collisions (`errors`, `flash`, `__inertia`).
* [History Encryption](advanced/history-encryption.md): History state encryption, history clearing, and fragment preservation.
* [Big Integers](advanced/big-integers.md): Preserving 64-bit PHP integers with `{"$bigint": "<digits>"}` markers.
* [Server-Side Rendering (SSR)](advanced/server-side-rendering.md): Official Node SSR server gateway (`HttpSsrGateway`).

### 6. [Building Framework Adapters](adapters/overview.md)
* [Adapter Overview](adapters/overview.md): Architectural guide to writing your own framework adapter.
* [PSR-7 / PSR-15](adapters/psr7.md): Integrating with Slim, Mezzio, or Nyholm.
* [Symfony](adapters/symfony.md): Integrating with Symfony HttpFoundation (`Request` / `Response`).
* [CodeIgniter 4](adapters/codeigniter.md): Integrating with CodeIgniter 4 controllers and filters.
* [Async Runtimes](adapters/async-runtimes.md): RoadRunner, Swoole, Workerman, and FrankenPHP.
