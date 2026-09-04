<?php

declare(strict_types=1);

namespace Inertia\Protocol\Contracts;

interface PropInterface
{
    /**
     * Resolves the underlying value or executes the callback.
     */
    public function resolve(): mixed;
}
