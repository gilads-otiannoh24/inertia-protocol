<?php

declare(strict_types=1);

namespace Inertia\Protocol\Support;

use Closure;
use Inertia\Protocol\Contracts\PropInterface;

class Arr
{
    /**
     * JavaScript's safe integer boundaries: -(2^53 - 1) to (2^53 - 1).
     */
    public const JS_MAX_SAFE_INTEGER = 9007199254740991;
    public const JS_MIN_SAFE_INTEGER = -9007199254740991;

    public static function accessible(mixed $value): bool
    {
        return is_array($value) || $value instanceof \ArrayAccess;
    }

    public static function exists(mixed $array, int|string $key): bool
    {
        if ($array instanceof \ArrayAccess) {
            return $array->offsetExists($key);
        }

        if (is_array($array)) {
            return array_key_exists($key, $array);
        }

        return false;
    }

    public static function get(mixed $array, mixed $key, mixed $default = null): mixed
    {
        if (!static::accessible($array)) {
            return static::value($default);
        }

        if (null === $key) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (!str_contains((string) $key, '.')) {
            return $array[$key] ?? static::value($default);
        }

        foreach (explode('.', (string) $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return static::value($default);
            }
        }

        return $array;
    }

    public static function set(mixed &$array, mixed $key, mixed $value): mixed
    {
        if (null === $key) {
            return $array = $value;
        }

        $keys = explode('.', (string) $key);

        while (count($keys) > 1) {
            $k = array_shift($keys);

            if (!isset($array[$k]) || !is_array($array[$k])) {
                $array[$k] = [];
            }

            $array = &$array[$k];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function only(array $array, array|string $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Resolves a single value, executing it if callable or closure.
     */
    public static function value(mixed $value, mixed ...$args): mixed
    {
        if ($value instanceof Closure || (is_callable($value) && !is_string($value))) {
            return $value(...$args);
        }

        return $value;
    }

    /**
     * Recursively resolves values: closures, PropInterface objects, and nested arrays.
     */
    public static function resolve(mixed $value, mixed ...$args): mixed
    {
        while ($value instanceof PropInterface || $value instanceof Closure || (is_callable($value) && !is_string($value))) {
            if ($value instanceof PropInterface) {
                $value = $value->resolve();
            } elseif ($value instanceof Closure || (is_callable($value) && !is_string($value))) {
                $value = $value(...$args);
            }
        }

        if (is_array($value)) {
            $resolved = [];
            foreach ($value as $k => $v) {
                $resolved[$k] = self::resolve($v, ...$args);
            }
            return $resolved;
        }

        return $value;
    }

    /**
     * Wrap integers exceeding JavaScript's safe integer range as {"$bigint": "<digits>"}.
     */
    public static function wrapBigIntegers(mixed $value): mixed
    {
        if (is_int($value)) {
            if ($value > self::JS_MAX_SAFE_INTEGER || $value < self::JS_MIN_SAFE_INTEGER) {
                return ['$bigint' => (string) $value];
            }
            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::wrapBigIntegers($v);
            }
            return $value;
        }

        return $value;
    }

    /**
     * Check if a top-level prop key is targeted by partial reload paths (exact or dot prefix).
     *
     * @param string $key
     * @param list<string> $partialData
     */
    public static function matchesPartial(string $key, array $partialData): bool
    {
        if (empty($partialData)) {
            return true;
        }

        if (in_array($key, $partialData, true)) {
            return true;
        }

        $prefix = "{$key}.";
        foreach ($partialData as $path) {
            if (str_starts_with($path, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter an array using dot-notated paths for partial reloads.
     *
     * @param array<string|int, mixed> $data
     * @param string $keyPrefix
     * @param list<string> $onlyPaths
     * @param list<string> $exceptPaths
     * @return array<string|int, mixed>
     */
    public static function filterByDotPaths(
        array $data,
        string $keyPrefix,
        array $onlyPaths = [],
        array $exceptPaths = []
    ): array {
        $prefix = "{$keyPrefix}.";
        $relevantOnly = [];
        $hasExactParentInOnly = in_array($keyPrefix, $onlyPaths, true);

        if (!empty($onlyPaths)) {
            foreach ($onlyPaths as $path) {
                if (str_starts_with($path, $prefix)) {
                    $relevantOnly[] = substr($path, strlen($prefix));
                }
            }
        }

        $relevantExcept = [];
        if (!empty($exceptPaths)) {
            foreach ($exceptPaths as $path) {
                if (str_starts_with($path, $prefix)) {
                    $relevantExcept[] = substr($path, strlen($prefix));
                }
            }
        }

        if (empty($relevantOnly) && empty($relevantExcept)) {
            return $data;
        }

        $filtered = [];

        foreach ($data as $subKey => $subVal) {
            $subKeyStr = (string) $subKey;

            if (!empty($relevantOnly) && !$hasExactParentInOnly) {
                $subMatches = in_array($subKeyStr, $relevantOnly, true);
                if (!$subMatches) {
                    $nestedPrefix = "{$subKeyStr}.";
                    foreach ($relevantOnly as $oPath) {
                        if (str_starts_with($oPath, $nestedPrefix)) {
                            $subMatches = true;
                            break;
                        }
                    }
                }
                if (!$subMatches) {
                    continue;
                }
            }

            if (!empty($relevantExcept)) {
                if (in_array($subKeyStr, $relevantExcept, true)) {
                    continue;
                }
            }

            if (is_array($subVal)) {
                $subVal = self::filterByDotPaths($subVal, "{$keyPrefix}.{$subKeyStr}", $onlyPaths, $exceptPaths);
            }

            $filtered[$subKey] = $subVal;
        }

        return $filtered;
    }
}
