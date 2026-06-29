@extends('layouts.app')

@section('page-title', 'Collection Ledger')
@section('eyebrow', 'Funds')

@section('content')
<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($fundSummaries as $summary)
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">{{ $summary['label'] }}</p>
            <p class="mt-2 text-2xl font-bold text-sky-950">PHP {{ number_format($summary['balance'], 2) }}</p>
            <p class="mt-2 text-xs text-slate-500">Credits PHP {{ number_format($summary['credits'], 2) }} / Debits PHP {{ number_format($summary['debits'], 2) }}</p>
        </article>
    @endforeach
</section>

@if (auth()->user()->hasAnyRole(['admin', 'treasurer']))
<section class="mt-6 grid gap-6 xl:grid-cols-2">
    <form method="POST" action="{{ route('ledger.entries.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <h2 class="text-lg font-bold text-sky-950">Post Beginning Balance / Other Source</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <select name="fund_type" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
                @foreach ($fundTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select name="entry_type" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
                <option value="credit">Add to fund</option>
                <option value="debit">Deduct from fund</option>
            </select>
            <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <input name="entry_date" type="date" value="{{ now()->format('Y-m-d') }}" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <input name="reference_no" placeholder="Reference" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <input name="remarks" placeholder="Notes / Source" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        </div>
        <button class="mt-4 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white">Post Ledger Entry</button>
    </form>

    <form method="POST" action="{{ route('ledger.expenses.store') }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        @csrf
        <div class="flex flex-wrap items-end justify-between gap-3">
            <h2 class="text-lg font-bold text-sky-950">Encode Expense</h2>
            <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Expense Date
                <input name="expense_date" type="date" value="{{ now()->format('Y-m-d') }}" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm normal-case tracking-normal text-slate-900">
            </label>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <select name="fund_type" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
                @foreach ($fundTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <div>
                <input name="category" list="expense-categories" placeholder="Expense category" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
                <datalist id="expense-categories">
                    @foreach ($expenseCategories as $category)
                        <option value="{{ $category }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-slate-500">Choose a category or type a new one.</p>
            </div>
            <input name="pay_to" placeholder="Pay to" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <input name="reference_no" placeholder="Reference" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <textarea name="remarks" rows="4" placeholder="Notes" class="rounded-lg border border-slate-300 px-4 py-3 text-sm md:col-span-2"></textarea>
        </div>
        <button class="mt-4 rounded-lg bg-rose-700 px-5 py-3 text-sm font-semibold text-white">Save Expense</button>
    </form>
</section>
@endif

<form method="GET" class="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
    <select name="fund_type" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <option value="">All funds</option>
        @foreach ($fundTypes as $value => $label)
            <option value="{{ $value }}" @selected(request('fund_type') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <input name="date_from" type="date" value="{{ request('date_from') }}" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
    <input name="date_to" type="date" value="{{ request('date_to') }}" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
    <button class="rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white">Filter Ledger</button>
</form>

<section class="mt-5 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Fund</th><th class="px-4 py-3">Source</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Description</th><th class="px-4 py-3 text-right">Credit</th><th class="px-4 py-3 text-right">Debit</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
                <tr>
                    <td class="px-4 py-3">{{ $row['date']?->format('M d, Y') }}</td>
                    <td class="px-4 py-3">{{ $fundTypes[$row['fund_type']] ?? $row['fund_type'] }}</td>
                    <td class="px-4 py-3">{{ $row['source'] }}</td>
                    <td class="px-4 py-3">{{ $row['reference_no'] ?: '-' }}</td>
                    <td class="px-4 py-3">{{ $row['description'] }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-emerald-700">{{ $row['credit'] > 0 ? 'PHP '.number_format($row['credit'], 2) : '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold text-rose-700">{{ $row['debit'] > 0 ? 'PHP '.number_format($row['debit'], 2) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No ledger entries found.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
