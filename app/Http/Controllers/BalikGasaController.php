<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\HugpongBanay;
use App\Models\Member;
use App\Models\MonthLock;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BalikGasaController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'hugpong_banay_id' => ['nullable', 'exists:hugpong_banays,id'],
        ]);
        $month = $validated['month'] ?? now()->format('Y-m');
        $selectedHugpongBanayId = $validated['hugpong_banay_id'] ?? null;

        $members = Member::with('hugpongBanay')
            ->active()
            ->when($selectedHugpongBanayId, fn ($query, $hugpongBanayId) => $query->where('hugpong_banay_id', $hugpongBanayId))
            ->orderBy('full_name')
            ->get();

        $monthLock = MonthLock::where('lockable_type', Collection::BALIK_GASA)->where('month', $month)->first();
        $activeMembersCount = $members->count();

        $payments = Collection::where('collection_type', Collection::BALIK_GASA)
            ->where('collection_month', $month)
            ->whereIn('member_id', $members->pluck('id'))
            ->get()
            ->keyBy('member_id');
        $displayMembers = $monthLock
            ? $members->filter(fn (Member $member) => $payments->has($member->id))->values()
            : $members;
        $balikGasaTotal = $payments
            ->filter(fn (Collection $payment) => ! $payment->excluded_from_totals)
            ->sum(fn (Collection $payment) => (float) $payment->amount);

        return view('balik-gasa.index', [
            'month' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->format('F Y'),
            'monthLock' => $monthLock,
            'balikGasaTotal' => $balikGasaTotal,
            'balikGasaIcpShare' => $balikGasaTotal * 0.60,
            'balikGasaChapelShare' => $balikGasaTotal * 0.40,
            'activeMembersCount' => $activeMembersCount,
            'hugpongBanays' => HugpongBanay::withCount(['members' => fn ($query) => $query->where('status', 'active')])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
            'selectedHugpongBanayId' => $selectedHugpongBanayId,
            'selectedHugpongBanay' => $selectedHugpongBanayId ? HugpongBanay::find($selectedHugpongBanayId) : null,
            'members' => $displayMembers,
            'payments' => $payments,
        ]);
    }

    public function quickPay(Request $request, Member $member): RedirectResponse
    {
        $data = $request->validate([
            'collection_month' => ['required', 'date_format:Y-m'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        if ($member->status !== 'active') {
            throw ValidationException::withMessages([
                'member' => 'Only active members can receive quick Balik Gasa payments.',
            ]);
        }

        if (MonthLock::isLocked(Collection::BALIK_GASA, $data['collection_month'])) {
            return back()->with('error', MonthLock::lockedMessage(Collection::BALIK_GASA, $data['collection_month']));
        }

        $exists = Collection::where('collection_type', Collection::BALIK_GASA)
            ->where('member_id', $member->id)
            ->where('collection_month', $data['collection_month'])
            ->exists();

        if ($exists) {
            return back()->with('error', "{$member->full_name} already contributed for {$data['collection_month']}.");
        }

        try {
            Collection::create([
                'member_id' => $member->id,
                'collection_type' => Collection::BALIK_GASA,
                'amount' => $data['amount'],
                'collection_date' => $data['collection_date'],
                'collection_month' => $data['collection_month'],
                'remarks' => 'Quick Balik Gasa contribution',
                'encoded_by' => $request->user()->id,
            ]);
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                return back()->with('error', "{$member->full_name} already contributed for {$data['collection_month']}.");
            }

            throw $exception;
        }

        return back()->with('success', 'Quick contribution recorded.');
    }
}
