<?php

declare(strict_types=1);

namespace Inertia\Protocol;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Contracts\RequestInterface;
use Inertia\Protocol\Props\Always;
use Inertia\Protocol\Props\Defer;
use Inertia\Protocol\Props\Lazy;
use Inertia\Protocol\Props\Live;
use Inertia\Protocol\Props\Mergeable;
use Inertia\Protocol\Props\Once;
use Inertia\Protocol\Props\Scroll;
use Inertia\Protocol\Support\Arr;
use Inertia\Protocol\Support\InertiaHeaders;
use Inertia\Protocol\Support\ReservedProps;
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
     * @param array<string, mixed> $options Additional options (encryptHistory, clearHistory, preserveBigIntegers, preserveFragment, sharedKeys, flash, etc.)
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

        $mergeIntent = strtolower($request->getHeaderLine(InertiaHeaders::HEADER_INFINITE_SCROLL_MERGE_INTENT));
        $errorBag = $request->getHeaderLine(InertiaHeaders::HEADER_ERROR_BAG);
        $preserveBigIntegers = (bool) ($options['preserveBigIntegers'] ?? false);
        $protocolVersion = ProtocolVersion::fromMixed($options['protocolVersion'] ?? ProtocolVersion::V3);

        // Validate user props against reserved keys
        ReservedProps::validate($props, $protocolVersion);

        // 3. Resolve Props and Metadata
        $resolvedProps = [];
        $deferredProps = [];
        $rescuedProps = [];
        $mergeProps = [];
        $prependProps = [];
        $deepMergeProps = [];
        $matchPropsOn = [];
        $onceProps = [];
        $liveProps = [];
        $scrollProps = [];

        foreach ($props as $key => $value) {
            $info = $this->inspectProp($value);

            // Handle Always props (always evaluated, even in partial reloads)
            if ($info['isAlways']) {
                $resolvedProps[$key] = Arr::resolve($value);
                continue;
            }

            // Partial reload filtering
            if ($isPartial) {
                if (!Arr::matchesPartial($key, $partialData)) {
                    continue;
                }
                if (!empty($partialExcept) && in_array($key, $partialExcept, true)) {
                    continue;
                }
            } elseif ($info['isLazy'] && $info['defer'] === null) {
                // Optional / Lazy props are skipped on full visits
                continue;
            }

            // Handle Once props
            if ($info['once'] !== null) {
                $onceProps[$key] = [
                    'prop'      => $key,
                    'expiresAt' => $info['once']->expiresAt,
                ];

                // If cached client-side and not a partial reload targeting it, skip resolving
                if (!$isPartial && in_array($key, $exceptOnce, true)) {
                    continue;
                }
            }

            // Handle Defer props
            $resolvedValue = null;
            $hasExplicitResolution = false;

            if ($info['defer'] !== null) {
                if (!$isPartial || (!empty($partialData) && !Arr::matchesPartial($key, $partialData))) {
                    $deferredProps[$info['defer']->group][] = $key;
                    continue;
                }

                try {
                    // Inspect intermediate values if defer callback returns a PropInterface (e.g. Scroll or Mergeable)
                    $target = $value;
                    while ($target instanceof Defer) {
                        $target = $target->callback;
                        if ($target instanceof Closure || (is_callable($target) && !is_string($target))) {
                            $target = $target();
                        }
                    }

                    if ($target instanceof Scroll && $info['scroll'] === null) {
                        $info['scroll'] = $target;
                    } elseif ($target instanceof Mergeable && $info['mergeable'] === null) {
                        $info['mergeable'] = $target;
                    } elseif ($target instanceof Live && $info['live'] === null) {
                        $info['live'] = $target;
                    }

                    $resolvedValue = Arr::resolve($target);
                    $hasExplicitResolution = true;
                } catch (Throwable $e) {
                    if ($info['defer']->rescue) {
                        $rescuedProps[] = $key;
                        continue;
                    }
                    throw $e;
                }
            }

            // Handle Scroll props
            if ($info['scroll'] !== null) {
                $metadata = $info['scroll']->toMetadata();
                if (in_array($key, $resetProps, true)) {
                    $metadata['reset'] = true;
                } else {
                    if ($mergeIntent === 'prepend') {
                        $prependProps[] = "{$key}.data";
                    } else {
                        $mergeProps[] = "{$key}.data";
                    }
                }
                $scrollProps[$key] = $metadata;
            }

            // Handle Mergeable props
            if ($info['mergeable'] !== null) {
                $isReset = in_array($key, $resetProps, true);
                if (!$isReset) {
                    if ($info['mergeable']->deep) {
                        $deepMergeProps[] = $key;
                    } elseif ($info['mergeable']->prepend) {
                        $prependProps[] = $key;
                    } else {
                        $mergeProps[] = $key;
                    }

                    if ($info['mergeable']->matchOn) {
                        $matchPropsOn[] = "{$key}.{$info['mergeable']->matchOn}";
                    }
                }
            }

            // Handle Live props
            if ($info['live'] !== null) {
                $liveProps[$key] = $info['live']->toMetadata();
            }

            // Resolve prop value if not already done
            if (!$hasExplicitResolution) {
                $resolvedValue = Arr::resolve($value);
            }

            // Dot notation filtering for plain arrays in partial reloads
            if ($isPartial && is_array($value) && is_array($resolvedValue)) {
                $resolvedValue = Arr::filterByDotPaths($resolvedValue, $key, $partialData, $partialExcept);
            }

            $resolvedProps[$key] = $resolvedValue;
        }

        // Populate errors prop from options or default to empty object
        $errorsData = $options['errors'] ?? (object) [];

        // Error Bag scoping
        if ($errorBag !== '') {
            if (is_array($errorsData) || is_object($errorsData)) {
                $errorsData = [$errorBag => (array) $errorsData];
            }
        }

        $resolvedProps['errors'] = $errorsData;

        // Big integer preservation outside JavaScript's safe range
        if ($preserveBigIntegers) {
            $resolvedProps = Arr::wrapBigIntegers($resolvedProps);
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
            liveProps: $liveProps,
            scrollProps: $scrollProps ?: (array) ($options['scrollProps'] ?? []),
            sharedProps: (array) ($options['sharedKeys'] ?? []),
            flash: (array) ($options['flash'] ?? []),
            encryptHistory: (bool) ($options['encryptHistory'] ?? false),
            clearHistory: (bool) ($options['clearHistory'] ?? false),
            preserveBigIntegers: $preserveBigIntegers,
            preserveFragment: (bool) ($options['preserveFragment'] ?? false),
            protocolVersion: $protocolVersion
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
     * Inspect a prop to extract any composed category modifiers.
     *
     * @return array{
     *   isAlways: bool,
     *   isLazy: bool,
     *   defer: Defer|null,
     *   once: Once|null,
     *   scroll: Scroll|null,
     *   mergeable: Mergeable|null,
     *   live: Live|null,
     *   innerValue: mixed
     * }
     */
    protected function inspectProp(mixed $prop): array
    {
        $isAlways = false;
        $isLazy = false;
        $defer = null;
        $once = null;
        $scroll = null;
        $mergeable = null;
        $live = null;

        $current = $prop;
        while ($current instanceof PropInterface) {
            if ($current instanceof Always) {
                $isAlways = true;
                $current = $current->value;
            } elseif ($current instanceof Defer) {
                $defer = $current;
                $current = $current->callback;
            } elseif ($current instanceof Lazy) {
                $isLazy = true;
                $current = $current->callback;
            } elseif ($current instanceof Once) {
                $once = $current;
                $current = $current->callback;
            } elseif ($current instanceof Scroll) {
                $scroll = $current;
                $current = $current->data;
            } elseif ($current instanceof Mergeable) {
                $mergeable = $current;
                $current = $current->value;
            } elseif ($current instanceof Live) {
                $live = $current;
                $current = $current->value;
            } else {
                break;
            }
        }

        return [
            'isAlways'   => $isAlways,
            'isLazy'     => $isLazy,
            'defer'      => $defer,
            'once'       => $once,
            'scroll'     => $scroll,
            'mergeable'  => $mergeable,
            'live'       => $live,
            'innerValue' => $current,
        ];
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
     * Create a standard redirect decision (302 or 303 for non-GET requests).
     *
     * @param string $url
     * @param RequestInterface|null $request
     * @param int $defaultStatusCode
     * @param array<string, string> $headers
     * @param ProtocolVersion|string|null $protocolVersion
     */
    public function redirect(
        string $url,
        ?RequestInterface $request = null,
        int $defaultStatusCode = 302,
        array $headers = [],
        ProtocolVersion|string|null $protocolVersion = null
    ): ResponseDecision {
        $ver = $protocolVersion ? ProtocolVersion::fromMixed($protocolVersion) : ProtocolVersion::V3;
        $method = $request ? strtoupper($request->getMethod()) : 'GET';
        $methodsWith303 = $ver->isV2() ? ['PUT', 'PATCH', 'DELETE'] : ['PUT', 'PATCH', 'DELETE', 'QUERY'];
        $statusCode = in_array($method, $methodsWith303, true) ? 303 : $defaultStatusCode;

        return new ResponseDecision(
            type: ResponseDecision::TYPE_REDIRECT,
            statusCode: $statusCode,
            headers: array_merge(['Location' => $url], $headers),
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
