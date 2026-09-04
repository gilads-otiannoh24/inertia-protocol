<?php

declare(strict_types=1);

namespace Inertia\Protocol;

use Inertia\Protocol\Contracts\RequestInterface;
use Inertia\Protocol\Props\Always;
use Inertia\Protocol\Props\Defer;
use Inertia\Protocol\Props\Lazy;
use Inertia\Protocol\Props\Mergeable;
use Inertia\Protocol\Props\Once;
use Inertia\Protocol\Props\Scroll;
use Inertia\Protocol\Support\Arr;
use Inertia\Protocol\Support\InertiaHeaders;
use Throwable;

class ProtocolEngine
{
    /**
     * Evaluate an Inertia render request and return a standard ResponseDecision.
     *
     * @param RequestInterface $request Incoming HTTP request
     * @param string $component JavaScript page component name
     * @param array<string, mixed> $props Raw page props (can contain Closures, Prop objects, arrays)
     * @param string|int|callable $version Asset version string, int, or callable
     * @param array<string, mixed> $options Additional options (encryptHistory, clearHistory, preserveFragment, sharedKeys, flash, etc.)
     */
    public function evaluate(
        RequestInterface $request,
        string $component,
        array $props = [],
        mixed $version = '',
        array $options = []
    ): ResponseDecision {
        $resolvedVersion = (string) Arr::value($version);

        // 1. Asset Version Check (GET requests only)
        if (strtoupper($request->getMethod()) === 'GET' && $request->hasHeader(InertiaHeaders::HEADER_VERSION)) {
            $sentVersion = $request->getHeaderLine(InertiaHeaders::HEADER_VERSION);
            if ($resolvedVersion !== '' && $sentVersion !== $resolvedVersion) {
                return new ResponseDecision(
                    type: ResponseDecision::TYPE_CONFLICT_VERSION,
                    statusCode: 409,
                    headers: [
                        InertiaHeaders::HEADER_LOCATION => $request->getUrl(),
                        InertiaHeaders::HEADER_VERSION  => $resolvedVersion,
                    ],
                    content: $request->getUrl()
                );
            }
        }

        // 2. Partial Reload Headers
        $rawInertia = strtolower($request->getHeaderLine(InertiaHeaders::HEADER_INERTIA));
        $isInertia = $request->hasHeader(InertiaHeaders::HEADER_INERTIA) &&
                     $rawInertia !== '' &&
                     $rawInertia !== 'false' &&
                     $rawInertia !== '0';

        $partialDataRaw = $request->getHeaderLine(InertiaHeaders::HEADER_PARTIAL_DATA);
        if ($partialDataRaw === '') {
            $partialDataRaw = $request->getHeaderLine(InertiaHeaders::HEADER_PARTIAL_ONLY);
        }

        $isPartial = $isInertia &&
                     $partialDataRaw !== '' &&
                     $request->getHeaderLine(InertiaHeaders::HEADER_PARTIAL_COMPONENT) === $component;

        $partialData = $isPartial ? array_filter(array_map('trim', explode(',', $partialDataRaw))) : [];
        $partialExcept = $isPartial && $request->hasHeader(InertiaHeaders::HEADER_PARTIAL_EXCEPT)
            ? array_filter(array_map('trim', explode(',', $request->getHeaderLine(InertiaHeaders::HEADER_PARTIAL_EXCEPT))))
            : [];

        $resetProps = $request->hasHeader(InertiaHeaders::HEADER_RESET)
            ? array_filter(array_map('trim', explode(',', $request->getHeaderLine(InertiaHeaders::HEADER_RESET))))
            : [];

        $exceptOnce = $request->hasHeader(InertiaHeaders::HEADER_EXCEPT_ONCE_PROPS)
            ? array_filter(array_map('trim', explode(',', $request->getHeaderLine(InertiaHeaders::HEADER_EXCEPT_ONCE_PROPS))))
            : [];

        $errorBag = $request->getHeaderLine(InertiaHeaders::HEADER_ERROR_BAG);

        // 3. Resolve Props and Metadata
        $resolvedProps = [];
        $deferredProps = [];
        $rescuedProps = [];
        $mergeProps = [];
        $prependProps = [];
        $deepMergeProps = [];
        $matchPropsOn = [];
        $onceProps = [];
        $scrollProps = [];

        // Ensure errors prop exists
        if (!isset($props['errors'])) {
            $props['errors'] = (object) [];
        }

        foreach ($props as $key => $value) {
            // Handle Always props (always resolved in both full visit and partial reload)
            if ($value instanceof Always) {
                $resolvedProps[$key] = $value->resolve();
                continue;
            }

            // Partial reload filtering
            if ($isPartial) {
                if (!empty($partialData) && !in_array($key, $partialData, true)) {
                    continue;
                }
                if (!empty($partialExcept) && in_array($key, $partialExcept, true)) {
                    continue;
                }
            } elseif ($value instanceof Lazy) {
                // Lazy / Optional props are skipped on full visits
                continue;
            }

            // Handle Once props
            if ($value instanceof Once) {
                $onceProps[$key] = [
                    'prop'      => $key,
                    'expiresAt' => $value->expiresAt,
                ];

                // If already cached on client and not a partial reload targeting it, skip resolving
                if (!$isPartial && in_array($key, $exceptOnce, true)) {
                    continue;
                }

                $resolvedProps[$key] = $value->resolve();
                continue;
            }

            // Handle Defer props
            if ($value instanceof Defer) {
                if (!$isPartial || (!empty($partialData) && !in_array($key, $partialData, true))) {
                    $deferredProps[$value->group][] = $key;
                    continue;
                }

                try {
                    $resolvedProps[$key] = $value->resolve();
                } catch (Throwable $e) {
                    if ($value->rescue) {
                        $rescuedProps[] = $key;
                    } else {
                        throw $e;
                    }
                }
                continue;
            }

            // Handle Mergeable props
            if ($value instanceof Mergeable) {
                $isReset = in_array($key, $resetProps, true);
                if (!$isReset) {
                    if ($value->deep) {
                        $deepMergeProps[] = $key;
                    } elseif ($value->prepend) {
                        $prependProps[] = $key;
                    } else {
                        $mergeProps[] = $key;
                    }

                    if ($value->matchOn) {
                        $matchPropsOn[] = "{$key}.{$value->matchOn}";
                    }
                }

                $resolvedProps[$key] = $value->resolve();
                continue;
            }

            // Handle Scroll props
            if ($value instanceof Scroll) {
                $metadata = $value->toMetadata();
                if (in_array($key, $resetProps, true)) {
                    $metadata['reset'] = true;
                } else {
                    $mergeProps[] = "{$key}.data";
                }

                $scrollProps[$key] = $metadata;
                $resolvedProps[$key] = $value->resolve();
                continue;
            }

            // Standard / Callable props
            $resolvedProps[$key] = Arr::value($value);
        }

        // Error Bag scoping
        if ($errorBag !== '' && isset($resolvedProps['errors']) && is_array($resolvedProps['errors'])) {
            $resolvedProps['errors'] = [$errorBag => $resolvedProps['errors']];
        }

        $url = $request->getUrl();

        $pageObject = new PageObject(
            component: $component,
            props: $resolvedProps,
            url: $url,
            version: $resolvedVersion,
            deferredProps: $deferredProps,
            rescuedProps: $rescuedProps,
            mergeProps: $mergeProps,
            prependProps: $prependProps,
            deepMergeProps: $deepMergeProps,
            matchPropsOn: $matchPropsOn,
            onceProps: $onceProps,
            scrollProps: $scrollProps ?: (array) ($options['scrollProps'] ?? []),
            sharedProps: (array) ($options['sharedKeys'] ?? []),
            flash: (array) ($options['flash'] ?? []),
            encryptHistory: (bool) ($options['encryptHistory'] ?? false),
            clearHistory: (bool) ($options['clearHistory'] ?? false),
            preserveFragment: (bool) ($options['preserveFragment'] ?? false)
        );

        if ($isInertia) {
            return new ResponseDecision(
                type: ResponseDecision::TYPE_PAGE_JSON,
                statusCode: 200,
                headers: [
                    InertiaHeaders::HEADER_INERTIA => 'true',
                    'Vary'                         => InertiaHeaders::HEADER_INERTIA,
                    'Content-Type'                 => 'application/json',
                ],
                pageObject: $pageObject,
                content: $pageObject->toArray()
            );
        }

        return new ResponseDecision(
            type: ResponseDecision::TYPE_PAGE_HTML,
            statusCode: 200,
            headers: [
                'Content-Type' => 'text/html; charset=UTF-8',
            ],
            pageObject: $pageObject,
            content: $pageObject
        );
    }

