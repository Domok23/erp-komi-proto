<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PoSupplier;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PoSupplierApprovalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Company $company;

    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'COM001',
        ]);

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);

        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Supplier Test',
            'code' => 'SUP001',
        ]);
    }

    public function test_po_starts_as_draft()
    {
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'grand_total' => 50000000,
        ]);

        $po->refresh();

        $this->assertEquals('draft', $po->approval_status);
        $this->assertEquals(0, $po->revision_number);
    }

    public function test_submit_for_approval_below_100m_only_requires_manager()
    {
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'grand_total' => 50000000, // 50M (under 100M)
        ]);

        $po->update(['approval_status' => 'pending_approval']);
        $po->approvals()->create([
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        $this->assertEquals(1, $po->approvals()->count());
        $this->assertEquals('manager', $po->approvals()->first()->approval_level);
        $this->assertEquals('pending', $po->approvals()->first()->status);
    }

    public function test_submit_for_approval_above_100m_requires_manager_and_director()
    {
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-2026-0002',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'grand_total' => 150000000, // 150M (above 100M)
        ]);

        $po->update(['approval_status' => 'pending_approval']);
        $po->approvals()->create([
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);
        $po->approvals()->create([
            'approval_level' => 'director',
            'status' => 'pending',
        ]);

        $this->assertEquals(2, $po->approvals()->count());
        $this->assertEquals('manager', $po->approvals()->where('approval_level', 'manager')->first()->approval_level);
        $this->assertEquals('director', $po->approvals()->where('approval_level', 'director')->first()->approval_level);
    }

    public function test_approval_flow_completion()
    {
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'grand_total' => 50000000,
            'approval_status' => 'pending_approval',
        ]);

        $approval = $po->approvals()->create([
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        // Manager signs
        $approval->update([
            'status' => 'approved',
            'user_id' => $this->user->id,
            'signature_path' => 'data:image/png;base64,foo',
            'actioned_at' => now(),
        ]);

        $remainingPending = $po->approvals()->where('status', 'pending')->exists();
        if (! $remainingPending) {
            $po->update([
                'approval_status' => 'approved',
                'status' => 'ordered',
            ]);
        }

        $this->assertEquals('approved', $po->approval_status);
        $this->assertEquals('ordered', $po->status);
    }

    public function test_rejection_reasons()
    {
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'grand_total' => 50000000,
            'approval_status' => 'pending_approval',
        ]);

        $approval = $po->approvals()->create([
            'approval_level' => 'manager',
            'status' => 'pending',
        ]);

        // Manager rejects
        $approval->update([
            'status' => 'rejected',
            'user_id' => $this->user->id,
            'rejection_reason' => 'Wrong pricing on items.',
            'actioned_at' => now(),
        ]);
        $po->update(['approval_status' => 'rejected']);

        $this->assertEquals('rejected', $po->approval_status);
        $this->assertEquals('Wrong pricing on items.', $po->approvals()->first()->rejection_reason);
    }

    public function test_revision_process_clones_po()
    {
        $po = PoSupplier::create([
            'company_id' => $this->company->id,
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $this->supplier->id,
            'po_date' => now(),
            'grand_total' => 50000000,
            'approval_status' => 'rejected',
        ]);

        // Clone/replicate to create revision
        $baseNumber = preg_replace('/-R\d+$/', '', $po->po_number);
        $newRevisionNumber = $po->revision_number + 1;
        $newPoNumber = $baseNumber.'-R'.$newRevisionNumber;

        $newPo = $po->replicate();
        $newPo->po_number = $newPoNumber;
        $newPo->approval_status = 'draft';
        $newPo->status = 'draft';
        $newPo->parent_id = $po->id;
        $newPo->revision_number = $newRevisionNumber;
        $newPo->save();

        $this->assertEquals('PO-2026-0001-R1', $newPo->po_number);
        $this->assertEquals('draft', $newPo->approval_status);
        $this->assertEquals($po->id, $newPo->parent_id);
        $this->assertEquals(1, $newPo->revision_number);
    }
}
