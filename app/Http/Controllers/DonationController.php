<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\HugpongBanay;
use App\Models\Member;
use App\Models\MonthLock;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'hugpong_banay_id' => ['nullable', 'exists:hugpong_banays,id'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $selectedHugpongBanayId = $validated['hugpong_banay_id'] ?? null;
        $monthDate = Carbon::createFromFormat('Y-m', $month);

        $members = Member::with('hugpongBanay')
            ->active()
            ->when($selectedHugpongBanayId, fn ($query, $hugpongBanayId) => $query->where('hugpong_banay_id', $hugpongBanayId))
            ->orderBy('full_name')
            ->get();

        $donations = Collection::with('member')
            ->where('collection_type', Collection::DONATION)
            ->whereBetween('collection_date', [$monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()])
            ->whereIn('member_id', $members->pluck('id'))
            ->latest('collection_date')
            ->latest()
            ->get();

        $donationSummary = $donations
            ->groupBy('member_id')
            ->map(fn ($rows) => [
                'total' => $rows->sum('amount'),
                'count' => $rows->count(),
                'last_date' => $rows->sortByDesc('collection_date')->first()?->collection_date,
            ]);

        return view('donations.index', [
            'month' => $month,
            'monthLabel' => $monthDate->format('F Y'),
            'monthLock' => MonthLock::where('lockable_type', Collection::DONATION)->where('month', $month)->first(),
            'hugpongBanays' => HugpongBanay::withCount(['members' => fn ($query) => $query->where('status', 'active')])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'selectedHugpongBanayId' => $selectedHugpongBanayId,
            'selectedHugpongBanay' => $selectedHugpongBanayId ? HugpongBanay::find($selectedHugpongBanayId) : null,
            'members' => $members,
            'donations' => $donations,
            'donationSummary' => $donationSummary,
            'totalDonation' => $donations->sum('amount'),
        ]);
    }

    public function quickPay(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'collection_month' => ['required', 'date_format:Y-m'],
            'collection_date' => ['nullable', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $entryMonth = Carbon::parse($data['collection_date'] ?? now())->format('Y-m');
        if (MonthLock::isLocked(Collection::DONATION, $entryMonth)) {
            return back()->with('error', MonthLock::lockedMessage(Collection::DONATION, $entryMonth));
        }

        Collection::create([
            'member_id' => $member->id,
            'collection_type' => Collection::DONATION,
            'amount' => $data['amount'],
            'collection_date' => $data['collection_date'] ?? now()->toDateString(),
            'collection_month' => null,
            'reference_no' => $data['reference_no'] ?? null,
            'remarks' => $data['remarks'] ?? 'Quick monthly donation for '.$data['collection_month'],
            'encoded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Quick donation recorded.');
    }
}
