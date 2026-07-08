<?php

namespace Tests\Feature;

use App\Filament\Resources\InventoryMovementResource\Pages\CreateInventoryMovement;
use App\Models\Company;
use App\Models\InventoryMovement;
use App\Models\InventoryStock;
use App\Models\Material;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryMovementAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Warehouse $warehouse;

    private Material $material;

    private InventoryStock $stock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST-COMP',
            'address' => 'Test Address',
        ]);

        $this->warehouse = Warehouse::create([
            'company_id' => $this->company->id,
            'code' => 'WH-MAIN',
            'name' => 'Main Warehouse',
            'is_active' => true,
        ]);

        $this->material = Material::create([
            'company_id' => $this->company->id,
            'code' => 'MAT-TEST',
            'name' => 'Test Material',
            'category' => 'fabric',
            'unit' => 'pcs',
            'price' => 1000,
        ]);

        $this->stock = InventoryStock::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'material_id' => $this->material->id,
            'quantity' => 100.00,
            'reserved_qty' => 0.00,
            'available_qty' => 100.00,
            'unit' => 'pcs',
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);
        CompanyContext::setCompany($this->company);
    }

    public function test_adjustment_addition_creates_positive_movement_and_adds_stock(): void
    {
        Livewire::test(CreateInventoryMovement::class)
            ->set('data.type', 'adjustment')
            ->set('data.direction', 'addition')
            ->set('data.material_id', $this->material->id)
            ->set('data.quantity', 25.50)
            ->assertSet('data.inventory_stock_id', $this->stock->id)
            ->assertSet('data.before_qty', 100.00)
            ->assertSet('data.after_qty', 125.50)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->stock->refresh();
        $this->assertEquals(125.50, (float) $this->stock->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'adjustment',
            'quantity' => 25.50,
            'before_qty' => 100.00,
            'after_qty' => 125.50,
        ]);
    }

    public function test_adjustment_subtraction_creates_negative_movement_and_subtracts_stock(): void
    {
        Livewire::test(CreateInventoryMovement::class)
            ->set('data.type', 'adjustment')
            ->set('data.direction', 'subtraction')
            ->set('data.material_id', $this->material->id)
            ->set('data.quantity', 40.00)
            ->assertSet('data.inventory_stock_id', $this->stock->id)
            ->assertSet('data.before_qty', 100.00)
            ->assertSet('data.after_qty', 60.00)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->stock->refresh();
        $this->assertEquals(60.00, (float) $this->stock->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'adjustment',
            'quantity' => -40.00,
            'before_qty' => 100.00,
            'after_qty' => 60.00,
        ]);
    }

    public function test_model_throws_exception_on_negative_quantity_for_non_adjustments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Quantity for non-adjustment movements must be positive.');

        InventoryMovement::create([
            'company_id' => $this->company->id,
            'inventory_stock_id' => $this->stock->id,
            'material_id' => $this->material->id,
            'type' => 'purchase',
            'quantity' => -10.00,
        ]);
    }
}
