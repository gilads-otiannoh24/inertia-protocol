<?php

declare(strict_types=1);

namespace Inertia\Protocol\Props;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Support\Arr;

class Defer implements PropInterface
{
    public function __construct(
        public mixed $callback,
        public string $group = 'default',
        public bool $rescue = false
    ) {
    }

    public function merge(?string $matchOn = null): self
    {
        $this->callback = new Mergeable($this->callback, prepend: false, deep: false, matchOn: $matchOn);
        return $this;
    }

    public function prepend(?string $matchOn = null): self
    {
        $this->callback = new Mergeable($this->callback, prepend: true, deep: false, matchOn: $matchOn);
        return $this;
    }

    public function deepMerge(?string $matchOn = null): self
    {
        $this->callback = new Mergeable($this->callback, prepend: false, deep: true, matchOn: $matchOn);
        return $this;
    }

    public function once(?int $expiresAt = null): self
    {
        $this->callback = new Once($this->callback, $expiresAt);
        return $this;
    }

    public function scroll(
        string $pageName = 'page',
        ?int $previousPage = null,
        ?int $nextPage = null,
        int $currentPage = 1,
        bool $reset = false
    ): self {
        $this->callback = new Scroll($this->callback, $pageName, $previousPage, $nextPage, $currentPage, $reset);
        return $this;
    }

    public function live(array $listeners = [], ?int $throttle = null): self
    {
        $this->callback = new Live($this->callback, $listeners, $throttle);
        return $this;
    }

    public function resolve(): mixed
    {
        return Arr::resolve($this->callback);
    }
}
