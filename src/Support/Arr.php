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
        // BUG: We noticed that the value was having collittions with globally 
        // defined funtions. Because of this normal strins cannot be evaluated as callbacks
        if ($value instanceof Closure || is_callable($value) && !is_string($value)) {
            return $value(...$args);
        }

        return $value;
    }
}
