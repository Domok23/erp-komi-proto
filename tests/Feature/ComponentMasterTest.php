<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Component;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComponentMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_master_creation_and_persistence(): void
    {
        $company = Company::create([
            'name' => 'Master Test Co',
            'code' => 'MTC',
            'address' => 'Test Address',
        ]);

        $comp = Component::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Shoulder Strap Pad',
        ]);

        $this->assertDatabaseHas('components', [
            'company_id' => $company->id,
            'name' => 'Shoulder Strap Pad',
        ]);

        // Assert duplicate creation returns existing record
        $comp2 = Component::firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Shoulder Strap Pad',
        ]);

        $this->assertEquals($comp->id, $comp2->id);
    }
}
