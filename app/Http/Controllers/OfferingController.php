<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferingController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $month = $validated['month'] ?? now()->format('Y-m');
        $monthDate = Carbon::createFromFormat('Y-m', $month);

        $offerings = Collection::with('encoder')
            ->where('collection_type', Collection::HALAD)
            ->whereBetween('collection_date', [$monthDate->copy()->startOfMonth(), $monthDate->copy()->endOfMonth()])
            ->latest('collection_date')
            ->latest()
            ->get();

        return view('offerings.index', [
            'month' => $month,
            'monthLabel' => $monthDate->format('F Y'),
            'offerings' => $offerings,
            'totalOffering' => $offerings->sum('amount'),
        ]);
    }

    public function quickPost(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        Collection::create([
            'member_id' => null,
            'collection_type' => Collection::HALAD,
            'amount' => $data['amount'],
            'collection_date' => $data['collection_date'],
            'collection_month' => null,
            'reference_no' => $data['reference_no'] ?? null,
            'remarks' => $data['remarks'] ?? 'Offering after mass',
            'encoded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Offering posted.');
    }
}
