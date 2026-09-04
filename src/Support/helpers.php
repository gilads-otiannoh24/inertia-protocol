<?php

declare(strict_types=1);

use Inertia\Protocol\Inertia;
use Inertia\Protocol\InertiaResponse;

if (!function_exists('inertia_render')) {
    /**
     * Create an Inertia response builder.
     */
    function inertia_render(string $component, array $props = []): InertiaResponse
    {
        return Inertia::render($component, $props);
    }
}
