@extends('layouts.app')

@section('page-title', 'Dashboard')
@section('eyebrow', 'Overview')

@section('content')
@php
    $cards = [
        ['Balik Gasa', $totals[\App\Models\Collection::BALIK_GASA] ?? 0, 'bg-sky-900 text-white', 'All-time monthly pledges', route('balik-gasa.index', ['month' => $currentMonth])],
        ['Donation', $totals[\App\Models\Collection::DONATION] ?? 0, 'bg-white text-slate-900', 'Optional gifts recorded', route('donations.index', ['month' => $currentMonth])],
        ['Offering', $totals[\App\Models\Collection::HALAD] ?? 0, 'bg-white text-slate-900', 'Mass offerings recorded', route('offerings.index', ['month' => $currentMonth])],
        ["{$currentMonthLabel} Balik Gasa", $currentMonthBalikGasa, 'bg-amber-100 text-amber-950', "{$paidMembersCount} of {$activeMembersCount} active members paid", route('balik-gasa.index', ['month' => $currentMonth])],
    ];
@endphp

<form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <label class="grid gap-2 text-sm font-medium text-slate-700">Dashboard Month
        <input name="month" type="month" value="{{ $currentMonth }}" class="rounded-lg border border-slate-300 px-4 py-3">
    </label>
    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white"><x-icon name="filter" class="h-4 w-4" /> View Month</button>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('dashboard', ['month' => $previousDashboardMonth]) }}" class="rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Previous Month</a>
        <a href="{{ route('dashboard', ['month' => $nextDashboardMonth]) }}" class="rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Next Month</a>
    </div>
</form>

<section class="overflow-hidden rounded-lg border border-sky-100 bg-white shadow-sm">
    <div class="grid lg:grid-cols-[1.15fr_.85fr]">
        <div class="bg-sky-900 p-6 text-white sm:p-8">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-200">{{ $currentMonthLabel }}</p>
            <h2 class="mt-3 text-3xl font-bold sm:text-4xl">Chapel collection pulse</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-sky-100">Monitor monthly Balik Gasa compliance, optional offerings, and recent collection activity in one clear workspace.</p>
            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs uppercase tracking-wide text-sky-100">Active Members</p>
                    <p class="mt-2 text-2xl font-bold">{{ $activeMembersCount }}</p>
                </div>
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs uppercase tracking-wide text-sky-100">Paid</p>
                    <p class="mt-2 text-2xl font-bold">{{ $paidMembersCount }}</p>
                </div>
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="text-xs uppercase tracking-wide text-sky-100">Unpaid</p>
                    <p class="mt-2 text-2xl font-bold">{{ $unpaidMembersCount }}</p>
                </div>
            </div>
        </div>
        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-500">Balik Gasa Paid Rate</p>
                    <p class="mt-2 text-4xl font-bold text-sky-950">{{ $paidRate }}%</p>
                </div>
                <a href="{{ route('balik-gasa.index', ['month' => $currentMonth]) }}" class="rounded-lg bg-amber-500 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-600">Monitor</a>
            </div>
            <div class="mt-6 h-3 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-amber-500" style="width: {{ $paidRate }}%"></div>
            </div>
            <div class="mt-6 grid gap-3 text-sm">
                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                    <span class="font-medium text-slate-600">Monthly Donation</span>
                    <span class="font-bold text-sky-950">PHP {{ number_format((float) ($monthTotals[\App\Models\Collection::DONATION] ?? 0), 2) }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                    <span class="font-medium text-slate-600">Monthly Offering</span>
                    <span class="font-bold text-sky-950">PHP {{ number_format((float) ($monthTotals[\App\Models\Collection::HALAD] ?? 0), 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($cards as [$label, $value, $class, $caption, $url])
        <article class="rounded-lg border border-slate-200 p-5 shadow-sm {{ $class }}">
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm font-semibold opacity-80">{{ $label }}</p>
                <a href="{{ $url }}" class="rounded-lg bg-white/80 px-3 py-1 text-xs font-bold text-sky-800 hover:bg-white">Open</a>
            </div>
            <p class="mt-3 text-3xl font-bold">PHP {{ number_format((float) $value, 2) }}</p>
            <p class="mt-2 text-xs font-medium opacity-70">{{ $caption }}</p>
        </article>
    @endforeach
</section>

<section class="mt-6">
    <div class="mb-3 flex items-center justify-between gap-4">
        <h2 class="text-lg font-bold text-sky-950">Fund Balances</h2>
        <a href="{{ route('ledger.index') }}" class="text-sm font-semibold text-sky-700">Open ledger</a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($fundSummaries as $summary)
            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-500">{{ $summary['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-sky-950">PHP {{ number_format((float) $summary['balance'], 2) }}</p>
                <p class="mt-2 text-xs text-slate-500">Credits PHP {{ number_format((float) $summary['credits'], 2) }} / Debits PHP {{ number_format((float) $summary['debits'], 2) }}</p>
            </article>
        @endforeach
    </div>
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-[1fr_1.35fr]">
    <article class="flex min-h-[20rem] flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-bold text-sky-950">Recent Disbursements</h2>
            <a href="{{ route('ledger.index') }}" class="text-sm font-semibold text-sky-700">Open ledger</a>
        </div>
        <div class="mt-4 max-h-72 flex-1 divide-y divide-slate-100 overflow-y-auto pr-2">
            @forelse ($recentExpenses as $expense)
                <div class="flex items-center justify-between gap-4 py-3">
                    <div>
                        <p class="font-semibold">{{ $expense->category }}</p>
                        <p class="text-sm text-slate-500">
                            {{ $expense->expense_date->format('M d, Y') }}
                            @if ($expense->pay_to)
                                - {{ $expense->pay_to }}
                            @endif
                        </p>
                    </div>
                    <p class="text-right font-bold text-rose-700">PHP {{ number_format((float) $expense->amount, 2) }}</p>
                </div>
            @empty
                <p class="py-6 text-sm text-slate-500">No disbursements recorded yet.</p>
            @endforelse
        </div>
    </article>

    <article class="flex min-h-[20rem] flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-lg font-bold text-sky-950">Recent Collection Entries</h2>
            <a href="{{ route('reports.index', ['month' => $currentMonth]) }}" class="text-sm font-semibold text-sky-700">Monthly report</a>
        </div>
        <div class="mt-4 max-h-72 flex-1 overflow-auto pr-2">
            <table class="min-w-full text-left text-sm">
                <thead class="sticky top-0 bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Member</th><th class="px-3 py-3">Type</th><th class="px-3 py-3 text-right">Amount</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentCollections as $collection)
                        <tr>
                            <td class="px-3 py-3">{{ $collection->collection_date->format('M d, Y') }}</td>
                            <td class="px-3 py-3 font-medium">{{ $collection->member?->full_name ?: 'All members / Mass collection' }}</td>
                            <td class="px-3 py-3">{{ $collection->typeLabel() }}</td>
                            <td class="px-3 py-3 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-slate-500">No collections recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>
@endsection
