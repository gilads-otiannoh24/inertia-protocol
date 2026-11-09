<?php

declare(strict_types=1);

namespace Inertia\Protocol;

use Inertia\Protocol\Contracts\RequestInterface;
use Inertia\Protocol\Props\Always;
use Inertia\Protocol\Props\Defer;
use Inertia\Protocol\Props\Lazy;
use Inertia\Protocol\Props\Live;
use Inertia\Protocol\Props\Mergeable;
use Inertia\Protocol\Props\Once;
use Inertia\Protocol\Props\Optional;
use Inertia\Protocol\Props\Scroll;
use Inertia\Protocol\Support\Arr;
use Inertia\Protocol\Support\ReservedProps;

class InertiaProtocol
{
    /**
     * @var array<string, mixed>
     */
    protected static array $sharedProps = [];

    protected static mixed $version = '';

    protected static bool $preserveBigIntegers = false;

    protected static ProtocolVersion $protocolVersion = ProtocolVersion::V3;

    /**
     * Enable or disable protection against overwriting reserved prop keys.
     */
    public static function protectReservedProps(bool $protect = true): void
    {
        if ($protect) {
            ReservedProps::enable();
        } else {
            ReservedProps::disable();
        }
    }

    /**
     * Set the global default wire protocol version (e.g. ProtocolVersion::V2 or ProtocolVersion::V3).
     */
    public static function setProtocolVersion(ProtocolVersion|string $version): void
    {
        static::$protocolVersion = ProtocolVersion::fromMixed($version);
    }

    /**
     * Get the global default wire protocol version.
     */
    public static function getProtocolVersion(): ProtocolVersion
    {
        return static::$protocolVersion;
    }

    /**
     * Create an Inertia response builder.
     *
     * @param string $component JavaScript page component name
     * @param array<string, mixed> $props Page props
     */
    public static function render(string $component, array $props = []): InertiaResponse
    {
        ReservedProps::validate($props, static::$protocolVersion);

        $allProps = array_merge(static::$sharedProps, $props);

        $response = new InertiaResponse($component, $allProps, static::$version);
        if (!empty(static::$sharedProps)) {
            $response->withSharedKeys(array_keys(static::$sharedProps));
        }

        if (static::$preserveBigIntegers) {
            $response->preserveBigIntegers(true);
        }

        $response->withProtocolVersion(static::$protocolVersion);

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
     * Generate a standard redirect decision (302 or 303 for non-GET requests).
     *
     * @param string $url Target redirect URL
     * @param RequestInterface|null $request Incoming request (to detect method for 303 conversion)
     * @param int $defaultStatusCode Default status (302)
     * @param array<string, string> $headers Additional headers
     * @param ProtocolVersion|string|null $protocolVersion
     */
    public static function redirect(
        string $url,
        ?RequestInterface $request = null,
        int $defaultStatusCode = 302,
        array $headers = [],
        ProtocolVersion|string|null $protocolVersion = null
    ): ResponseDecision {
        $ver = $protocolVersion ? ProtocolVersion::fromMixed($protocolVersion) : static::$protocolVersion;
        return (new ProtocolEngine())->redirect($url, $request, $defaultStatusCode, $headers, $ver);
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
     * Create a Lazy prop (skipped on full visits, resolved only on partial reloads).
     */
    public static function lazy(mixed $callback): Lazy
    {
        return new Lazy($callback);
    }

    /**
     * Create an Optional prop (skipped on full visits, resolved only on partial reloads).
     * Standard alias for lazy().
     */
    public static function optional(mixed $callback): Optional
    {
        return new Optional($callback);
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
     * Create a Live prop (refreshes automatically upon broadcast event arrival).
     *
     * @param mixed $value Underlying prop value
     * @param list<array{channel: array{name: string, type?: string}|string, events: list<string>|string}> $listeners
     * @param int|null $throttle Throttle in milliseconds
     */
    public static function live(mixed $value, array $listeners = [], ?int $throttle = null): Live
    {
        return new Live($value, $listeners, $throttle);
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
     * Globally configure integer preservation outside JavaScript's safe range.
     */
    public static function preserveBigIntegers(bool $preserve = true): void
    {
        static::$preserveBigIntegers = $preserve;
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
            ReservedProps::validate($key, static::$protocolVersion);
            static::$sharedProps = array_merge(static::$sharedProps, $key);
        } else {
            ReservedProps::validate([$key => $value], static::$protocolVersion);
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
     * Resolve all shared props values explicitly.
     *
     * @return array<string, mixed>
     */
    public static function resolveSharedProps(): array
    {
        $resolved = [];
        foreach (static::$sharedProps as $key => $value) {
            $resolved[$key] = Arr::resolve($value);
        }

        return $resolved;
    }
}
