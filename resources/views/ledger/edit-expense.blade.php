@extends('layouts.app')

@section('page-title', 'Edit Disbursement')
@section('eyebrow', 'Funds')

@section('content')
<form method="POST" action="{{ route('ledger.expenses.update', $expense) }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
    @csrf
    @method('PUT')
    <div class="flex flex-wrap items-end justify-between gap-3">
        <h2 class="text-lg font-bold text-sky-950">Disbursement Details</h2>
        <label class="grid gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500">Disbursement Date
            <input name="expense_date" type="date" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm normal-case tracking-normal text-slate-900">
        </label>
    </div>
    <div class="mt-4 grid gap-4 md:grid-cols-2">
        <select name="fund_type" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
            @foreach ($fundTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('fund_type', $expense->fund_type) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <div>
            <input name="category" list="expense-categories" value="{{ old('category', $expense->category) }}" placeholder="Disbursement category" required class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm">
            <datalist id="expense-categories">
                @foreach ($expenseCategories as $category)
                    <option value="{{ $category }}"></option>
                @endforeach
            </datalist>
            <p class="mt-1 text-xs text-slate-500">Choose a category or type a new one.</p>
        </div>
        <input name="pay_to" value="{{ old('pay_to', $expense->pay_to) }}" placeholder="Pay to" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <input name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount', $expense->amount) }}" placeholder="Amount" required class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <input name="reference_no" value="{{ old('reference_no', $expense->reference_no) }}" placeholder="Reference" class="rounded-lg border border-slate-300 px-4 py-3 text-sm">
        <textarea name="remarks" rows="4" placeholder="Notes" class="rounded-lg border border-slate-300 px-4 py-3 text-sm md:col-span-2">{{ old('remarks', $expense->remarks) }}</textarea>
    </div>
    <div class="mt-5 flex flex-wrap gap-3">
        <button class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white">
            <x-icon name="save" class="h-4 w-4" />
            Update Disbursement
        </button>
        <a href="{{ route('ledger.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</a>
    </div>
</form>
@endsection
