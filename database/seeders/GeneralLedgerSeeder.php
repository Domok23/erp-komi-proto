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

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Balanced sample entries spanning current month and previous month
        $entries = [
            // --- Previous Month Entries ---
            [
                'entry_number' => 'GL-'.$lastMonth->format('Ym').'05-001',
                'entry_date' => $lastMonth->copy()->addDays(4)->toDateString(),
                'description' => 'Sales Revenue - Order SO-PREV-001',
                'debit_account' => 'Accounts Receivable',
                'credit_account' => 'Sales Revenue',
                'amount' => 120000000,
                'reference_type' => 'invoice',
                'reference_id' => 1,
                'notes' => 'Sales to Customer ABC',
            ],
            [
                'entry_number' => 'GL-'.$lastMonth->format('Ym').'10-001',
                'entry_date' => $lastMonth->copy()->addDays(9)->toDateString(),
                'description' => 'Material Cost - Fabric & Zippers',
                'debit_account' => 'Material Cost',
                'credit_account' => 'Accounts Payable',
                'amount' => 45000000,
                'reference_type' => 'po',
                'reference_id' => 1,
                'notes' => 'Raw materials purchase for sample line',
            ],
            [
                'entry_number' => 'GL-'.$lastMonth->format('Ym').'20-001',
                'entry_date' => $lastMonth->copy()->addDays(19)->toDateString(),
                'description' => 'Labor Cost - Factory Wages',
                'debit_account' => 'Labor Cost',
                'credit_account' => 'Bank BCA',
                'amount' => 30000000,
                'reference_type' => 'payment',
                'reference_id' => 1,
                'notes' => 'Monthly operator salary payment',
            ],

            // --- Current Month Entries (Active Display in Profit/Loss Report) ---
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'02-001',
                'entry_date' => $thisMonth->copy()->addDays(1)->toDateString(),
                'description' => 'Sales Revenue - Order SO-2026-001 (Nike Backpack)',
                'debit_account' => 'Accounts Receivable',
                'credit_account' => 'Sales Revenue',
                'amount' => 150000000,
                'reference_type' => 'invoice',
                'reference_id' => 1,
                'notes' => 'Down payment & invoice for Nike order',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'03-001',
                'entry_date' => $thisMonth->copy()->addDays(2)->toDateString(),
                'description' => 'Material Cost - Purchase PO-SUP-2026-001',
                'debit_account' => 'Material Cost',
                'credit_account' => 'Accounts Payable',
                'amount' => 50000000,
                'reference_type' => 'po',
                'reference_id' => 1,
                'notes' => 'Raw materials batch 1',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'05-001',
                'entry_date' => $thisMonth->copy()->addDays(4)->toDateString(),
                'description' => 'Labor Cost - Production & Sewing wages',
                'debit_account' => 'Labor Cost',
                'credit_account' => 'Bank BCA',
                'amount' => 35000000,
                'reference_type' => 'payment',
                'reference_id' => 1,
                'notes' => 'Bi-weekly labor cost allocation',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'06-001',
                'entry_date' => $thisMonth->copy()->addDays(5)->toDateString(),
                'description' => 'Overhead - Factory Utilities (Electricity & Water)',
                'debit_account' => 'Utilities',
                'credit_account' => 'Bank BCA',
                'amount' => 8000000,
                'reference_type' => 'payment',
                'reference_id' => 2,
                'notes' => 'Factory PLN and PDAM bill',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'07-001',
                'entry_date' => $thisMonth->copy()->addDays(6)->toDateString(),
                'description' => 'Shipping Cost - Export Freight Forwarding',
                'debit_account' => 'Shipping Cost',
                'credit_account' => 'Cash',
                'amount' => 12000000,
                'reference_type' => 'invoice',
                'reference_id' => 2,
                'notes' => 'International ocean freight shipment',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'08-001',
                'entry_date' => $thisMonth->copy()->addDays(7)->toDateString(),
                'description' => 'Rent Payment - Factory Facility',
                'debit_account' => 'Rent',
                'credit_account' => 'Bank BCA',
                'amount' => 25000000,
                'reference_type' => 'payment',
                'reference_id' => 3,
                'notes' => 'Factory space rental',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'09-001',
                'entry_date' => $thisMonth->copy()->addDays(8)->toDateString(),
                'description' => 'Sales Revenue - Order SO-2026-002 (Adidas Tote Bag)',
                'debit_account' => 'Accounts Receivable',
                'credit_account' => 'Sales Revenue',
                'amount' => 200000000,
                'reference_type' => 'invoice',
                'reference_id' => 3,
                'notes' => 'Invoice for Adidas contract',
            ],
            [
                'entry_number' => 'GL-'.$thisMonth->format('Ym').'10-001',
                'entry_date' => $thisMonth->copy()->addDays(9)->toDateString(),
                'description' => 'Material Cost - Purchase PO-SUP-2026-002',
                'debit_account' => 'Material Cost',
                'credit_account' => 'Accounts Payable',
                'amount' => 75000000,
                'reference_type' => 'po',
                'reference_id' => 2,
                'notes' => 'Accessories and hardware purchase',
            ],
        ];

        foreach ($entries as $entry) {
            $debitAccount = $accounts->get($entry['debit_account']);
            $creditAccount = $accounts->get($entry['credit_account']);

            if (! $debitAccount || ! $creditAccount) {
                $this->command->warn("Skipping entry {$entry['entry_number']}: Missing accounts");

                continue;
            }

            GeneralLedger::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'entry_number' => $entry['entry_number'],
                ],
                [
                    'entry_date' => $entry['entry_date'],
                    'description' => $entry['description'],
                    'debit_account_id' => $debitAccount->id,
                    'credit_account_id' => $creditAccount->id,
                    'debit_amount' => $entry['amount'],
                    'credit_amount' => $entry['amount'],
                    'reference_type' => $entry['reference_type'],
                    'reference_id' => $entry['reference_id'],
                    'notes' => $entry['notes'],
                ]
            );
        }

        $this->command->info('General Ledger entries seeded successfully.');
    }
}
