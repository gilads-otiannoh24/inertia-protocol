<?php

declare(strict_types=1);

namespace Inertia\Protocol;

class ResponseDecision
{
    public const TYPE_PAGE_JSON = 'page_json';
    public const TYPE_PAGE_HTML = 'page_html';
    public const TYPE_CONFLICT_VERSION = 'conflict_version';
    public const TYPE_CONFLICT_LOCATION = 'conflict_location';
    public const TYPE_CONFLICT_REDIRECT = 'conflict_redirect';
    public const TYPE_PRECOGNITION_SUCCESS = 'precognition_success';
    public const TYPE_REDIRECT = 'redirect';

    /**
     * @param string $type One of the TYPE_* constants
     * @param int $statusCode HTTP status code
     * @param array<string, string> $headers HTTP headers to attach
     * @param PageObject|null $pageObject The evaluated page object (if a page response)
     * @param mixed $content Raw content / body or redirect target
     */
    public function __construct(
        public string $type,
        public int $statusCode,
        public array $headers = [],
        public ?PageObject $pageObject = null,
        public mixed $content = null
    ) {
    }

    public function isJson(): bool
    {
        return $this->type === self::TYPE_PAGE_JSON;
    }

    public function isHtml(): bool
    {
        return $this->type === self::TYPE_PAGE_HTML;
    }

    public function isConflict(): bool
    {
        return in_array($this->type, [
            self::TYPE_CONFLICT_VERSION,
            self::TYPE_CONFLICT_LOCATION,
            self::TYPE_CONFLICT_REDIRECT,
        ], true);
    }

    public function isPrecognition(): bool
    {
        return $this->type === self::TYPE_PRECOGNITION_SUCCESS;
    }
}
