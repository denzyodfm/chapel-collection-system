@csrf
<div class="grid gap-5 md:grid-cols-2">
    <div class="grid gap-2 text-sm font-medium text-slate-700">Member ID
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-slate-600">{{ $member->member_id ?: 'Auto-generated on save' }}</div>
    </div>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Full Name
        <input name="full_name" value="{{ old('full_name', $member->full_name) }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Contact Number
        <input name="contact_number" value="{{ old('contact_number', $member->contact_number) }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Address
        <input name="address_purok" value="{{ old('address_purok', $member->address_purok) }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Hugpong Banay
        <select name="hugpong_banay_id" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            <option value="">Select Hugpong Banay</option>
            @foreach ($hugpongBanays as $hugpongBanay)
                <option value="{{ $hugpongBanay->id }}" @selected((string) old('hugpong_banay_id', $member->hugpong_banay_id) === (string) $hugpongBanay->id)>{{ $hugpongBanay->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Status
        <select name="status" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $member->status ?: 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Date Joined
        <input name="date_joined" type="date" value="{{ old('date_joined', $member->date_joined?->format('Y-m-d')) }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
</div>
<div class="mt-6 flex flex-wrap gap-3">
    <button class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Save Member</button>
    <a href="{{ route('members.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</a>
</div>
