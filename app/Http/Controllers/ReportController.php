<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\LedgerEntry;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'member_id' => ['nullable', 'exists:members,id'],
            'statement_basis' => ['nullable', Rule::in(['as_of', 'monthly'])],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $memberId = $validated['member_id'] ?? null;
        $statementBasis = $validated['statement_basis'] ?? 'as_of';

        $monthly = $this->monthlyQuery($month)
            ->with('member')
            ->latest('collection_date')
            ->get();

        $summary = $this->monthlyQuery($month)
            ->selectRaw('collection_type, SUM(amount) as total')
            ->groupBy('collection_type')
            ->pluck('total', 'collection_type');
        $balikGasaShares = $this->balikGasaSharesByHugpongBanay($month);
        $balikGasaSubsummary = $this->balikGasaSubsummaryByHugpongBanay($month);
        $financialStatements = $this->financialStatements($month, $statementBasis);

        $memberHistory = collect();
        if ($memberId) {
            $memberHistory = Collection::with('member')
                ->where('member_id', $memberId)
                ->latest('collection_date')
                ->get();
        }

        return view('reports.index', [
            'month' => $month,
            'members' => Member::orderBy('full_name')->get(),
            'monthly' => $monthly,
            'summary' => $summary,
            'balikGasaShares' => $balikGasaShares,
            'balikGasaSubsummary' => $balikGasaSubsummary,
            'financialStatements' => $financialStatements,
            'memberHistory' => $memberHistory,
            'selectedMember' => $memberId ? Member::find($memberId) : null,
            'types' => Collection::TYPES,
        ]);
    }

    public function printBalikGasaSubsummary(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');

        return view('reports.balik-gasa-subsummary-print', [
            'month' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
            'balikGasaSubsummary' => $this->balikGasaSubsummaryByHugpongBanay($month),
        ]);
    }

    public function print(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'collection_type' => ['nullable', Rule::in(array_keys(Collection::TYPES))],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $collectionType = $validated['collection_type'] ?? null;

        $collections = $this->monthlyQuery($month)
            ->with(['member', 'encoder'])
            ->when($collectionType, fn ($query, $type) => $query->where('collection_type', $type))
            ->orderBy('collection_type')
            ->orderBy('collection_date')
            ->get();

        $summary = $collections
            ->groupBy('collection_type')
            ->map(fn ($rows) => $rows->sum('amount'));
        $balikGasaShares = $this->balikGasaSharesByHugpongBanay($month);
        $balikGasaSubsummary = $this->balikGasaSubsummaryByHugpongBanay($month);

        return view('reports.print', [
            'collections' => $collections,
            'summary' => $summary,
            'balikGasaShares' => $balikGasaShares,
            'balikGasaSubsummary' => $balikGasaSubsummary,
            'month' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
            'types' => Collection::TYPES,
            'collectionType' => $collectionType,
        ]);
    }

    public function csv(Request $request)
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = $validated['month'] ?? now()->format('Y-m');

        $rows = $this->monthlyQuery($month)
            ->with(['member', 'encoder'])
            ->latest('collection_date')
            ->get();

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Member ID', 'Member', 'Type', 'Amount', 'Date', 'Month', 'Reference', 'Remarks', 'Encoded By']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->member?->member_id ?? '',
                    $row->member?->full_name ?? 'All members / Mass collection',
                    $row->typeLabel(),
                    $row->amount,
                    $row->collection_date?->format('Y-m-d'),
                    $row->collection_month,
                    $row->reference_no,
                    $row->remarks,
                    $row->encoder?->name,
                ]);
            }

            fclose($handle);
        };

        return Response::streamDownload($callback, "chapel-collections-{$month}.csv", ['Content-Type' => 'text/csv']);
    }

    private function monthlyQuery(string $month)
    {
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        return Collection::query()
            ->includedInTotals()
            ->where(function ($query) use ($month, $start, $end) {
                $query->where(function ($query) use ($month) {
                    $query->where('collection_type', Collection::BALIK_GASA)
                        ->where('collection_month', $month);
                })->orWhere(function ($query) use ($start, $end) {
                    $query->whereIn('collection_type', [Collection::DONATION, Collection::HALAD])
                        ->whereBetween('collection_date', [$start, $end]);
                });
            });
    }

    private function balikGasaSharesByHugpongBanay(string $month): array
    {
        $rows = Collection::with('member.hugpongBanay')
            ->includedInTotals()
            ->where('collection_type', Collection::BALIK_GASA)
            ->where('collection_month', $month)
            ->get()
            ->groupBy(fn (Collection $collection) => $collection->member?->hugpongBanay?->name ?: 'No Hugpong Banay')
            ->map(fn ($collections, $name) => [
                'name' => $name,
                'members_paid' => $collections->pluck('member_id')->filter()->unique()->count(),
                'total' => (float) $collections->sum('amount'),
                'icp_share' => (float) $collections->sum('amount') * 0.60,
                'chapel_share' => (float) $collections->sum('amount') * 0.40,
            ])
            ->sortBy('name')
            ->values();

        $grandTotal = (float) $rows->sum('total');

        return [
            'rows' => $rows,
            'grand' => [
                'members_paid' => (int) $rows->sum('members_paid'),
                'total' => $grandTotal,
                'icp_share' => $grandTotal * 0.60,
                'chapel_share' => $grandTotal * 0.40,
            ],
        ];
    }

    private function balikGasaSubsummaryByHugpongBanay(string $month): array
    {
        $groups = Collection::with('member.hugpongBanay')
            ->includedInTotals()
            ->where('collection_type', Collection::BALIK_GASA)
            ->where('collection_month', $month)
            ->get()
            ->groupBy(fn (Collection $collection) => $collection->member?->hugpongBanay?->name ?: 'No Hugpong Banay')
            ->map(function ($collections, $name) {
                $entries = $collections
                    ->sortBy(fn (Collection $collection) => $collection->member?->full_name ?? '')
                    ->values();
                $total = (float) $entries->sum('amount');

                return [
                    'name' => $name,
                    'entries' => $entries,
                    'members_paid' => $entries->pluck('member_id')->filter()->unique()->count(),
                    'total' => $total,
                    'icp_share' => $total * 0.60,
                    'chapel_share' => $total * 0.40,
                ];
            })
            ->sortBy('name')
            ->values();

        $grandTotal = (float) $groups->sum('total');

        return [
            'groups' => $groups,
            'grand' => [
                'members_paid' => (int) $groups->sum('members_paid'),
                'total' => $grandTotal,
                'icp_share' => $grandTotal * 0.60,
                'chapel_share' => $grandTotal * 0.40,
            ],
        ];
    }

    private function financialStatements(string $month, string $basis = 'as_of'): array
    {
        $monthDate = Carbon::createFromFormat('Y-m', $month);
        $periodStart = $monthDate->copy()->startOfMonth();
        $periodEnd = $monthDate->copy()->endOfMonth();
        $isMonthly = $basis === 'monthly';
        $fundTypes = [
            Collection::BALIK_GASA => 'Balik Gasa Fund',
            Collection::DONATION => 'Donation Fund',
            Collection::HALAD => 'Offering Fund',
            'general' => 'General Chapel Fund Adjustments',
        ];

        $collectionRows = Collection::with('member')
            ->includedInTotals()
            ->when($isMonthly, function ($query) use ($month, $periodStart, $periodEnd) {
                $query->where(function ($query) use ($month, $periodStart, $periodEnd) {
                    $query->where(function ($query) use ($month) {
                        $query->where('collection_type', Collection::BALIK_GASA)
                            ->where('collection_month', $month);
                    })->orWhere(function ($query) use ($periodStart, $periodEnd) {
                        $query->whereIn('collection_type', [Collection::DONATION, Collection::HALAD])
                            ->whereBetween('collection_date', [$periodStart->toDateString(), $periodEnd->toDateString()]);
                    });
                });
            }, fn ($query) => $query->whereDate('collection_date', '<=', $periodEnd->toDateString()))
            ->orderBy('collection_date')
            ->get();

        $collectionCredits = $collectionRows
            ->groupBy('collection_type')
            ->map(fn ($rows) => $rows->sum('amount'));

        $ledgerRows = LedgerEntry::query()
            ->when($isMonthly, fn ($query) => $query->whereBetween('entry_date', [$periodStart->toDateString(), $periodEnd->toDateString()]), fn ($query) => $query->whereDate('entry_date', '<=', $periodEnd->toDateString()))
            ->orderBy('entry_date')
            ->get();

        $manualCreditRows = $ledgerRows->where('entry_type', LedgerEntry::CREDIT);
        $manualDebitRows = $ledgerRows->where('entry_type', LedgerEntry::DEBIT);

        $manualCredits = $manualCreditRows
            ->groupBy('fund_type')
            ->map(fn ($rows) => $rows->sum('amount'));

        $manualDebits = $manualDebitRows
            ->groupBy('fund_type')
            ->map(fn ($rows) => $rows->sum('amount'));

        $disbursementRows = Expense::query()
            ->when($isMonthly, fn ($query) => $query->whereBetween('expense_date', [$periodStart->toDateString(), $periodEnd->toDateString()]), fn ($query) => $query->whereDate('expense_date', '<=', $periodEnd->toDateString()))
            ->orderBy('expense_date')
            ->get();

        $disbursements = $disbursementRows
            ->groupBy('fund_type')
            ->map(fn ($rows) => $rows->sum('amount'));

        $details = collect()
            ->concat($collectionRows->map(fn (Collection $collection) => [
                'date' => $collection->collection_date,
                'fund' => Collection::TYPES[$collection->collection_type] ?? $collection->collection_type,
                'source' => 'Collection',
                'description' => $collection->member?->full_name ?: 'All members / Mass collection',
                'reference' => $collection->reference_no ?: '-',
                'debit' => 0.0,
                'credit' => (float) $collection->amount,
            ]))
            ->concat($manualCreditRows->map(fn (LedgerEntry $entry) => [
                'date' => $entry->entry_date,
                'fund' => $fundTypes[$entry->fund_type] ?? $entry->fund_type,
                'source' => 'Manual Credit',
                'description' => $entry->remarks ?: 'Manual ledger entry',
                'reference' => $entry->reference_no ?: '-',
                'debit' => 0.0,
                'credit' => (float) $entry->amount,
            ]))
            ->concat($manualDebitRows->map(fn (LedgerEntry $entry) => [
                'date' => $entry->entry_date,
                'fund' => $fundTypes[$entry->fund_type] ?? $entry->fund_type,
                'source' => 'Manual Debit',
                'description' => $entry->remarks ?: 'Manual ledger entry',
                'reference' => $entry->reference_no ?: '-',
                'debit' => (float) $entry->amount,
                'credit' => 0.0,
            ]))
            ->concat($disbursementRows->map(fn (Expense $expense) => [
                'date' => $expense->expense_date,
                'fund' => $fundTypes[$expense->fund_type] ?? $expense->fund_type,
                'source' => 'Disbursement',
                'description' => $expense->pay_to ? "{$expense->category} - {$expense->pay_to}" : $expense->category,
                'reference' => $expense->reference_no ?: '-',
                'debit' => (float) $expense->amount,
                'credit' => 0.0,
            ]))
            ->sortBy(fn ($row) => $row['date']?->format('Y-m-d').$row['source'])
            ->values();

        $fundRows = collect($fundTypes)->map(function (string $label, string $type) use ($collectionCredits, $manualCredits, $manualDebits, $disbursements) {
            $credits = (float) ($manualCredits[$type] ?? 0);
            if ($type !== 'general') {
                $credits += (float) ($collectionCredits[$type] ?? 0);
            }

            $debits = (float) ($manualDebits[$type] ?? 0) + (float) ($disbursements[$type] ?? 0);

            return [
                'type' => $type,
                'label' => $label,
                'credits' => $credits,
                'debits' => $debits,
                'balance' => $credits - $debits,
            ];
        })->values();

        $totalCredits = (float) $fundRows->sum('credits');
        $totalDebits = (float) $fundRows->sum('debits');
        $cashBalance = $totalCredits - $totalDebits;

        $trialRows = [
            ['account' => 'Cash / Chapel Funds', 'debit' => max($cashBalance, 0), 'credit' => max($cashBalance * -1, 0)],
            ['account' => 'Disbursements and Fund Deductions', 'debit' => $totalDebits, 'credit' => 0.0],
            ['account' => 'Collections and Other Sources', 'debit' => 0.0, 'credit' => $totalCredits],
        ];

        return [
            'period_end' => $periodEnd,
            'period_start' => $periodStart,
            'basis' => $basis,
            'basis_label' => $isMonthly
                ? 'For '.$periodStart->format('F Y')
                : 'As of '.$periodEnd->format('F d, Y'),
            'fund_rows' => $fundRows,
            'trial_rows' => $trialRows,
            'details' => $details,
            'trial_totals' => [
                'debit' => collect($trialRows)->sum('debit'),
                'credit' => collect($trialRows)->sum('credit'),
            ],
            'balance_sheet' => [
                'cash_balance' => $cashBalance,
                'fund_balance' => $cashBalance,
            ],
        ];
    }
}
