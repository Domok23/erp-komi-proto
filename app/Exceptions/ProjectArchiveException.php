<?php

namespace App\Exceptions;

use RuntimeException;

class ProjectArchiveException extends RuntimeException
{
    /** @param list<array{type: string, id: int, label: string}> $blockers */
    public function __construct(
        string $message,
        private array $blockers = [],
    ) {
        parent::__construct($message);
    }

    /** @return list<array{type: string, id: int, label: string}> */
    public function blockers(): array
    {
        return $this->blockers;
    }
}
