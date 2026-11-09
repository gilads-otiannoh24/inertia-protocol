<?php

declare(strict_types=1);

namespace Inertia\Protocol\Ssr;

use Inertia\Protocol\PageObject;

interface SsrGatewayInterface
{
    /**
     * Dispatch the page object to the SSR server for server-side rendering.
     *
     * @param PageObject $page
     * @return SsrResponse|null Returns rendered SsrResponse or null on failure (fallback to client-side)
     */
    public function render(PageObject $page): ?SsrResponse;

    /**
     * Check if the SSR server process is running and healthy.
     */
    public function health(): bool;

    /**
     * Send a shutdown request to the SSR server.
     */
    public function shutdown(): bool;
}
