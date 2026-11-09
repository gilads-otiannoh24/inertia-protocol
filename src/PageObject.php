<?php

declare(strict_types=1);

namespace Inertia\Protocol;

use JsonSerializable;

class PageObject implements JsonSerializable
{
    /**
     * @param string $component JavaScript page component name
     * @param array<string, mixed> $props Resolved page props (including 'errors')
     * @param string $url Page URL with path, query, and fragment
     * @param string|int $version Asset version string or integer
     * @param array<string, list<string>> $deferredProps Grouped deferred prop names
     * @param list<string> $rescuedProps Rescued deferred prop names
     * @param list<string> $mergeProps Appended merge prop keys
     * @param list<string> $prependProps Prepended merge prop keys
     * @param list<string> $deepMergeProps Deep-merged prop keys
     * @param list<string> $matchPropsOn Matching identification fields (e.g. ['posts.id'])
     * @param array<string, array{prop: string, expiresAt: int|null}> $onceProps Once props metadata
     * @param array<string, array{listeners: list<array{channel: array{name: string, type: string}, events: list<string>}>, throttle?: int}> $liveProps Live props metadata
     * @param array<string, array{pageName: string, previousPage: int|null, nextPage: int|null, currentPage: int, reset: bool}> $scrollProps Infinite scroll metadata
     * @param list<string> $sharedProps Top-level shared prop names
     * @param array<string, mixed> $flash Flash session data
     * @param bool $encryptHistory
     * @param bool $clearHistory
     * @param bool $preserveBigIntegers
     * @param bool $preserveFragment
     * @param ProtocolVersion $protocolVersion Wire protocol version (V2 or V3)
     */
    public function __construct(
        public string $component,
        public array $props,
        public string $url,
        public string|int $version = '',
        public array $deferredProps = [],
        public array $rescuedProps = [],
        public array $mergeProps = [],
        public array $prependProps = [],
        public array $deepMergeProps = [],
        public array $matchPropsOn = [],
        public array $onceProps = [],
        public array $liveProps = [],
        public array $scrollProps = [],
        public array $sharedProps = [],
        public array $flash = [],
        public bool $encryptHistory = false,
        public bool $clearHistory = false,
        public bool $preserveBigIntegers = false,
        public bool $preserveFragment = false,
        public ProtocolVersion $protocolVersion = ProtocolVersion::V3
    ) {
        // Ensure errors is always present as an object/array
        if (!isset($this->props['errors'])) {
            $this->props['errors'] = (object) [];
        }
    }

    /**
     * Convert the Page Object to an associative array matching the Inertia specification.
     * Conditional metadata fields are only emitted when active/non-empty and supported by the protocol version.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'component' => $this->component,
            'props'     => $this->props,
            'url'       => $this->url,
            'version'   => (string) $this->version,
        ];

        if ($this->encryptHistory) {
            $data['encryptHistory'] = true;
        }

        if ($this->clearHistory) {
            $data['clearHistory'] = true;
        }

        if (!empty($this->deferredProps)) {
            $data['deferredProps'] = $this->deferredProps;
        }

        if (!empty($this->mergeProps)) {
            $data['mergeProps'] = $this->mergeProps;
        }

        if (!empty($this->prependProps)) {
            $data['prependProps'] = $this->prependProps;
        }

        if (!empty($this->deepMergeProps)) {
            $data['deepMergeProps'] = $this->deepMergeProps;
        }

        if (!empty($this->matchPropsOn)) {
            $data['matchPropsOn'] = $this->matchPropsOn;
        }

        if (!empty($this->onceProps)) {
            $data['onceProps'] = $this->onceProps;
        }

        if (!empty($this->scrollProps)) {
            $data['scrollProps'] = $this->scrollProps;
        }

        // v3-specific metadata fields
        if ($this->protocolVersion->isV3()) {
            if ($this->preserveBigIntegers) {
                $data['preserveBigIntegers'] = true;
            }

            if ($this->preserveFragment) {
                $data['preserveFragment'] = true;
            }

            if (!empty($this->rescuedProps)) {
                $data['rescuedProps'] = $this->rescuedProps;
            }

            if (!empty($this->liveProps)) {
                $data['liveProps'] = $this->liveProps;
            }

            if (!empty($this->sharedProps)) {
                $data['sharedProps'] = $this->sharedProps;
            }

            if (!empty($this->flash)) {
                $data['flash'] = $this->flash;
            }
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags | JSON_THROW_ON_ERROR);
    }

    /**
     * Generate HTML-safe JSON representation, escaping characters that could break <script> tags.
     */
    public function toHtmlJson(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        );
    }

    /**
     * Render the v3 specification <script data-page="..." type="application/json"> element.
     */
    public function toHtmlScript(string $rootId = 'app'): string
    {
        $escapedRootId = htmlspecialchars($rootId, ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<script data-page="%s" type="application/json">%s</script>',
            $escapedRootId,
            $this->toHtmlJson()
        );
    }

    /**
     * Render the root mounting element <div id="..."></div>.
     */
    public function toHtmlTag(string $rootId = 'app'): string
    {
        $escapedRootId = htmlspecialchars($rootId, ENT_QUOTES, 'UTF-8');
        return sprintf('<div id="%s"></div>', $escapedRootId);
    }

    /**
     * Render the v2 specification <div id="..." data-page="..."></div> element.
     */
    public function toHtmlDiv(string $rootId = 'app'): string
    {
        $escapedRootId = htmlspecialchars($rootId, ENT_QUOTES, 'UTF-8');
        $escapedJson = htmlspecialchars($this->toJson(), ENT_QUOTES, 'UTF-8');
        return sprintf('<div id="%s" data-page=\'%s\'></div>', $escapedRootId, $escapedJson);
    }

    /**
     * Render the complete HTML markup for the page according to the configured wire protocol version.
     * - Version 2: <div id="app" data-page="..."></div>
     * - Version 3: <script data-page="app" type="application/json">...</script><div id="app"></div>
     */
    public function toHtml(string $rootId = 'app'): string
    {
        if ($this->protocolVersion->isV2()) {
            return $this->toHtmlDiv($rootId);
        }

        return $this->toHtmlScript($rootId) . $this->toHtmlTag($rootId);
    }
}
