<?php

declare(strict_types=1);

namespace Inertia\Protocol\Support;

final class InertiaHeaders
{
    public const HEADER_INERTIA = 'X-Inertia';
    public const HEADER_VERSION = 'X-Inertia-Version';
    public const HEADER_LOCATION = 'X-Inertia-Location';
    public const HEADER_REDIRECT = 'X-Inertia-Redirect';
    public const HEADER_ERROR_BAG = 'X-Inertia-Error-Bag';
    public const HEADER_PARTIAL_COMPONENT = 'X-Inertia-Partial-Component';
    public const HEADER_PARTIAL_DATA = 'X-Inertia-Partial-Data';
    public const HEADER_PARTIAL_EXCEPT = 'X-Inertia-Partial-Except';
    public const HEADER_RESET = 'X-Inertia-Reset';
    public const HEADER_EXCEPT_ONCE_PROPS = 'X-Inertia-Except-Once-Props';
    public const HEADER_INFINITE_SCROLL_MERGE_INTENT = 'X-Inertia-Infinite-Scroll-Merge-Intent';
    public const HEADER_PRECOGNITION = 'Precognition';
    public const HEADER_PRECOGNITION_VALIDATE_ONLY = 'Precognition-Validate-Only';
    public const HEADER_PRECOGNITION_SUCCESS = 'Precognition-Success';
}
