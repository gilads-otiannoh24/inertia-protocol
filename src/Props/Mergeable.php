<?php

declare(strict_types=1);

namespace Inertia\Protocol\Props;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Support\Arr;

class Mergeable implements PropInterface
{
    public function __construct(
        public mixed $value,
        public bool $prepend = false,
        public bool $deep = false,
        public ?string $matchOn = null
    ) {
    }

    public function defer(string $group = 'default', bool $rescue = false): Defer
    {
        return new Defer($this, $group, $rescue);
    }

    public function once(?int $expiresAt = null): Once
    {
        return new Once($this, $expiresAt);
    }

    public function live(array $listeners = [], ?int $throttle = null): Live
    {
        return new Live($this, $listeners, $throttle);
    }

    public function resolve(): mixed
    {
        return Arr::resolve($this->value);
    }
}
