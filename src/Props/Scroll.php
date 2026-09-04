<?php

declare(strict_types=1);

namespace Inertia\Protocol\Props;

use Inertia\Protocol\Contracts\PropInterface;
use Inertia\Protocol\Support\Arr;

class Scroll implements PropInterface
{
    public function __construct(
        public mixed $data,
        public string $pageName = 'page',
        public ?int $previousPage = null,
        public ?int $nextPage = null,
        public int $currentPage = 1,
        public bool $reset = false
    ) {
    }

    public function resolve(): mixed
    {
        return Arr::value($this->data);
    }

    /**
     * @return array{pageName: string, previousPage: int|null, nextPage: int|null, currentPage: int, reset: bool}
     */
    public function toMetadata(): array
    {
        return [
            'pageName'     => $this->pageName,
            'previousPage' => $this->previousPage,
            'nextPage'     => $this->nextPage,
            'currentPage'  => $this->currentPage,
            'reset'        => $this->reset,
        ];
    }
}
