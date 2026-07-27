<?php

namespace Tests\Unit\Services;

use App\Services\StockCalculator;
use PHPUnit\Framework\TestCase;

class StockCalculatorTest extends TestCase
{
    public function test_calculates_status_for_sufficient_stock(): void
    {
        $calc = new StockCalculator;
        $result = $calc->calculate(
            materialId: 1,
            quantityPerUnit: 0.5,
            productionQty: 100.0,
            currentStock: 60.0,
        );

        $this->assertSame(50.0, $result['required']);
        $this->assertSame(0.0, $result['to_buy']);
        $this->assertSame('sufficient', $result['status']);
    }

    public function test_calculates_status_for_short_stock(): void
    {
        $calc = new StockCalculator;
        $result = $calc->calculate(
            materialId: 2,
            quantityPerUnit: 1.0,
            productionQty: 100.0,
            currentStock: 0.0,
        );

        $this->assertSame(100.0, $result['required']);
        $this->assertSame(100.0, $result['to_buy']);
        $this->assertSame('short', $result['status']);
    }

    public function test_calculates_status_for_partial_stock(): void
    {
        $calc = new StockCalculator;
        $result = $calc->calculate(
            materialId: 3,
            quantityPerUnit: 1.0,
            productionQty: 100.0,
            currentStock: 70.0,
        );

        $this->assertSame(30.0, $result['to_buy']);
        $this->assertSame('partial', $result['status']);
    }
}
