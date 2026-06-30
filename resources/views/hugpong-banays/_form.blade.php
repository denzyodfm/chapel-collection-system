@csrf
<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 text-sm font-medium text-slate-700">Hugpong Banay Name
        <input name="name" value="{{ old('name', $hugpongBanay->name) }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Status
        <select name="status" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $hugpongBanay->status ?: 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Current Leader
        <input type="search" data-member-filter-target="hugpong-leader-select" placeholder="Type a member name to filter" class="rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
        <select id="hugpong-leader-select" name="current_leader_id" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            <option value="">No leader selected</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('current_leader_id', $hugpongBanay->current_leader_id) === (string) $member->id)>{{ $member->member_id }} - {{ $member->full_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Leader Tenure Start
        <input name="leader_started_at" type="date" value="{{ old('leader_started_at', $leaderHistory?->started_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700 md:col-span-2">Description / Area Notes
        <textarea name="description" rows="3" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">{{ old('description', $hugpongBanay->description) }}</textarea>
    </label>
</div>
<p class="mt-4 text-sm text-slate-500">If the selected leader belongs to another Hugpong Banay, the system will move that member to this Hugpong Banay and record the new leader tenure.</p>
<div class="mt-6 flex flex-wrap gap-3">
    <button class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900"><x-icon name="save" class="h-4 w-4" /> Save Hugpong Banay</button>
    <a href="{{ route('hugpong-banays.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</a>
</div>
