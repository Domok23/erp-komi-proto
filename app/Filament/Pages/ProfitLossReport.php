<?php

namespace App\Filament\Pages;

use App\Models\GeneralLedger;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ProfitLossReport extends Page
{
    protected static ?string $navigationLabel = 'Profit/Loss Report';

    protected static ?int $navigationSort = 6;

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Finance & Invoices';
    }

    protected string $view = 'filament.pages.profit-loss-report';

    public ?string $startDate = null;

    public ?string $endDate = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->endOfMonth()->toDateString();
    }

    public function getRevenue(): float
    {
        return GeneralLedger::whereBetween('entry_date', [$this->startDate, $this->endDate])
            ->whereHas('creditAccount', function ($query) {
                $query->where('account_type', 'revenue');
            })
            ->sum('credit_amount');
    }

    public function getExpense(): float
    {
        return GeneralLedger::whereBetween('entry_date', [$this->startDate, $this->endDate])
            ->whereHas('debitAccount', function ($query) {
                $query->where('account_type', 'expense');
            })
            ->sum('debit_amount');
    }

    public function getGrossProfit(): float
    {
        return $this->getRevenue() - $this->getExpense();
    }

    public function getProfitMargin(): float
    {
        $revenue = $this->getRevenue();

        return $revenue > 0 ? ($this->getGrossProfit() / $revenue) * 100 : 0;
    }

    public function getRevenueBreakdown(): array
    {
        return GeneralLedger::whereBetween('entry_date', [$this->startDate, $this->endDate])
            ->whereHas('creditAccount', function ($query) {
                $query->where('account_type', 'revenue');
            })
            ->select('credit_account_id', DB::raw('SUM(credit_amount) as total'))
            ->with('creditAccount')
            ->groupBy('credit_account_id')
            ->get()
            ->toArray();
    }

    public function getExpenseBreakdown(): array
    {
        return GeneralLedger::whereBetween('entry_date', [$this->startDate, $this->endDate])
            ->whereHas('debitAccount', function ($query) {
                $query->where('account_type', 'expense');
            })
            ->select('debit_account_id', DB::raw('SUM(debit_amount) as total'))
            ->with('debitAccount')
            ->groupBy('debit_account_id')
            ->get()
            ->toArray();
    }
}
