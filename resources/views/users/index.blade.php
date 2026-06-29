@extends('layouts.app')
@section('page-title', 'User Accounts')
@section('page-actions')
<a href="{{ route('users.create') }}" class="rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900">Add User</a>
@endsection
@section('content')
<div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Email</th><th class="px-4 py-3">Role</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($users as $user)
                <tr>
                    <td class="px-4 py-3 font-semibold">{{ $user->name }}</td>
                    <td class="px-4 py-3">{{ $user->email }}</td>
                    <td class="px-4 py-3">{{ ucfirst($user->role) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('users.edit', $user) }}" class="font-semibold text-amber-700">Edit</a>
                        <form class="inline" method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?')">
                            @csrf @method('DELETE')
                            <button class="ml-3 font-semibold text-rose-700">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-5">{{ $users->links() }}</div>
@endsection
