@csrf
<div class="grid gap-5 md:grid-cols-2">
    <label class="grid gap-2 text-sm font-medium text-slate-700">Name
        <input name="name" value="{{ old('name', $user->name) }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Email
        <input name="email" type="email" value="{{ old('email', $user->email) }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Role
        <select name="role" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            @foreach (['admin' => 'Admin', 'treasurer' => 'Treasurer / Encoder', 'viewer' => 'Viewer'] as $value => $label)
                <option value="{{ $value }}" @selected(old('role', $user->role ?: 'viewer') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="grid gap-2 text-sm font-medium text-slate-700">Password {{ $user->exists ? '(leave blank to keep current)' : '' }}
        <input name="password" type="password" @required(! $user->exists) class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
    </label>
</div>
<div class="mt-6 flex gap-3">
    <button class="inline-flex items-center gap-2 rounded-lg bg-sky-800 px-5 py-3 text-sm font-semibold text-white hover:bg-sky-900"><x-icon name="save" class="h-4 w-4" /> Save User</button>
    <a href="{{ route('users.index') }}" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancel</a>
</div>
