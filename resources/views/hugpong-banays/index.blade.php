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
                    <td class="px-4 py-3 font-semibold">{{ $hugpongBanay->name }}</td>
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
@endsection
