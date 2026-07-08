<?php

namespace App\Http\Controllers;

use App\Models\MonthLock;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonthLockController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lockable_type' => ['required', Rule::in(array_keys(MonthLock::TYPES))],
            'month' => ['required', 'date_format:Y-m'],
        ]);

        MonthLock::firstOrCreate(
            [
                'lockable_type' => $data['lockable_type'],
                'month' => $data['month'],
            ],
            ['locked_by' => $request->user()->id]
        );

        return back()->with('success', MonthLock::TYPES[$data['lockable_type']]." records for {$data['month']} are now locked.");
    }

    public function destroy(MonthLock $monthLock): RedirectResponse
    {
        $label = MonthLock::TYPES[$monthLock->lockable_type] ?? 'Records';
        $month = $monthLock->month;

        $monthLock->delete();

        return back()->with('success', "{$label} records for {$month} are now unlocked.");
    }
}
