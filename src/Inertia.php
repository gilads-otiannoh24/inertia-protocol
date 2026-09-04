<?php

declare(strict_types=1);

namespace Inertia\Protocol;

use Inertia\Protocol\Props\Always;
use Inertia\Protocol\Props\Defer;
use Inertia\Protocol\Props\Lazy;
use Inertia\Protocol\Props\Mergeable;
use Inertia\Protocol\Props\Once;
use Inertia\Protocol\Props\Scroll;
use Inertia\Protocol\Support\Arr;

class Inertia
{
    /**
     * @var array<string, mixed>
     */
    protected static array $sharedProps = [];

    protected static mixed $version = '';

    /**
     * Create an Inertia response builder.
     *
     * @param string $component JavaScript page component name
     * @param array<string, mixed> $props Page props
     */
    public static function render(string $component, array $props = []): InertiaResponse
    {
        $allProps = array_merge(static::resolveSharedProps(), $props);

        $response = new InertiaResponse($component, $allProps, static::$version);
        if (!empty(static::$sharedProps)) {
            $response->withSharedKeys(array_keys(static::$sharedProps));
        }

        return $response;
    }

    /**
     * Generate an external location visit decision (409 Conflict with X-Inertia-Location).
     */
    public static function location(string $url): ResponseDecision
    {
        return (new ProtocolEngine())->location($url);
    }

    /**
     * Generate a URL fragment redirect decision (409 Conflict with X-Inertia-Redirect).
     */
    public static function redirectWithFragment(string $url): ResponseDecision
    {
        return (new ProtocolEngine())->redirectWithFragment($url);
    }

    /**
     * Generate a Precognition validation success decision (204 No Content).
     */
    public static function precognitionSuccess(): ResponseDecision
    {
        return (new ProtocolEngine())->precognitionSuccess();
    }

    /**
     * Create an Always prop (always evaluated, even on partial reloads).
     */
    public static function always(mixed $value): Always
    {
        return new Always($value);
    }

    /**
     * Create a Lazy / Optional prop (skipped on full visits, resolved only on partial reloads).
     */
    public static function lazy(mixed $callback): Lazy
    {
        return new Lazy($callback);
    }

    /**
     * Create a Defer prop (announced in deferredProps, resolved on follow-up request).
     */
    public static function defer(mixed $callback, string $group = 'default', bool $rescue = false): Defer
    {
        return new Defer($callback, $group, $rescue);
    }

    /**
     * Create a Once prop (resolved once and cached client-side).
     */
    public static function once(mixed $callback, ?int $expiresAt = null): Once
    {
        return new Once($callback, $expiresAt);
    }

    /**
     * Create an appended Merge prop.
     */
    public static function merge(mixed $value, ?string $matchOn = null): Mergeable
    {
        return new Mergeable($value, prepend: false, deep: false, matchOn: $matchOn);
    }

    /**
     * Create a prepended Merge prop.
     */
    public static function prepend(mixed $value, ?string $matchOn = null): Mergeable
    {
        return new Mergeable($value, prepend: true, deep: false, matchOn: $matchOn);
    }

    /**
     * Create a deep-merged Merge prop.
     */
    public static function deepMerge(mixed $value, ?string $matchOn = null): Mergeable
    {
        return new Mergeable($value, prepend: false, deep: true, matchOn: $matchOn);
    }

    /**
     * Create an Infinite Scroll prop.
     */
    public static function scroll(
        mixed $data,
        string $pageName = 'page',
        ?int $previousPage = null,
        ?int $nextPage = null,
        int $currentPage = 1,
        bool $reset = false
    ): Scroll {
        return new Scroll($data, $pageName, $previousPage, $nextPage, $currentPage, $reset);
    }

    /**
     * Set the global asset version (string, int, or callable).
     */
    public static function version(mixed $version): void
    {
        static::$version = $version;
    }

    /**
     * Get the current resolved asset version.
     */
    public static function getVersion(): string
    {
        return (string) Arr::value(static::$version);
    }

    /**
     * Share data globally across all Inertia responses.
     *
     * @param array<string, mixed>|string $key
     */
    public static function share(array|string $key, mixed $value = null): void
    {
        if (is_array($key)) {
            static::$sharedProps = array_merge(static::$sharedProps, $key);
        } else {
            static::$sharedProps[$key] = $value;
        }
    }

    /**
     * Get a shared prop value.
     */
    public static function getShared(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return static::$sharedProps;
        }

        return static::$sharedProps[$key] ?? $default;
    }

    /**
     * Flush all shared props.
     */
    public static function flushShared(): void
    {
        static::$sharedProps = [];
    }

    /**
     * Resolve all shared props values.
     *
     * @return array<string, mixed>
     */
    protected static function resolveSharedProps(): array
    {
        $resolved = [];
        foreach (static::$sharedProps as $key => $value) {
            $resolved[$key] = Arr::value($value);
        }

        return $resolved;
    }
}
