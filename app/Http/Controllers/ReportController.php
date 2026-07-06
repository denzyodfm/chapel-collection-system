<?php

namespace App\Http\Controllers;

use App\Models\Collection;
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
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $memberId = $validated['member_id'] ?? null;

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

        return view('reports.print', [
            'collections' => $collections,
            'summary' => $summary,
            'balikGasaShares' => $balikGasaShares,
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
}
