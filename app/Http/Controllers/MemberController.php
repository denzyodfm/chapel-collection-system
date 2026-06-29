<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\HugpongBanay;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'hugpong_banay_id' => ['nullable', 'exists:hugpong_banays,id'],
        ]);

        $members = Member::with('hugpongBanay')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('member_id', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('address_purok', 'like', "%{$search}%")
                    ->orWhereHas('hugpongBanay', fn ($query) => $query->where('name', 'like', "%{$search}%"));
            }))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['hugpong_banay_id'] ?? null, fn ($query, $hugpongBanayId) => $query->where('hugpong_banay_id', $hugpongBanayId))
            ->orderBy('full_name')
            ->paginate(12)
            ->withQueryString();

        return view('members.index', [
            'members' => $members,
            'hugpongBanays' => HugpongBanay::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('members.create', [
            'member' => new Member,
            'hugpongBanays' => HugpongBanay::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Member::create($this->validated($request));

        return redirect()->route('members.index')->with('success', 'Member added successfully.');
    }

    public function show(Member $member): View
    {
        $member->load('hugpongBanay');
        $collections = $member->collections()->with('encoder')->latest('collection_date')->paginate(10);
        $totals = $member->collections()->selectRaw('collection_type, SUM(amount) as total')->groupBy('collection_type')->pluck('total', 'collection_type');

        return view('members.show', compact('member', 'collections', 'totals'));
    }

    public function edit(Member $member): View
    {
        return view('members.edit', [
            'member' => $member,
            'hugpongBanays' => HugpongBanay::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Member $member): RedirectResponse
    {
        $member->update($this->validated($request, $member));

        return redirect()->route('members.show', $member)->with('success', 'Member updated successfully.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        abort_if($member->collections()->exists(), 422, 'Members with collection history cannot be deleted.');
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Member deleted successfully.');
    }

    public function balikGasaYear(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);
        $year = (int) ($validated['year'] ?? now()->year);

        $payments = Collection::where('collection_type', Collection::BALIK_GASA)
            ->where('member_id', $member->id)
            ->whereBetween('collection_month', ["{$year}-01", "{$year}-12"])
            ->get()
            ->keyBy('collection_month');

        $months = collect(range(1, 12))->map(function ($month) use ($year, $payments) {
            $key = sprintf('%d-%02d', $year, $month);
            $payment = $payments->get($key);

            return [
                'month' => $key,
                'label' => Carbon::createFromFormat('Y-m', $key)->format('M'),
                'paid' => (bool) $payment,
                'amount' => $payment ? (float) $payment->amount : 0,
                'date' => $payment?->collection_date?->format('M d, Y'),
                'reference_no' => $payment?->reference_no,
            ];
        });

        return response()->json([
            'member' => [
                'id' => $member->id,
                'member_id' => $member->member_id,
                'name' => $member->full_name,
            ],
            'year' => $year,
            'months' => $months,
        ]);
    }

    private function validated(Request $request, ?Member $member = null): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+()\\-\\s]+$/'],
            'address_purok' => ['nullable', 'string', 'max:255'],
            'hugpong_banay_id' => ['required', 'exists:hugpong_banays,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'date_joined' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
    }
}
