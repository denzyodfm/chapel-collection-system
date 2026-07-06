@extends('layouts.app')

@section('page-title', 'Reports')
@section('page-actions')
<div class="flex flex-wrap gap-3 print:hidden">
    <a href="{{ route('reports.print', ['month' => $month]) }}" target="_blank" class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Printable Monthly Report</a>
    <a href="{{ route('reports.csv', ['month' => $month]) }}" class="rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-600">Export CSV</a>
</div>
@endsection

@section('content')
<form method="GET" class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[220px_1fr_auto_minmax(280px,360px)] print:hidden">
    <input name="month" type="month" value="{{ $month }}" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
    <div class="grid gap-2">
        <input type="search" data-member-filter-target="report-member-select" placeholder="Search member for history report" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <select id="report-member-select" name="member_id" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <option value="">Select member for history report</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) request('member_id') === (string) $member->id)>{{ $member->full_name }}</option>
            @endforeach
        </select>
    </div>
    <button class="rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white">Generate</button>
    <div class="grid gap-1 rounded-lg bg-sky-50 px-4 py-3 text-sm">
        <p class="font-semibold text-sky-950">Balik Gasa: PHP {{ number_format((float) $balikGasaShares['grand']['total'], 2) }}</p>
        <p class="text-xs font-medium text-slate-600">ICP 60%: PHP {{ number_format((float) $balikGasaShares['grand']['icp_share'], 2) }}</p>
        <p class="text-xs font-medium text-slate-600">Chapel 40%: PHP {{ number_format((float) $balikGasaShares['grand']['chapel_share'], 2) }}</p>
    </div>
</form>

<section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @php $grand = 0; @endphp
    @foreach ($types as $type => $label)
        @php $total = (float) ($summary[$type] ?? 0); $grand += $total; @endphp
        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-semibold text-slate-500">{{ $label }} for {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('M Y') }}</p>
            <p class="mt-2 text-2xl font-bold text-sky-950">PHP {{ number_format($total, 2) }}</p>
        </article>
    @endforeach
    <article class="rounded-lg border border-amber-200 bg-amber-100 p-5 shadow-sm">
        <p class="text-sm font-semibold text-amber-800">Monthly Grand Total</p>
        <p class="mt-2 text-2xl font-bold text-amber-950">PHP {{ number_format($grand, 2) }}</p>
    </article>
</section>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-sky-950">Balik Gasa ICP / Chapel Share by Hugpong Banay</h2>
            <p class="text-sm text-slate-500">{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }} monthly filtered data</p>
        </div>
        <div class="rounded-lg bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900">
            Grand Total PHP {{ number_format((float) $balikGasaShares['grand']['total'], 2) }}
        </div>
    </div>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-3 py-3">Hugpong Banay</th><th class="px-3 py-3 text-right">Paid Members</th><th class="px-3 py-3 text-right">Balik Gasa Total</th><th class="px-3 py-3 text-right">ICP 60%</th><th class="px-3 py-3 text-right">Chapel 40%</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($balikGasaShares['rows'] as $row)
                    <tr>
                        <td class="px-3 py-3 font-semibold">{{ $row['name'] }}</td>
                        <td class="px-3 py-3 text-right">{{ $row['members_paid'] }}</td>
                        <td class="px-3 py-3 text-right font-semibold">PHP {{ number_format((float) $row['total'], 2) }}</td>
                        <td class="px-3 py-3 text-right text-sky-800">PHP {{ number_format((float) $row['icp_share'], 2) }}</td>
                        <td class="px-3 py-3 text-right text-amber-700">PHP {{ number_format((float) $row['chapel_share'], 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-6 text-center text-slate-500">No Balik Gasa payments for this month.</td></tr>
                @endforelse
                <tr class="bg-slate-50 font-bold">
                    <td class="px-3 py-3">Grand Total</td>
                    <td class="px-3 py-3 text-right">{{ $balikGasaShares['grand']['members_paid'] }}</td>
                    <td class="px-3 py-3 text-right">PHP {{ number_format((float) $balikGasaShares['grand']['total'], 2) }}</td>
                    <td class="px-3 py-3 text-right text-sky-900">PHP {{ number_format((float) $balikGasaShares['grand']['icp_share'], 2) }}</td>
                    <td class="px-3 py-3 text-right text-amber-800">PHP {{ number_format((float) $balikGasaShares['grand']['chapel_share'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-lg font-bold text-sky-950">Monthly Report: {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}</h2>
        <div class="flex flex-wrap gap-2 print:hidden">
            @foreach ($types as $type => $label)
                <a href="{{ route('reports.print', ['month' => $month, 'collection_type' => $type]) }}" target="_blank" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Print {{ $label }}</a>
            @endforeach
        </div>
    </div>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Member</th><th class="px-3 py-3">Type</th><th class="px-3 py-3">Reference</th><th class="px-3 py-3">Remarks</th><th class="px-3 py-3 text-right">Amount</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($monthly as $collection)
                    <tr><td class="px-3 py-3">{{ $collection->collection_date->format('M d, Y') }}</td><td class="px-3 py-3">{{ $collection->member?->full_name ?: 'All members / Mass collection' }}</td><td class="px-3 py-3">{{ $collection->typeLabel() }}</td><td class="px-3 py-3">{{ $collection->reference_no ?: '-' }}</td><td class="px-3 py-3">{{ $collection->remarks ?: '-' }}</td><td class="px-3 py-3 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">No entries for this report.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($selectedMember)
<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-bold text-sky-950">Member Collection History: {{ $selectedMember->full_name }}</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Type</th><th class="px-3 py-3">Month</th><th class="px-3 py-3 text-right">Amount</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($memberHistory as $collection)
                    <tr><td class="px-3 py-3">{{ $collection->collection_date->format('M d, Y') }}</td><td class="px-3 py-3">{{ $collection->typeLabel() }}</td><td class="px-3 py-3">{{ $collection->collection_month ?: '-' }}</td><td class="px-3 py-3 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No collection history for this member.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
