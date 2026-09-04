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
     * @param array<string, array{pageName: string, previousPage: int|null, nextPage: int|null, currentPage: int, reset: bool}> $scrollProps Infinite scroll metadata
     * @param list<string> $sharedProps Top-level shared prop names
     * @param array<string, mixed> $flash Flash session data
     * @param bool $encryptHistory
     * @param bool $clearHistory
     * @param bool $preserveFragment
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
        public array $scrollProps = [],
        public array $sharedProps = [],
        public array $flash = [],
        public bool $encryptHistory = false,
        public bool $clearHistory = false,
        public bool $preserveFragment = false
    ) {
        // Ensure errors is always present as an object/array
        if (!isset($this->props['errors'])) {
            $this->props['errors'] = (object) [];
        }
    }

    /**
     * Convert the Page Object to an associative array matching the Inertia specification.
     * Conditional metadata fields are only emitted when active/non-empty.
     *
     * @return array
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

        if ($this->preserveFragment) {
            $data['preserveFragment'] = true;
        }

        if (!empty($this->deferredProps)) {
            $data['deferredProps'] = $this->deferredProps;
        }

        if (!empty($this->rescuedProps)) {
            $data['rescuedProps'] = $this->rescuedProps;
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

        if (!empty($this->sharedProps)) {
            $data['sharedProps'] = $this->sharedProps;
        }

        if (!empty($this->flash)) {
            $data['flash'] = $this->flash;
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
}
