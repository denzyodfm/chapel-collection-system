@extends('layouts.app')

@section('page-title', 'Donation Monthly Monitoring')

@section('page-actions')
@if (auth()->user()->hasAnyRole(['admin', 'treasurer']))
    <a href="{{ route('collections.create', ['collection_type' => \App\Models\Collection::DONATION]) }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900"><x-icon name="plus" class="h-4 w-4" /> Add Donation</a>
@endif
@endsection

@section('content')
<form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <label class="grid gap-2 text-sm font-medium text-slate-700">Select Month
        <input name="month" type="month" value="{{ $month }}" class="rounded-lg border border-slate-300 px-4 py-3">
    </label>
    @if ($selectedHugpongBanayId)
        <input type="hidden" name="hugpong_banay_id" value="{{ $selectedHugpongBanayId }}">
    @endif
    <label class="grid min-w-64 gap-2 text-sm font-medium text-slate-700">Search Member
        <input type="search" data-table-filter-target="donations-table" placeholder="Type a member name" class="rounded-lg border border-slate-300 px-4 py-3">
    </label>
    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white"><x-icon name="filter" class="h-4 w-4" /> View Month</button>
</form>

<x-month-lock-panel :lockable-type="\App\Models\Collection::DONATION" :month="$month" :month-label="$monthLabel" :lock="$monthLock" />

<section class="mb-5 grid gap-4 lg:grid-cols-[1fr_280px]">
    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-bold text-sky-950">Filter by Hugpong Banay</h2>
                <p class="text-sm text-slate-500">Show members and donations for {{ $monthLabel }}.</p>
            </div>
            @if ($selectedHugpongBanay)
                <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">Selected: {{ $selectedHugpongBanay->name }}</span>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('donations.index', ['month' => $month]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $selectedHugpongBanayId ? 'border border-slate-200 text-slate-600 hover:bg-slate-50' : 'bg-sky-800 text-white' }}">All Hugpong Banay</a>
            @foreach ($hugpongBanays as $hugpongBanay)
                <a href="{{ route('donations.index', ['month' => $month, 'hugpong_banay_id' => $hugpongBanay->id]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ (string) $selectedHugpongBanayId === (string) $hugpongBanay->id ? 'bg-sky-800 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                    {{ $hugpongBanay->name }}
                    <span class="ml-1 text-xs opacity-75">{{ $hugpongBanay->members_count }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <article class="rounded-lg border border-amber-200 bg-amber-50 p-5 shadow-sm">
        <p class="text-sm font-semibold text-amber-800">{{ $monthLabel }} Donation</p>
        <p class="mt-2 text-3xl font-bold text-sky-950">PHP {{ number_format((float) $totalDonation, 2) }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ $donations->count() }} donation {{ \Illuminate\Support\Str::plural('entry', $donations->count()) }}</p>
    </article>
</section>

<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-sky-50 px-5 py-4">
        <div>
            <h2 class="text-lg font-bold text-sky-950">{{ $selectedHugpongBanay?->name ?: 'All Hugpong Banay' }}</h2>
            <p class="text-sm text-slate-600">Donation entries are optional and may be posted multiple times per member.</p>
        </div>
        <p class="text-sm font-semibold text-slate-600">{{ $members->count() }} active members</p>
    </div>
    <div class="overflow-x-auto">
        <table id="donations-table" class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Member</th><th class="px-4 py-3 text-right">Month Total</th><th class="px-4 py-3">Entries</th><th class="px-4 py-3">Last Donation</th><th class="px-4 py-3">Quick Donation</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                    @php $summary = $donationSummary->get($member->id); @endphp
                    <tr>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-slate-900">{{ $member->full_name }}</span>
                            <span class="block text-xs text-slate-500">{{ $member->member_id }} - {{ $member->hugpongBanay?->name ?: 'No Hugpong Banay set' }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $summary ? 'PHP '.number_format((float) $summary['total'], 2) : '-' }}</td>
                        <td class="px-4 py-3">{{ $summary['count'] ?? 0 }}</td>
                        <td class="px-4 py-3">{{ data_get($summary, 'last_date')?->format('M d, Y') ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($monthLock)
                                <span class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">Month locked</span>
                            @elseif (auth()->user()->hasAnyRole(['admin', 'treasurer']))
                                <form method="POST" action="{{ route('donations.quick-pay', $member) }}" class="grid min-w-96 gap-2">
                                    @csrf
                                    <input type="hidden" name="collection_month" value="{{ $month }}">
                                    <div class="grid gap-2 sm:grid-cols-[140px_130px_1fr_auto]">
                                        <input name="collection_date" type="date" value="{{ now()->format('Y-m-d') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        <input name="reference_no" placeholder="Reference" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                        <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white"><x-icon name="save" class="h-4 w-4" /> Post</button>
                                    </div>
                                    <input name="remarks" placeholder="Notes" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </form>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No active members found for this Hugpong Banay.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<section class="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="text-lg font-bold text-sky-950">Recent Donations for {{ $monthLabel }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Member</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3">Notes</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($donations as $donation)
                    <tr>
                        <td class="px-4 py-3">{{ $donation->collection_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $donation->member?->full_name }}</td>
                        <td class="px-4 py-3">{{ $donation->reference_no ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $donation->remarks ?: '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">PHP {{ number_format((float) $donation->amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($monthLock)
                                <span class="text-rose-600">Locked</span>
                            @elseif (auth()->user()->hasAnyRole(['admin', 'treasurer']))
                                <a href="{{ route('collections.edit', $donation) }}" class="inline-flex items-center gap-1 font-semibold text-amber-700"><x-icon name="edit" class="h-3.5 w-3.5" /> Edit</a>
                                <form class="inline" method="POST" action="{{ route('collections.destroy', $donation) }}" onsubmit="return confirm('Delete this donation?')">
                                    @csrf @method('DELETE')
                                    <button class="ml-3 inline-flex items-center gap-1 font-semibold text-rose-700"><x-icon name="trash" class="h-3.5 w-3.5" /> Delete</button>
                                </form>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No donations recorded for this month.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
