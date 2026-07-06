@extends('layouts.app')

@section('page-title', 'Balik Gasa Monthly Monitoring')

@section('content')
<form method="GET" class="mb-5 flex flex-wrap items-end gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <label class="grid gap-2 text-sm font-medium text-slate-700">Select Month
        <input name="month" type="month" value="{{ $month }}" class="rounded-lg border border-slate-300 px-4 py-3">
    </label>
    @if ($selectedHugpongBanayId)
        <input type="hidden" name="hugpong_banay_id" value="{{ $selectedHugpongBanayId }}">
    @endif
    <label class="grid gap-2 text-sm font-medium text-slate-700">Search Member
        <input type="search" data-table-filter-target="balik-gasa-table" placeholder="Type a member name" class="rounded-lg border border-slate-300 px-4 py-3">
    </label>
    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white"><x-icon name="filter" class="h-4 w-4" /> View Month</button>
</form>

<section class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-bold text-sky-950">Filter by Hugpong Banay</h2>
            <p class="text-sm text-slate-500">Select a group to show only its active members for {{ $monthLabel }}.</p>
        </div>
        @if ($selectedHugpongBanay)
            <span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">Selected: {{ $selectedHugpongBanay->name }}</span>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('balik-gasa.index', ['month' => $month]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $selectedHugpongBanayId ? 'border border-slate-200 text-slate-600 hover:bg-slate-50' : 'bg-sky-800 text-white' }}">All Hugpong Banay</a>
        @foreach ($hugpongBanays as $hugpongBanay)
            <a href="{{ route('balik-gasa.index', ['month' => $month, 'hugpong_banay_id' => $hugpongBanay->id]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ (string) $selectedHugpongBanayId === (string) $hugpongBanay->id ? 'bg-sky-800 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                {{ $hugpongBanay->name }}
                <span class="ml-1 text-xs opacity-75">{{ $hugpongBanay->members_count }}</span>
            </a>
        @endforeach
    </div>
</section>

<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-sky-50 px-5 py-4">
        <div>
            <h2 class="text-lg font-bold text-sky-950">{{ $selectedHugpongBanay?->name ?: 'All Hugpong Banay' }}</h2>
            <p class="text-sm text-slate-600">{{ $monthLabel }}</p>
        </div>
        <p class="text-sm font-semibold text-slate-600">{{ $payments->count() }} paid / {{ $members->count() }} active members</p>
    </div>
    <div class="overflow-x-auto">
        <table id="balik-gasa-table" class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Member</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3">Payment Date</th><th class="px-4 py-3">Balik Gasa Payment</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                    @php $payment = $payments->get($member->id); @endphp
                    <tr class="{{ $payment ? 'bg-slate-50 text-slate-500' : 'bg-white' }}">
                        <td class="px-4 py-3"><span class="font-semibold {{ $payment ? 'text-slate-500' : 'text-slate-900' }}">{{ $member->full_name }}</span><span class="block text-xs text-slate-500"><button type="button" data-balik-gasa-member-url="{{ route('members.balik-gasa-year', $member) }}" data-balik-gasa-year="{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->year }}" class="font-semibold text-sky-700 hover:underline">{{ $member->member_id }}</button> - {{ $member->hugpongBanay?->name ?: 'No Hugpong Banay set' }}</span></td>
                        <td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $payment ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $payment ? 'Paid' : 'Unpaid' }}</span></td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $payment ? 'PHP '.number_format((float) $payment->amount, 2) : '-' }}</td>
                        <td class="px-4 py-3">{{ $payment?->collection_date?->format('M d, Y') ?: '-' }}</td>
                        <td class="px-4 py-3">
                            @if (! $payment && auth()->user()->hasAnyRole(['admin', 'treasurer']))
                                <form method="POST" action="{{ route('balik-gasa.quick-pay', $member) }}" class="flex min-w-[22rem] flex-wrap gap-2">
                                    @csrf
                                    <input type="hidden" name="collection_month" value="{{ $month }}">
                                    <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <input name="collection_date" type="date" value="{{ now()->format('Y-m-d') }}" required class="w-36 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white"><x-icon name="save" class="h-4 w-4" /> Pay</button>
                                </form>
                            @elseif ($payment)
                                <span class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500">Already paid</span>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($payment && auth()->user()->hasAnyRole(['admin', 'treasurer']))
                                <a href="{{ route('collections.edit', $payment) }}" class="inline-flex items-center gap-1 font-semibold text-amber-700"><x-icon name="edit" class="h-3.5 w-3.5" /> Edit</a>
                                <form class="inline" method="POST" action="{{ route('collections.destroy', $payment) }}" onsubmit="return confirm('Delete this Balik Gasa payment?')">
                                    @csrf @method('DELETE')
                                    <button class="ml-3 inline-flex items-center gap-1 font-semibold text-rose-700"><x-icon name="trash" class="h-3.5 w-3.5" /> Delete</button>
                                </form>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No active members found for this Hugpong Banay.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="balik-gasa-modal" class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4">
    <div class="mx-auto mt-12 max-w-3xl rounded-lg bg-white p-5 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">Balik Gasa Plot</p>
                <h2 id="balik-gasa-modal-title" class="text-xl font-bold text-sky-950">Member</h2>
            </div>
            <button type="button" data-balik-gasa-close class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">Close</button>
        </div>
        <div class="mt-4 flex items-center justify-between gap-3">
            <button type="button" data-balik-gasa-prev class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">Previous Year</button>
            <p id="balik-gasa-modal-year" class="text-lg font-bold text-slate-900"></p>
            <button type="button" data-balik-gasa-next class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">Next Year</button>
        </div>
        <div id="balik-gasa-modal-grid" class="mt-5 grid gap-3 sm:grid-cols-3 md:grid-cols-4"></div>
    </div>
</div>
@endsection
