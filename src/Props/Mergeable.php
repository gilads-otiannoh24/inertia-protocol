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

    public function resolve(): mixed
    {
        return Arr::value($this->value);
    }
}
