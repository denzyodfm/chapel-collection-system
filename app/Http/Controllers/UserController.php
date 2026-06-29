<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', ['users' => User::orderBy('name')->paginate(12)]);
    }

    public function create(): View
    {
        return view('users.create', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        User::create($data);

        return redirect()->route('users.index')->with('success', 'User account created.');
    }

    public function edit(User $user): View
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        $this->ensureAdminRemains($user, $data['role']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User account updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'You cannot delete your own account.');
        $this->ensureAdminRemains($user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User account deleted.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(['admin', 'treasurer', 'viewer'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }

    private function ensureAdminRemains(User $user, ?string $newRole = null): void
    {
        $wouldRemoveAdmin = $user->role === 'admin' && $newRole !== 'admin';

        if ($wouldRemoveAdmin && User::where('role', 'admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'role' => 'At least one admin account must remain.',
            ]);
        }
    }
}
