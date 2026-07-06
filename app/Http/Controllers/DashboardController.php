<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $currentMonth = now()->format('Y-m');
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
            ->where(function ($query) use ($currentMonth) {
                $query->where(function ($query) use ($currentMonth) {
                    $query->where('collection_type', Collection::BALIK_GASA)
                        ->where('collection_month', $currentMonth);
                })->orWhere(function ($query) {
                    $query->whereIn('collection_type', [Collection::DONATION, Collection::HALAD])
                        ->whereBetween('collection_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
                });
            })
            ->groupBy('collection_type')
            ->pluck('total', 'collection_type');

        return view('dashboard.index', [
            'totals' => $totals,
            'currentMonthLabel' => Carbon::createFromFormat('Y-m', $currentMonth)->format('F Y'),
            'currentMonth' => $currentMonth,
            'currentMonthBalikGasa' => Collection::where('collection_type', Collection::BALIK_GASA)
                ->includedInTotals()
                ->where('collection_month', $currentMonth)
                ->sum('amount'),
            'monthTotals' => $monthTotals,
            'activeMembersCount' => $activeMembersCount,
            'paidMembersCount' => $paidMembersCount,
            'unpaidMembersCount' => max($activeMembersCount - $paidMembersCount, 0),
            'paidRate' => $activeMembersCount > 0 ? round(($paidMembersCount / $activeMembersCount) * 100) : 0,
            'recentExpenses' => Expense::with('encoder')->latest('expense_date')->latest()->limit(6)->get(),
            'recentCollections' => Collection::with(['member', 'encoder'])->includedInTotals()->latest('collection_date')->latest()->limit(8)->get(),
        ]);
    }
}
