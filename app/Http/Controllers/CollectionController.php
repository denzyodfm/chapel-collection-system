<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Member;
use App\Models\MonthLock;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'member_id' => ['nullable', 'exists:members,id'],
            'collection_type' => ['nullable', Rule::in(array_keys(Collection::TYPES))],
            'collection_month' => ['nullable', 'date_format:Y-m'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $collections = Collection::with(['member', 'encoder'])
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('reference_no', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%")
                    ->orWhere('collection_type', 'like', "%{$search}%")
                    ->orWhereHas('member', fn ($query) => $query->where('member_id', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%"));
            }))
            ->when($filters['member_id'] ?? null, fn ($query, $memberId) => $query->where('member_id', $memberId))
            ->when($filters['collection_type'] ?? null, fn ($query, $type) => $query->where('collection_type', $type))
            ->when($filters['collection_month'] ?? null, fn ($query, $month) => $query->where('collection_month', $month))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('collection_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('collection_date', '<=', $date))
            ->latest('collection_date')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('collections.index', [
            'collections' => $collections,
            'members' => Member::with('hugpongBanay')->orderBy('full_name')->get(),
            'types' => Collection::TYPES,
        ]);
    }

    public function create(Request $request): View
    {
        $validated = $request->validate([
            'collection_type' => ['nullable', Rule::in(array_keys(Collection::TYPES))],
        ]);

        return view('collections.create', [
            'collection' => new Collection([
                'collection_date' => now(),
                'collection_type' => $validated['collection_type'] ?? null,
            ]),
            'members' => Member::with('hugpongBanay')->active()->orderBy('full_name')->get(),
            'types' => Collection::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['encoded_by'] = $request->user()->id;
        $this->assertCollectionMonthUnlocked($data['collection_type'], $data['collection_month'] ?? null, $data['collection_date']);

        $this->saveCollection(fn () => Collection::create($data));

        return redirect()->route('collections.index')->with('success', 'Collection entry saved.');
    }

    public function edit(Collection $collection): View
    {
        return view('collections.edit', [
            'collection' => $collection,
            'members' => Member::with('hugpongBanay')->orderBy('full_name')->get(),
            'types' => Collection::TYPES,
        ]);
    }

    public function update(Request $request, Collection $collection): RedirectResponse
    {
        $data = $this->validated($request, $collection);
        $this->assertCollectionMonthUnlocked($collection->collection_type, $collection->collection_month, $collection->collection_date);
        $this->assertCollectionMonthUnlocked($data['collection_type'], $data['collection_month'] ?? null, $data['collection_date']);

        $this->saveCollection(fn () => $collection->update($data));

        return redirect()->route('collections.index')->with('success', 'Collection entry updated.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        if ($this->collectionMonthLocked($collection->collection_type, $collection->collection_month, $collection->collection_date)) {
            return back()->with('error', MonthLock::lockedMessage($collection->collection_type, $this->lockMonthForCollection($collection->collection_type, $collection->collection_month, $collection->collection_date)));
        }

        $collection->delete();

        return redirect()->route('collections.index')->with('success', 'Collection entry deleted.');
    }

    private function validated(Request $request, ?Collection $collection = null): array
    {
        $data = $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'collection_type' => ['required', Rule::in(array_keys(Collection::TYPES))],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'collection_month' => ['nullable', 'date_format:Y-m'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['collection_type'] === Collection::BALIK_GASA) {
            $request->validate([
                'member_id' => ['required', 'exists:members,id'],
                'collection_month' => ['required', 'date_format:Y-m'],
            ]);

            $duplicate = Collection::where('collection_type', Collection::BALIK_GASA)
                ->where('member_id', $data['member_id'])
                ->where('collection_month', $data['collection_month'])
                ->when($collection, fn ($query) => $query->whereKeyNot($collection->id))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'collection_month' => 'This member already has a Balik Gasa payment for this month.',
                ]);
            }
        } elseif ($data['collection_type'] === Collection::DONATION) {
            $request->validate(['member_id' => ['required', 'exists:members,id']]);
            $data['collection_month'] = null;
        } else {
            $data['member_id'] = null;
            $data['collection_month'] = null;
        }

        return $data;
    }

    private function saveCollection(callable $callback): void
    {
        try {
            $callback();
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'collection_month' => 'This member already has a Balik Gasa payment for this month.',
                ]);
            }

            throw $exception;
        }
    }

    private function assertCollectionMonthUnlocked(string $type, ?string $collectionMonth, string|\DateTimeInterface $collectionDate): void
    {
        $month = $this->lockMonthForCollection($type, $collectionMonth, $collectionDate);

        if (MonthLock::isLocked($type, $month)) {
            throw ValidationException::withMessages([
                'collection_date' => MonthLock::lockedMessage($type, $month),
            ]);
        }
    }

    private function collectionMonthLocked(string $type, ?string $collectionMonth, string|\DateTimeInterface $collectionDate): bool
    {
        return MonthLock::isLocked($type, $this->lockMonthForCollection($type, $collectionMonth, $collectionDate));
    }

    private function lockMonthForCollection(string $type, ?string $collectionMonth, string|\DateTimeInterface $collectionDate): string
    {
        if ($type === Collection::BALIK_GASA) {
            return (string) $collectionMonth;
        }

        return Carbon::parse($collectionDate)->format('Y-m');
    }
}
