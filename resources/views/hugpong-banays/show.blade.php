@extends('layouts.app')

@section('page-title', $hugpongBanay->name)
@section('page-actions')
<div class="flex gap-3">
    <a href="{{ route('hugpong-banays.edit', $hugpongBanay) }}" class="rounded-lg bg-amber-500 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-600">Edit</a>
    <form method="POST" action="{{ route('hugpong-banays.destroy', $hugpongBanay) }}" onsubmit="return confirm('Delete this Hugpong Banay? This only works when no members are assigned.')">
        @csrf @method('DELETE')
        <button class="rounded-lg border border-rose-200 px-5 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button>
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
        <h2 class="text-lg font-bold text-sky-950">Members</h2>
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
