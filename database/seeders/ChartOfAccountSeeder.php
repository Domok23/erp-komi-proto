<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $this->command->warn('No company found. Skipping Chart of Accounts seeder.');
            return;
        }

        // Assets
        $cash = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-1001',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'balance' => 50000000,
            'is_active' => true,
        ]);

        $bank = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-1002',
            'account_name' => 'Bank BCA',
            'account_type' => 'asset',
            'balance' => 150000000,
            'is_active' => true,
        ]);

        $accountsReceivable = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-2001',
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'balance' => 75000000,
            'is_active' => true,
        ]);

        $inventoryRaw = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-3001',
            'account_name' => 'Raw Materials Inventory',
            'account_type' => 'asset',
            'balance' => 100000000,
            'is_active' => true,
        ]);

        $inventoryWIP = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-3002',
            'account_name' => 'Work in Progress Inventory',
            'account_type' => 'asset',
            'balance' => 50000000,
            'is_active' => true,
        ]);

        $inventoryFinished = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-3003',
            'account_name' => 'Finished Goods Inventory',
            'account_type' => 'asset',
            'balance' => 80000000,
            'is_active' => true,
        ]);

        $equipment = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '1-4001',
            'account_name' => 'Equipment',
            'account_type' => 'asset',
            'balance' => 200000000,
            'is_active' => true,
        ]);

        // Liabilities
        $accountsPayable = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '2-1001',
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'balance' => 60000000,
            'is_active' => true,
        ]);

        $taxPayable = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '2-2001',
            'account_name' => 'Tax Payable',
            'account_type' => 'liability',
            'balance' => 15000000,
            'is_active' => true,
        ]);

        // Equity
        $capital = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '3-1001',
            'account_name' => 'Owner Capital',
            'account_type' => 'equity',
            'balance' => 500000000,
            'is_active' => true,
        ]);

        $retainedEarnings = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '3-2001',
            'account_name' => 'Retained Earnings',
            'account_type' => 'equity',
            'balance' => 80000000,
            'is_active' => true,
        ]);

        // Revenue
        $salesRevenue = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '4-1001',
            'account_name' => 'Sales Revenue',
            'account_type' => 'revenue',
            'balance' => 0,
            'is_active' => true,
        ]);

        $otherRevenue = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '4-2001',
            'account_name' => 'Other Revenue',
            'account_type' => 'revenue',
            'balance' => 0,
            'is_active' => true,
        ]);

        // Expenses
        $materialCost = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '5-1001',
            'account_name' => 'Material Cost',
            'account_type' => 'expense',
            'balance' => 0,
            'is_active' => true,
        ]);

        $laborCost = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '5-1002',
            'account_name' => 'Labor Cost',
            'account_type' => 'expense',
            'balance' => 0,
            'is_active' => true,
        ]);

        $overhead = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '5-1003',
            'account_name' => 'Overhead',
            'account_type' => 'expense',
            'balance' => 0,
            'is_active' => true,
        ]);

        $shipping = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '5-1004',
            'account_name' => 'Shipping Cost',
            'account_type' => 'expense',
            'balance' => 0,
            'is_active' => true,
        ]);

        $utilities = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '5-2001',
            'account_name' => 'Utilities',
            'account_type' => 'expense',
            'balance' => 0,
            'is_active' => true,
        ]);

        $rent = ChartOfAccount::create([
            'company_id' => $company->id,
            'account_code' => '5-2002',
            'account_name' => 'Rent',
            'account_type' => 'expense',
            'balance' => 0,
            'is_active' => true,
        ]);

        $this->command->info('Chart of Accounts seeded successfully.');
    }
}
