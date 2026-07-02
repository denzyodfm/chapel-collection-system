<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\LedgerEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LedgerController extends Controller
{
    public const FUND_TYPES = [
        Collection::BALIK_GASA => 'Balik Gasa',
        Collection::DONATION => 'Donation',
        Collection::HALAD => 'Offering',
        'general' => 'Total Chapel Fund',
    ];

    public const EXPENSE_CATEGORIES = [
        'Repairs',
        'Maintenance',
        'Electric Bill',
        'Water Bill',
        'ICP Share from BG',
        'Miscellaneous',
    ];

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'fund_type' => ['nullable', Rule::in(array_keys(self::FUND_TYPES))],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $fundType = $validated['fund_type'] ?? null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $collectionRows = Collection::with('member')
            ->when($fundType && $fundType !== 'general', fn ($query) => $query->where('collection_type', $fundType))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('collection_date', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('collection_date', '<=', $date))
            ->get()
            ->map(fn (Collection $collection) => [
                'date' => $collection->collection_date,
                'fund_type' => $collection->collection_type,
                'source' => 'Collection',
                'description' => trim($collection->typeLabel().' - '.($collection->member?->full_name ?? 'All members / Mass collection')),
                'reference_no' => $collection->reference_no,
                'credit' => (float) $collection->amount,
                'debit' => 0.0,
                'remarks' => $collection->remarks,
            ]);

        $manualRows = LedgerEntry::query()
            ->when($fundType && $fundType !== 'general', fn ($query) => $query->where('fund_type', $fundType))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('entry_date', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('entry_date', '<=', $date))
            ->get()
            ->map(fn (LedgerEntry $entry) => [
                'id' => $entry->id,
                'row_type' => 'manual',
                'date' => $entry->entry_date,
                'fund_type' => $entry->fund_type,
                'source' => $entry->entry_type === LedgerEntry::CREDIT ? 'Manual Credit' : 'Manual Debit',
                'description' => $entry->remarks ?: 'Manual ledger entry',
                'reference_no' => $entry->reference_no,
                'credit' => $entry->entry_type === LedgerEntry::CREDIT ? (float) $entry->amount : 0.0,
                'debit' => $entry->entry_type === LedgerEntry::DEBIT ? (float) $entry->amount : 0.0,
                'remarks' => $entry->remarks,
            ]);

        $expenseRows = Expense::query()
            ->when($fundType && $fundType !== 'general', fn ($query) => $query->where('fund_type', $fundType))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('expense_date', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('expense_date', '<=', $date))
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'row_type' => 'expense',
                'date' => $expense->expense_date,
                'fund_type' => $expense->fund_type,
                'source' => 'Expense',
                'description' => $expense->pay_to ? "{$expense->category} - {$expense->pay_to}" : $expense->category,
                'reference_no' => $expense->reference_no,
                'credit' => 0.0,
                'debit' => (float) $expense->amount,
                'remarks' => $expense->remarks,
            ]);

        $rows = $collectionRows
            ->concat($manualRows)
            ->concat($expenseRows)
            ->sortBy(fn ($row) => $row['date']?->format('Y-m-d').$row['source'])
            ->values();

        $fundSummaries = collect(self::FUND_TYPES)->mapWithKeys(function ($label, $type) {
            $collectionCredits = Collection::query()
                ->when($type !== 'general', fn ($query) => $query->where('collection_type', $type))
                ->sum('amount');
            $manualCredits = LedgerEntry::query()
                ->when($type !== 'general', fn ($query) => $query->where('fund_type', $type))
                ->where('entry_type', LedgerEntry::CREDIT)
                ->sum('amount');
            $manualDebits = LedgerEntry::query()
                ->when($type !== 'general', fn ($query) => $query->where('fund_type', $type))
                ->where('entry_type', LedgerEntry::DEBIT)
                ->sum('amount');
            $expenses = Expense::query()
                ->when($type !== 'general', fn ($query) => $query->where('fund_type', $type))
                ->sum('amount');

            return [$type => [
                'label' => $label,
                'credits' => (float) $collectionCredits + (float) $manualCredits,
                'debits' => (float) $manualDebits + (float) $expenses,
                'balance' => (float) $collectionCredits + (float) $manualCredits - (float) $manualDebits - (float) $expenses,
            ]];
        });

        return view('ledger.index', [
            'rows' => $rows,
            'fundTypes' => self::FUND_TYPES,
            'fundSummaries' => $fundSummaries,
            'expenseCategories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fund_type' => ['required', Rule::in(array_keys(self::FUND_TYPES))],
            'entry_type' => ['required', Rule::in([LedgerEntry::CREDIT, LedgerEntry::DEBIT])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'entry_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['encoded_by'] = $request->user()->id;

        LedgerEntry::create($data);

        return redirect()->route('ledger.index')->with('success', 'Ledger entry posted.');
    }

    public function destroyEntry(Request $request, LedgerEntry $entry): RedirectResponse
    {
        $request->validate([
            'delete_confirmation' => ['required', 'string', 'in:DELETE'],
        ], [
            'delete_confirmation.in' => 'Type DELETE to confirm deleting this ledger entry.',
        ]);

        $entry->delete();

        return redirect()->route('ledger.index')->with('success', 'Ledger entry deleted.');
    }

    public function storeExpense(Request $request): RedirectResponse
    {
        $data = $this->validatedExpense($request);
        $data['encoded_by'] = $request->user()->id;

        Expense::create($data);

        return redirect()->route('ledger.index')->with('success', 'Expense encoded and deducted from chapel funds.');
    }

    public function editExpense(Expense $expense): View
    {
        return view('ledger.edit-expense', [
            'expense' => $expense,
            'fundTypes' => self::FUND_TYPES,
            'expenseCategories' => self::EXPENSE_CATEGORIES,
        ]);
    }

    public function updateExpense(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validatedExpense($request));

        return redirect()->route('ledger.index')->with('success', 'Expense updated.');
    }

    public function destroyExpense(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('ledger.index')->with('success', 'Expense deleted.');
    }

    private function validatedExpense(Request $request): array
    {
        return $request->validate([
            'fund_type' => ['required', Rule::in(array_keys(self::FUND_TYPES))],
            'category' => ['required', 'string', 'max:150'],
            'pay_to' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
