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

    public function resolve(): mixed
    {
        return Arr::value($this->callback);
    }
}
