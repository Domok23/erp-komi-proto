<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public static function forMaterial(string $materialName, float $available, float $required): self
    {
        return new self("Insufficient stock for '{$materialName}'. Available: {$available}, Required: {$required}.");
    }
}
