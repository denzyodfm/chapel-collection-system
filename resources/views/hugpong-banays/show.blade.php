@extends('layouts.app')

@section('page-title', $hugpongBanay->name)
@section('page-actions')
<div class="flex flex-wrap gap-3">
    <button type="button" data-modal-open="add-member-modal" class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900"><x-icon name="plus" class="h-4 w-4" /> Add Member</button>
    <a href="{{ route('hugpong-banays.edit', $hugpongBanay) }}" class="inline-flex items-center gap-2 rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-600"><x-icon name="edit" class="h-4 w-4" /> Edit</a>
    <form method="POST" action="{{ route('hugpong-banays.destroy', $hugpongBanay) }}" onsubmit="return confirm('Delete this Hugpong Banay? This only works when no members are assigned.')">
        @csrf @method('DELETE')
        <button class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-5 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50"><x-icon name="trash" class="h-4 w-4" /> Delete</button>
    </form>
</div>
@endsection

@section('content')
<section class="grid gap-6 xl:grid-cols-[360px_1fr]">
    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-semibold text-amber-600">{{ ucfirst($hugpongBanay->status) }}</p>
        <dl class="mt-4 grid gap-4 text-sm">
            <div><dt class="text-slate-500">Current Leader</dt><dd class="font-semibold">{{ $hugpongBanay->currentLeader?->full_name ?: 'No leader selected' }}</dd></div>
            <div><dt class="text-slate-500">Current Tenure</dt><dd class="font-semibold">{{ $hugpongBanay->activeLeaderHistory?->started_at?->format('M d, Y') ?: '-' }} to Present</dd></div>
            <div><dt class="text-slate-500">Description</dt><dd class="font-semibold">{{ $hugpongBanay->description ?: '-' }}</dd></div>
        </dl>
    </article>

    <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-sky-950">Members</h2>
            <button type="button" data-modal-open="add-member-modal" class="inline-flex items-center gap-2 rounded-lg border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-800 hover:bg-sky-50"><x-icon name="plus" class="h-4 w-4" /> Add Member</button>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Member ID</th><th class="px-3 py-3">Name</th><th class="px-3 py-3">Contact</th><th class="px-3 py-3">Status</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($members as $member)
                        <tr><td class="px-3 py-3 font-semibold">{{ $member->member_id }}</td><td class="px-3 py-3">{{ $member->full_name }}</td><td class="px-3 py-3">{{ $member->contact_number ?: '-' }}</td><td class="px-3 py-3">{{ ucfirst($member->status) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No members assigned yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $members->links() }}</div>
    </article>
</section>

<div id="add-member-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
    <div class="mx-auto mt-10 max-w-3xl rounded-lg bg-white p-5 shadow-xl">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">{{ $hugpongBanay->name }}</p>
                <h2 class="text-xl font-bold text-sky-950">Add Member to Hugpong Banay</h2>
            </div>
            <button type="button" data-modal-close="add-member-modal" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">Close</button>
        </div>

        <form method="POST" action="{{ route('hugpong-banays.members.store', $hugpongBanay) }}" class="mt-5">
            @csrf
            <div class="grid gap-4 md:grid-cols-2">
                <label class="grid gap-2 text-sm font-medium text-slate-700">Full Name
                    <input name="full_name" value="{{ old('full_name') }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <label class="grid gap-2 text-sm font-medium text-slate-700">Contact Number
                    <input name="contact_number" value="{{ old('contact_number') }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <label class="grid gap-2 text-sm font-medium text-slate-700">Address
                    <input name="address_purok" value="{{ old('address_purok') }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <label class="grid gap-2 text-sm font-medium text-slate-700">Status
                    <select name="status" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="grid gap-2 text-sm font-medium text-slate-700">Date Joined
                    <input name="date_joined" type="date" value="{{ old('date_joined', now()->format('Y-m-d')) }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <div class="rounded-lg bg-sky-50 p-4 text-sm text-sky-900">
                    <p class="font-semibold">Hugpong Banay</p>
                    <p class="mt-1">{{ $hugpongBanay->name }}</p>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <button class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white"><x-icon name="save" class="h-4 w-4" /> Save Member</button>
                <button type="button" data-modal-close="add-member-modal" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
            </div>
        </form>
    </div>
</div>

<section class="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-lg font-bold text-sky-950">Leader Tenure History</h2>
    <div class="mt-4 overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3">Leader</th><th class="px-3 py-3">Started</th><th class="px-3 py-3">Ended</th><th class="px-3 py-3">Tenure</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($leaderHistories as $history)
                    @php
                        $end = $history->ended_at ?? now();
                        $tenure = $history->started_at ? $history->started_at->diffForHumans($end, ['parts' => 2, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) : '-';
                    @endphp
                    <tr><td class="px-3 py-3 font-semibold">{{ $history->member->full_name }}</td><td class="px-3 py-3">{{ $history->started_at?->format('M d, Y') ?: '-' }}</td><td class="px-3 py-3">{{ $history->ended_at?->format('M d, Y') ?: 'Present' }}</td><td class="px-3 py-3">{{ $tenure }}</td></tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No leader history yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
