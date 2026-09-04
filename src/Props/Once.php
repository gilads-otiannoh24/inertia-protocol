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

    public function resolve(): mixed
    {
        return Arr::value($this->callback);
    }
}
