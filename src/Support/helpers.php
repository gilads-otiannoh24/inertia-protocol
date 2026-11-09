<?php

declare(strict_types=1);

use Inertia\Protocol\Contracts\RequestInterface;
use Inertia\Protocol\InertiaProtocol;
use Inertia\Protocol\InertiaResponse;
use Inertia\Protocol\ProtocolVersion;
use Inertia\Protocol\ResponseDecision;

if (!function_exists('inertia_render')) {
    /**
     * Create an Inertia response builder.
     */
    function inertia_render(string $component, array $props = []): InertiaResponse
    {
        return InertiaProtocol::render($component, $props);
    }
}

if (!function_exists('inertia_location')) {
    /**
     * Generate an external location visit decision (409 Conflict).
     */
    function inertia_location(string $url): ResponseDecision
    {
        return InertiaProtocol::location($url);
    }
}

if (!function_exists('inertia_redirect')) {
    /**
     * Generate an HTTP redirect decision (302 or 303 for non-GET requests).
     */
    function inertia_redirect(
        string $url,
        ?RequestInterface $request = null,
        int $defaultStatusCode = 302,
        array $headers = [],
        ProtocolVersion|string|null $protocolVersion = null
    ): ResponseDecision {
        return InertiaProtocol::redirect($url, $request, $defaultStatusCode, $headers, $protocolVersion);
    }
}
