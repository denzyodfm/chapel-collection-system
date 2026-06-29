@extends('layouts.app')

@section('page-title', 'Offering Monthly Monitoring')

@section('page-actions')
<a href="{{ route('collections.create', ['collection_type' => \App\Models\Collection::HALAD]) }}" class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Add Offering</a>
@endsection

@section('content')
<section class="mb-5 grid gap-4 lg:grid-cols-[1fr_320px]">
    <form method="GET" class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="grid gap-3 sm:grid-cols-[180px_minmax(240px,1fr)_auto] sm:items-end">
            <label class="grid gap-2 text-sm font-medium text-slate-700">Select Month
                <input name="month" type="month" value="{{ $month }}" class="rounded-lg border border-slate-300 px-4 py-3">
            </label>
            <label class="grid gap-2 text-sm font-medium text-slate-700">Search Offering
                <input type="search" data-table-filter-target="offerings-table" placeholder="Type reference, notes, date, or amount" class="rounded-lg border border-slate-300 px-4 py-3">
            </label>
            <button class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white">View Month</button>
        </div>
    </form>

    <article class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <p class="text-sm font-semibold text-amber-800">{{ $monthLabel }} Offering</p>
        <p class="mt-2 text-3xl font-bold text-sky-950">PHP {{ number_format((float) $totalOffering, 2) }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $offerings->count() }} mass {{ \Illuminate\Support\Str::plural('entry', $offerings->count()) }}</p>
    </article>
</section>

@if (auth()->user()->hasAnyRole(['admin', 'treasurer']))
<section class="mb-5 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4">
        <h2 class="text-lg font-bold text-sky-950">Post Offering After Mass</h2>
        <p class="text-sm text-slate-500">Offering is recorded as one total collection from all members, not per member.</p>
    </div>
    <form method="POST" action="{{ route('offerings.quick-post') }}" class="grid gap-3 lg:grid-cols-[180px_160px_1fr_1fr_auto]">
        @csrf
        <input name="collection_date" type="date" value="{{ now()->format('Y-m-d') }}" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <input name="amount" type="number" min="0.01" step="0.01" placeholder="Offering amount" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <input name="reference_no" placeholder="Reference" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <input name="remarks" placeholder="Offering notes after mass" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <button class="rounded-lg bg-amber-600 px-5 py-3 text-sm font-semibold text-white">Post Offering</button>
    </form>
</section>
@endif

<section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-sky-50 px-5 py-4">
        <div>
            <h2 class="text-lg font-bold text-sky-950">Offering Entries for {{ $monthLabel }}</h2>
            <p class="text-sm text-slate-600">Mass-level totals collected after chapel services.</p>
        </div>
        <p class="text-sm font-semibold text-slate-600">{{ $offerings->count() }} entries</p>
    </div>
    <div class="overflow-x-auto">
        <table id="offerings-table" class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Notes</th><th class="px-4 py-3">Encoded By</th><th class="px-4 py-3 text-right">Amount</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($offerings as $offering)
                    <tr>
                        <td class="px-4 py-3">{{ $offering->collection_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3">{{ $offering->reference_no ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $offering->remarks ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $offering->encoder?->name ?: '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">PHP {{ number_format((float) $offering->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No offerings recorded for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
