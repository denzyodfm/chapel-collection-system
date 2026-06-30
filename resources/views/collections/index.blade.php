@extends('layouts.app')
@section('page-title', 'Collection Entries')
@section('page-actions')
<a href="{{ route('collections.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900"><x-icon name="plus" class="h-4 w-4" /> Add Collection</a>
@endsection

@section('content')
<form method="GET" class="mb-5 rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-4 xl:grid-cols-[minmax(260px,1.4fr)_minmax(220px,1.1fr)_minmax(150px,.7fr)_minmax(150px,.7fr)_minmax(220px,1fr)_120px]">
        <label class="grid gap-2 text-sm font-medium text-slate-700">Search
            <input name="search" value="{{ request('search') }}" data-table-filter-target="collections-table" type="search" placeholder="Type member, reference, type, or notes" class="min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
        </label>
        <label class="grid gap-2 text-sm font-medium text-slate-700">Member
            <select name="member_id" class="min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                <option value="">All members</option>
                @foreach ($members as $member)
                    <option value="{{ $member->id }}" @selected((string) request('member_id') === (string) $member->id)>{{ $member->full_name }} - {{ $member->hugpongBanay?->name ?: 'No Hugpong Banay' }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2 text-sm font-medium text-slate-700">Type
            <select name="collection_type" class="min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                <option value="">All types</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(request('collection_type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-2 text-sm font-medium text-slate-700">Month
            <input name="collection_month" type="month" value="{{ request('collection_month') }}" class="min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
        </label>
        <div class="grid grid-cols-2 gap-3">
            <label class="grid gap-2 text-sm font-medium text-slate-700">From
                <input name="date_from" type="date" value="{{ request('date_from') }}" class="min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            </label>
            <label class="grid gap-2 text-sm font-medium text-slate-700">To
                <input name="date_to" type="date" value="{{ request('date_to') }}" class="min-w-0 rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            </label>
        </div>
        <div class="grid content-end">
            <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-900"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
        </div>
    </div>
</form>

<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table id="collections-table" class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Date</th><th class="px-4 py-3">Member</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Month</th><th class="px-4 py-3">Reference</th><th class="px-4 py-3 text-right">Amount</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($collections as $collection)
                    <tr>
                        <td class="px-4 py-3">{{ $collection->collection_date->format('M d, Y') }}</td>
                        <td class="px-4 py-3 font-medium">{{ $collection->member?->full_name ?: 'All members / Mass collection' }}</td>
                        <td class="px-4 py-3">{{ $collection->typeLabel() }}</td>
                        <td class="px-4 py-3">{{ $collection->collection_month ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $collection->reference_no ?: '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('collections.edit', $collection) }}" class="font-semibold text-amber-700">Edit</a>
                            <form class="inline" method="POST" action="{{ route('collections.destroy', $collection) }}" onsubmit="return confirm('Delete this collection entry?')">
                                @csrf @method('DELETE')
                                <button class="ml-3 font-semibold text-rose-700">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No collection entries found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-5">{{ $collections->links() }}</div>
@endsection
