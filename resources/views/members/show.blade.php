@extends('layouts.app')
@section('page-title', $member->full_name)
@section('page-actions')
<div class="flex flex-wrap gap-3">
    <a href="{{ route('members.edit', $member) }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-600"><x-icon name="edit" class="h-4 w-4" /> Edit</a>
    @if ($member->status === 'active')
        <form method="POST" action="{{ route('members.deactivate', $member) }}" onsubmit="return confirm('Mark this member as inactive?')">
            @csrf @method('PATCH')
            <button class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100"><x-icon name="users" class="h-4 w-4" /> Set Inactive</button>
        </form>
    @endif
    @if (auth()->user()->role === 'admin')
        <form method="POST" action="{{ route('members.destroy', $member) }}" class="flex flex-wrap gap-2" onsubmit="return confirm('Delete this member? This only works when there is no collection history.')">
            @csrf @method('DELETE')
            <input name="delete_confirmation" placeholder="Type delete" required pattern="delete" class="w-32 rounded-lg border border-rose-200 px-3 py-3 text-sm focus:border-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-100">
            <button class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-5 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50"><x-icon name="trash" class="h-4 w-4" /> Delete</button>
        </form>
    @endif
</div>
@endsection
@section('content')
<section class="grid gap-5 lg:grid-cols-[360px_1fr]">
    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-amber-600">{{ $member->member_id }}</p>
        <dl class="mt-4 grid gap-4 text-sm">
            <div><dt class="text-slate-500">Contact</dt><dd class="font-semibold">{{ $member->contact_number ?: '-' }}</dd></div>
            <div><dt class="text-slate-500">Address</dt><dd class="font-semibold">{{ $member->address_purok ?: '-' }}</dd></div>
            <div><dt class="text-slate-500">Hugpong Banay</dt><dd class="font-semibold">{{ $member->hugpongBanay?->name ?: 'Unassigned' }}</dd></div>
            <div><dt class="text-slate-500">Status</dt><dd class="font-semibold">{{ ucfirst($member->status) }}</dd></div>
            <div><dt class="text-slate-500">Date Joined</dt><dd class="font-semibold">{{ $member->date_joined?->format('M d, Y') ?: '-' }}</dd></div>
        </dl>
        <div class="mt-5 grid gap-3">
            @foreach ($memberCollectionTypes as $type)
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-sm text-slate-500">{{ \App\Models\Collection::TYPES[$type] }}</p>
                    <p class="text-xl font-bold text-sky-950">PHP {{ number_format((float) ($totals[$type] ?? 0), 2) }}</p>
                </div>
            @endforeach
        </div>
    </article>
    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-bold text-sky-950">Collection History</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Date</th><th class="px-3 py-3">Type</th><th class="px-3 py-3">Month</th><th class="px-3 py-3 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($collections as $collection)
                        <tr><td class="px-3 py-3">{{ $collection->collection_date->format('M d, Y') }}</td><td class="px-3 py-3">{{ $collection->typeLabel() }}</td><td class="px-3 py-3">{{ $collection->collection_month ?: '—' }}</td><td class="px-3 py-3 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No collection history yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $collections->links() }}</div>
    </article>
</section>
@endsection
