<?php

declare(strict_types=1);

namespace Inertia\Protocol\Props;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Support\Arr;

class Once implements PropInterface
{
    public function __construct(
        public mixed $callback,
        public ?int $expiresAt = null
    ) {
    }

    public function defer(string $group = 'default', bool $rescue = false): Defer
    {
        return new Defer($this, $group, $rescue);
    }

    public function merge(?string $matchOn = null): Mergeable
    {
        return new Mergeable($this, prepend: false, deep: false, matchOn: $matchOn);
    }

    public function prepend(?string $matchOn = null): Mergeable
    {
        return new Mergeable($this, prepend: true, deep: false, matchOn: $matchOn);
    }

    public function deepMerge(?string $matchOn = null): Mergeable
    {
        return new Mergeable($this, prepend: false, deep: true, matchOn: $matchOn);
    }

    public function live(array $listeners = [], ?int $throttle = null): Live
    {
        return new Live($this, $listeners, $throttle);
    }

    public function resolve(): mixed
    {
        return Arr::resolve($this->callback);
    }
}
