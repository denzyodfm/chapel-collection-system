@extends('layouts.app')

@section('page-title', 'Members Management')
@section('page-actions')
<a href="{{ route('members.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900"><x-icon name="plus" class="h-4 w-4" /> Add Member</a>
@endsection

@section('content')
<form class="mb-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_220px_180px_auto]" method="GET">
    <input name="search" value="{{ request('search') }}" data-table-filter-target="members-table" placeholder="Search member ID, name, address, or Hugpong Banay" class="rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    <select name="hugpong_banay_id" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <option value="">All Hugpong Banay</option>
        @foreach ($hugpongBanays as $hugpongBanay)
            <option value="{{ $hugpongBanay->id }}" @selected((string) request('hugpong_banay_id') === (string) $hugpongBanay->id)>{{ $hugpongBanay->name }}</option>
        @endforeach
    </select>
    <select name="status" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
    </select>
    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white"><x-icon name="filter" class="h-4 w-4" /> Filter</button>
</form>

<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table id="members-table" class="min-w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Member ID</th><th class="px-4 py-3">Name</th><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Address</th><th class="px-4 py-3">Hugpong Banay</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($members as $member)
                    <tr>
                        <td class="px-4 py-3 font-semibold"><button type="button" data-balik-gasa-member-url="{{ route('members.balik-gasa-year', $member) }}" data-balik-gasa-year="{{ now()->year }}" class="font-semibold text-sky-700 hover:underline">{{ $member->member_id }}</button></td>
                        <td class="px-4 py-3">{{ $member->full_name }}</td>
                        <td class="px-4 py-3">{{ $member->contact_number ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $member->address_purok ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $member->hugpongBanay?->name ?: 'Unassigned' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $member->status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst($member->status) }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a class="font-semibold text-sky-700" href="{{ route('members.show', $member) }}">View</a>
                            <a class="ml-3 font-semibold text-amber-700" href="{{ route('members.edit', $member) }}">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-5">{{ $members->links() }}</div>

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
