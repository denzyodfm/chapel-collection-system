@props(['lockableType', 'month', 'monthLabel', 'lock' => null])

@if (auth()->user()->hasAnyRole(['admin', 'treasurer']))
    <section class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-lg border {{ $lock ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-white' }} p-4 shadow-sm">
        <div>
            <p class="text-sm font-bold {{ $lock ? 'text-rose-800' : 'text-sky-950' }}">
                {{ $lock ? 'Locked Month' : 'Open Month' }}
            </p>
            <p class="text-sm {{ $lock ? 'text-rose-700' : 'text-slate-600' }}">
                {{ \App\Models\MonthLock::TYPES[$lockableType] }} records for {{ $monthLabel }} {{ $lock ? 'cannot be added, edited, or deleted.' : 'can still be encoded.' }}
            </p>
        </div>

        @if ($lock)
            @if (auth()->user()->role === 'admin')
                <form method="POST" action="{{ route('month-locks.destroy', $lock) }}" onsubmit="return confirm('Unlock {{ \App\Models\MonthLock::TYPES[$lockableType] }} records for {{ $monthLabel }}?')">
                    @csrf
                    @method('DELETE')
                    <button class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">
                        <x-icon name="unlock" class="h-4 w-4" />
                        Unlock Month
                    </button>
                </form>
            @else
                <span class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-rose-700">Admin unlock required</span>
            @endif
        @else
            <form method="POST" action="{{ route('month-locks.store') }}" onsubmit="return confirm('Lock {{ \App\Models\MonthLock::TYPES[$lockableType] }} records for {{ $monthLabel }}?')">
                @csrf
                <input type="hidden" name="lockable_type" value="{{ $lockableType }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <button class="inline-flex items-center gap-2 rounded-lg bg-rose-700 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-800">
                    <x-icon name="lock" class="h-4 w-4" />
                    Lock Month
                </button>
            </form>
        @endif
    </section>
@endif
