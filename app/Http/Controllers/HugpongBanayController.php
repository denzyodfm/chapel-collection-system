<?php

namespace App\Http\Controllers;

use App\Models\HugpongBanay;
use App\Models\HugpongBanayLeaderHistory;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class HugpongBanayController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $hugpongBanays = HugpongBanay::with(['currentLeader'])
            ->withCount(['members', 'members as active_members_count' => fn ($query) => $query->where('status', 'active')])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('hugpong-banays.index', compact('hugpongBanays'));
    }

    public function create(): View
    {
        return view('hugpong-banays.create', [
            'hugpongBanay' => new HugpongBanay(['status' => 'active']),
            'members' => Member::active()->orderBy('full_name')->get(),
            'leaderHistory' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $leaderId = $data['current_leader_id'] ?? null;
        $leaderStartedAt = $data['leader_started_at'] ?? now()->toDateString();
        unset($data['leader_started_at']);

        $hugpongBanay = HugpongBanay::create($data);

        if ($leaderId) {
            $this->setLeader($hugpongBanay, (int) $leaderId, $leaderStartedAt, true);
        }

        return redirect()->route('hugpong-banays.show', $hugpongBanay)->with('success', 'Hugpong Banay created successfully.');
    }

    public function show(HugpongBanay $hugpongBanay): View
    {
        $hugpongBanay->load(['currentLeader', 'activeLeaderHistory']);

        return view('hugpong-banays.show', [
            'hugpongBanay' => $hugpongBanay,
            'members' => $hugpongBanay->members()->orderBy('full_name')->paginate(10),
            'leaderHistories' => $hugpongBanay->leaderHistories()->with('member')->latest('started_at')->get(),
        ]);
    }

    public function edit(HugpongBanay $hugpongBanay): View
    {
        $hugpongBanay->load('activeLeaderHistory');

        return view('hugpong-banays.edit', [
            'hugpongBanay' => $hugpongBanay,
            'members' => Member::active()->orderBy('full_name')->get(),
            'leaderHistory' => $hugpongBanay->activeLeaderHistory,
        ]);
    }

    public function update(Request $request, HugpongBanay $hugpongBanay): RedirectResponse
    {
        $data = $this->validated($request, $hugpongBanay);
        $leaderId = $data['current_leader_id'] ?? null;
        $leaderStartedAt = $data['leader_started_at'] ?? now()->toDateString();
        unset($data['current_leader_id']);
        unset($data['leader_started_at']);

        $hugpongBanay->update($data);
        $this->syncLeader($hugpongBanay->fresh(), $leaderId ? (int) $leaderId : null, $leaderStartedAt);

        return redirect()->route('hugpong-banays.show', $hugpongBanay)->with('success', 'Hugpong Banay updated successfully.');
    }

    public function destroy(HugpongBanay $hugpongBanay): RedirectResponse
    {
        abort_if($hugpongBanay->members()->exists(), 422, 'Hugpong Banay with assigned members cannot be deleted.');
        $hugpongBanay->delete();

        return redirect()->route('hugpong-banays.index')->with('success', 'Hugpong Banay deleted successfully.');
    }

    private function validated(Request $request, ?HugpongBanay $hugpongBanay = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('hugpong_banays', 'name')->ignore($hugpongBanay)],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'current_leader_id' => ['nullable', 'exists:members,id'],
            'leader_started_at' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
    }

    private function syncLeader(HugpongBanay $hugpongBanay, ?int $leaderId, string $startedAt): void
    {
        if ((int) $hugpongBanay->current_leader_id === (int) $leaderId) {
            return;
        }

        if (! $leaderId) {
            $this->closeActiveLeaderHistory($hugpongBanay, $startedAt);
            $hugpongBanay->update(['current_leader_id' => null]);

            return;
        }

        $this->setLeader($hugpongBanay, $leaderId, $startedAt);
    }

    private function setLeader(HugpongBanay $hugpongBanay, int $leaderId, string $startedAt, bool $newRecord = false): void
    {
        $leader = Member::active()->findOrFail($leaderId);

        if (! $newRecord) {
            $this->closeActiveLeaderHistory($hugpongBanay, $startedAt);
        }

        $leader->update(['hugpong_banay_id' => $hugpongBanay->id]);
        $hugpongBanay->update(['current_leader_id' => $leader->id]);

        HugpongBanayLeaderHistory::create([
            'hugpong_banay_id' => $hugpongBanay->id,
            'member_id' => $leader->id,
            'started_at' => $startedAt,
            'notes' => 'Selected as Hugpong Banay leader.',
        ]);
    }

    private function closeActiveLeaderHistory(HugpongBanay $hugpongBanay, string $newStartedAt): void
    {
        $activeHistory = $hugpongBanay->leaderHistories()->whereNull('ended_at')->latest('started_at')->first();

        if (! $activeHistory) {
            return;
        }

        $endedAt = Carbon::parse($newStartedAt)->subDay();

        if ($endedAt->lt($activeHistory->started_at)) {
            throw ValidationException::withMessages([
                'leader_started_at' => 'New leader start date must be after the current leader tenure start date.',
            ]);
        }

        $activeHistory->update(['ended_at' => $endedAt->toDateString()]);
    }
}
