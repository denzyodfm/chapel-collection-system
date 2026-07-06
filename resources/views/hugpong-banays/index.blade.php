@extends('layouts.app')

@section('page-title', 'Hugpong Banay')
@section('page-actions')
<a href="{{ route('hugpong-banays.create') }}" class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Add Hugpong Banay</a>
@endsection

@section('content')
<form class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_180px_auto]" method="GET">
    <input name="search" value="{{ request('search') }}" placeholder="Search Hugpong Banay" class="rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    <select name="status" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
    </select>
    <button class="rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white">Filter</button>
</form>

<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
            <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Leader</th><th class="px-4 py-3">Members</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($hugpongBanays as $hugpongBanay)
                <tr>
                    <td class="px-4 py-3">
                        <button type="button" data-modal-open="hugpong-banay-{{ $hugpongBanay->id }}-modal" class="font-semibold text-sky-800 hover:text-sky-950 hover:underline">
                            {{ $hugpongBanay->name }}
                        </button>
                    </td>
                    <td class="px-4 py-3">{{ $hugpongBanay->currentLeader?->full_name ?: 'No leader' }}</td>
                    <td class="px-4 py-3">{{ $hugpongBanay->active_members_count }} active / {{ $hugpongBanay->members_count }} total</td>
                    <td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $hugpongBanay->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($hugpongBanay->status) }}</span></td>
                    <td class="px-4 py-3 text-right">
                        <a class="font-semibold text-sky-700" href="{{ route('hugpong-banays.show', $hugpongBanay) }}">View</a>
                        <a class="ml-3 font-semibold text-amber-700" href="{{ route('hugpong-banays.edit', $hugpongBanay) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No Hugpong Banay records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $hugpongBanays->links() }}</div>

@foreach ($hugpongBanays as $hugpongBanay)
    <div id="hugpong-banay-{{ $hugpongBanay->id }}-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/50 p-4">
        <div class="mx-auto my-8 max-w-5xl overflow-hidden rounded-lg bg-white shadow-xl">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 bg-sky-50 px-5 py-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">Hugpong Banay Details</p>
                    <h2 class="text-2xl font-bold text-sky-950">{{ $hugpongBanay->name }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $hugpongBanay->active_members_count }} active / {{ $hugpongBanay->members_count }} total members</p>
                </div>
                <button type="button" data-modal-close="hugpong-banay-{{ $hugpongBanay->id }}-modal" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Close</button>
            </div>

            <div class="grid gap-5 p-5 lg:grid-cols-[.9fr_1.4fr]">
                <section class="rounded-lg border border-slate-200 bg-white p-4">
                    <dl class="grid gap-4 text-sm">
                        <div>
                            <dt class="font-semibold text-slate-500">Status</dt>
                            <dd class="mt-1"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $hugpongBanay->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($hugpongBanay->status) }}</span></dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Current Leader</dt>
                            <dd class="mt-1 font-semibold text-sky-950">{{ $hugpongBanay->currentLeader?->full_name ?: 'No leader selected' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Description</dt>
                            <dd class="mt-1 text-slate-700">{{ $hugpongBanay->description ?: 'No description provided.' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex flex-wrap gap-2">
                        <a href="{{ route('hugpong-banays.show', $hugpongBanay) }}" class="rounded-lg bg-sky-800 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-900">Open Full View</a>
                        <a href="{{ route('hugpong-banays.edit', $hugpongBanay) }}" class="rounded-lg border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50">Edit</a>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
                        <h3 class="font-bold text-sky-950">Members Portal</h3>
                        <span class="text-sm font-semibold text-slate-500">{{ $hugpongBanay->members->count() }} members</span>
                    </div>
                    <div class="max-h-96 overflow-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="sticky top-0 bg-slate-50 text-xs uppercase text-slate-500">
                                <tr><th class="px-4 py-3">Member ID</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($hugpongBanay->members as $member)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-sky-700">{{ $member->member_id }}</td>
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $member->full_name }}</td>
                                        <td class="px-4 py-3">{{ $member->contact_number ?: '-' }}</td>
                                        <td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $member->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($member->status) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">No members assigned to this Hugpong Banay.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endforeach
@endsection
