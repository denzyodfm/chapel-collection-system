@csrf
<div class="grid gap-5 md:grid-cols-2">
    <label id="member_wrap" class="grid gap-2 text-sm font-medium text-slate-700">Member
        <input type="search" data-member-filter-target="collection-member-select" placeholder="Type a member name to filter" class="rounded-lg border border-slate-300 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
        <select id="collection-member-select" name="member_id" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            <option value="">Select member</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('member_id', $collection->member_id) === (string) $member->id)>{{ $member->member_id }} - {{ $member->full_name }} ({{ $member->hugpongBanay?->name ?: 'No Hugpong Banay' }})</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Collection Type
        <select name="collection_type" id="collection_type" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('collection_type', $collection->collection_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Amount
        <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $collection->amount) }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Collection Date
        <input name="collection_date" type="date" value="{{ old('collection_date', $collection->collection_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label id="collection_month_wrap" class="grid gap-2 text-sm font-medium text-slate-700">Collection Month
        <input name="collection_month" type="month" value="{{ old('collection_month', $collection->collection_month) }}" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Reference
        <input name="reference_no" value="{{ old('reference_no', $collection->reference_no) }}" placeholder="Receipt, OR number, source ref" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700 md:col-span-2">Notes / Remarks
        <textarea name="remarks" rows="3" class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">{{ old('remarks', $collection->remarks) }}</textarea>
    </label>
</div>
<p id="halad_hint" class="mt-4 hidden rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">Offering is recorded as one total mass collection from all members. No member is required.</p>
<div class="mt-6 flex flex-wrap gap-3">
    <button class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Save Collection</button>
    <a href="{{ route('collections.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</a>
</div>
<script>
    const typeSelect = document.getElementById('collection_type');
    const memberWrap = document.getElementById('member_wrap');
    const memberSelect = document.getElementById('collection-member-select');
    const monthWrap = document.getElementById('collection_month_wrap');
    const monthInput = monthWrap.querySelector('input');
    const haladHint = document.getElementById('halad_hint');
    function toggleCollectionFields() {
        const isBalikGasa = typeSelect.value === 'balik_gasa';
        const isHalad = typeSelect.value === 'halad';
        memberWrap.classList.toggle('hidden', isHalad);
        memberSelect.required = !isHalad;
        if (isHalad) {
            memberSelect.value = '';
        }
        monthWrap.classList.toggle('hidden', !isBalikGasa);
        monthInput.required = isBalikGasa;
        if (!isBalikGasa) {
            monthInput.value = '';
        }
        haladHint.classList.toggle('hidden', !isHalad);
    }
    typeSelect.addEventListener('change', toggleCollectionFields);
    toggleCollectionFields();
</script>
