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

    public function isRedirect(): bool
    {
        return $this->type === self::TYPE_REDIRECT;
    }

    /**
     * Render the page object's HTML tags (respects protocolVersion: v2 div vs v3 script+div).
     */
    public function toHtml(string $rootId = 'app'): ?string
    {
        return $this->pageObject?->toHtml($rootId);
    }

    /**
     * Render the v2 specification <div id="..." data-page="..."></div>.
     */
    public function toHtmlDiv(string $rootId = 'app'): ?string
    {
        return $this->pageObject?->toHtmlDiv($rootId);
    }

    /**
     * Render the v3 specification <script data-page="..." type="application/json">...</script>.
     */
    public function toHtmlScript(string $rootId = 'app'): ?string
    {
        return $this->pageObject?->toHtmlScript($rootId);
    }
}