    /**
     * Create an external location visit response decision (409 Conflict).
     */
    public function location(string $url): ResponseDecision
    {
        return new ResponseDecision(
            type: ResponseDecision::TYPE_CONFLICT_LOCATION,
            statusCode: 409,
            headers: [
                InertiaHeaders::HEADER_LOCATION => $url,
            ],
            content: $url
        );
    }

    /**
     * Create a URL fragment redirect response decision (409 Conflict).
     */
    public function redirectWithFragment(string $url): ResponseDecision
    {
        return new ResponseDecision(
            type: ResponseDecision::TYPE_CONFLICT_REDIRECT,
            statusCode: 409,
            headers: [
                InertiaHeaders::HEADER_REDIRECT => $url,
            ],
            content: $url
        );
    }

    /**
     * Create a Precognition validation success response decision (204 No Content).
     */
    public function precognitionSuccess(): ResponseDecision
    {
        return new ResponseDecision(
            type: ResponseDecision::TYPE_PRECOGNITION_SUCCESS,
            statusCode: 204,
            headers: [
                InertiaHeaders::HEADER_PRECOGNITION         => 'true',
                InertiaHeaders::HEADER_PRECOGNITION_SUCCESS => 'true',
                'Vary'                                      => InertiaHeaders::HEADER_PRECOGNITION,
            ]
        );
    }
}
