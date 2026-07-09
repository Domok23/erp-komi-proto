<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\GeneralLedger;
use Illuminate\Database\Seeder;

class GeneralLedgerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        if (! $company) {
            $this->command->warn('No company found. Skipping General Ledger seeder.');

            return;
        }

        $accounts = ChartOfAccount::where('company_id', $company->id)->get()->keyBy('account_name');

        // Sample entries for January 2026
        $entries = [
            [
                'entry_number' => 'GL-' . now()->subDays(25)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(25)->toDateString(),
                'description' => 'Sales Revenue - Order SO-2026-001',
                'debit_account' => 'Accounts Receivable',
                'credit_account' => 'Sales Revenue',
                'amount' => 150000000,
                'reference_type' => 'invoice',
                'reference_id' => 1,
                'notes' => 'Sales to Customer ABC',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(22)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(22)->toDateString(),
                'description' => 'Material Cost - Purchase PO-SUP-2026-001',
                'debit_account' => 'Material Cost',
                'credit_account' => 'Accounts Payable',
                'amount' => 50000000,
                'reference_type' => 'po',
                'reference_id' => 1,
                'notes' => 'Raw materials purchase',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(18)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(18)->toDateString(),
                'description' => 'Labor Cost - wages',
                'debit_account' => 'Labor Cost',
                'credit_account' => 'Bank BCA',
                'amount' => 35000000,
                'reference_type' => 'payment',
                'reference_id' => 1,
                'notes' => 'Monthly salary payment',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(15)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(15)->toDateString(),
                'description' => 'Overhead - Utilities',
                'debit_account' => 'Utilities',
                'credit_account' => 'Bank BCA',
                'amount' => 8000000,
                'reference_type' => 'payment',
                'reference_id' => 2,
                'notes' => 'Electricity and water bills',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(12)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(12)->toDateString(),
                'description' => 'Shipping Cost - Export',
                'debit_account' => 'Shipping Cost',
                'credit_account' => 'Cash',
                'amount' => 12000000,
                'reference_type' => 'invoice',
                'reference_id' => 2,
                'notes' => 'International shipping',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(8)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(8)->toDateString(),
                'description' => 'Rent Payment',
                'debit_account' => 'Rent',
                'credit_account' => 'Bank BCA',
                'amount' => 25000000,
                'reference_type' => 'payment',
                'reference_id' => 3,
                'notes' => 'Monthly rent',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(4)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(4)->toDateString(),
                'description' => 'Sales Revenue - Order SO-2026-002',
                'debit_account' => 'Accounts Receivable',
                'credit_account' => 'Sales Revenue',
                'amount' => 200000000,
                'reference_type' => 'invoice',
                'reference_id' => 3,
                'notes' => 'Sales to Customer XYZ',
            ],
            [
                'entry_number' => 'GL-' . now()->subDays(2)->format('Ymd') . '-001',
                'entry_date' => now()->subDays(2)->toDateString(),
                'description' => 'Material Cost - Purchase PO-SUP-2026-002',
                'debit_account' => 'Material Cost',
                'credit_account' => 'Accounts Payable',
                'amount' => 75000000,
                'reference_type' => 'po',
                'reference_id' => 2,
                'notes' => 'Additional raw materials',
            ],
        ];

        foreach ($entries as $entry) {
            $debitAccount = $accounts->get($entry['debit_account']);
            $creditAccount = $accounts->get($entry['credit_account']);

            if (! $debitAccount || ! $creditAccount) {
                $this->command->warn("Skipping entry {$entry['entry_number']}: Missing accounts");

                continue;
            }

            GeneralLedger::create([
                'company_id' => $company->id,
                'entry_number' => $entry['entry_number'],
                'entry_date' => $entry['entry_date'],
                'description' => $entry['description'],
                'debit_account_id' => $debitAccount->id,
                'credit_account_id' => $creditAccount->id,
                'debit_amount' => $entry['amount'],
                'credit_amount' => $entry['amount'],
                'reference_type' => $entry['reference_type'],
                'reference_id' => $entry['reference_id'],
                'notes' => $entry['notes'],
            ]);
        }

        $this->command->info('General Ledger entries seeded successfully.');
    }
}
