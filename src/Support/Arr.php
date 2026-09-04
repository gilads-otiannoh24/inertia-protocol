<?php

declare(strict_types=1);

namespace Inertia\Protocol\Support;

use Closure;

final class Arr
{
    /**
     * Resolves a value, executing it if callable or closure.
     */
    public static function value(mixed $value, mixed ...$args): mixed
    {
        if ($value instanceof Closure || is_callable($value)) {
            return $value(...$args);
        }

        return $value;
    }
}
