<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $currentMonth = $validated['month'] ?? now()->subMonthNoOverflow()->format('Y-m');
        $monthDate = Carbon::createFromFormat('Y-m', $currentMonth);
        $activeMembersCount = Member::active()->count();
        $totals = Collection::query()
            ->includedInTotals()
            ->selectRaw('collection_type, SUM(amount) as total')
            ->groupBy('collection_type')
            ->pluck('total', 'collection_type');

        $paidMemberIds = Collection::query()
            ->includedInTotals()
            ->where('collection_type', Collection::BALIK_GASA)
            ->where('collection_month', $currentMonth)
            ->pluck('member_id');

        $paidMembersCount = $paidMemberIds->unique()->count();
        $monthTotals = Collection::query()
            ->includedInTotals()
            ->selectRaw('collection_type, SUM(amount) as total')
            ->where(function ($query) use ($currentMonth, $monthDate) {
                $query->where(function ($query) use ($currentMonth) {
                    $query->where('collection_type', Collection::BALIK_GASA)
                        ->where('collection_month', $currentMonth);
                })->orWhere(function ($query) use ($monthDate) {
                    $query->whereIn('collection_type', [Collection::DONATION, Collection::HALAD])
                        ->whereBetween('collection_date', [$monthDate->copy()->startOfMonth()->toDateString(), $monthDate->copy()->endOfMonth()->toDateString()]);
                });
            })
            ->groupBy('collection_type')
            ->pluck('total', 'collection_type');

        return view('dashboard.index', [
            'totals' => $totals,
            'currentMonthLabel' => $monthDate->format('F Y'),
            'currentMonth' => $currentMonth,
            'previousDashboardMonth' => $monthDate->copy()->subMonthNoOverflow()->format('Y-m'),
            'nextDashboardMonth' => $monthDate->copy()->addMonthNoOverflow()->format('Y-m'),
            'currentMonthBalikGasa' => Collection::where('collection_type', Collection::BALIK_GASA)
                ->includedInTotals()
                ->where('collection_month', $currentMonth)
                ->sum('amount'),
            'monthTotals' => $monthTotals,
            'fundSummaries' => LedgerController::fundSummaries(),
            'activeMembersCount' => $activeMembersCount,
            'paidMembersCount' => $paidMembersCount,
            'unpaidMembersCount' => max($activeMembersCount - $paidMembersCount, 0),
            'paidRate' => $activeMembersCount > 0 ? round(($paidMembersCount / $activeMembersCount) * 100) : 0,
            'recentExpenses' => Expense::with('encoder')->latest('expense_date')->latest()->limit(6)->get(),
            'recentCollections' => Collection::with(['member', 'encoder'])->includedInTotals()->latest('collection_date')->latest()->limit(8)->get(),
        ]);
    }
}
