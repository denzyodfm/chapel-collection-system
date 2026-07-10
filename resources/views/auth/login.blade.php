@extends('layouts.app')

@section('title', 'Login | Princess Homes Fatima Chapel Collection System')

@section('content')
<main class="grid min-h-screen lg:grid-cols-[1fr_480px]">
    <section class="relative hidden overflow-hidden bg-sky-900 lg:block">
        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('images/our-lady-of-fatima.jpg') }}');"></div>
        <div class="absolute inset-0 bg-gradient-to-br from-sky-950/92 via-sky-900/76 to-blue-900/82"></div>
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-transparent via-white/10 to-transparent"></div>
        <div class="relative flex h-full flex-col justify-between p-12 text-white">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[.2em] text-amber-200">Community Stewardship</p>
                <h1 class="mt-5 max-w-3xl text-5xl font-bold leading-tight drop-shadow-sm">Princess Homes Fatima Chapel Collection System</h1>
            </div>
            <p class="max-w-xl text-lg text-sky-50 drop-shadow-sm">Record Balik Gasa, Donation, and Offering collections with clear monthly monitoring and printable reports.</p>
        </div>
    </section>
    <section class="flex items-center justify-center bg-white px-6 py-12">
        <div class="w-full max-w-sm">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">Welcome back</p>
            <h2 class="mt-2 text-3xl font-bold text-sky-950">Sign in</h2>
            @if ($errors->any())
                <div class="mt-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif
            @if (session('status'))
                <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            <form method="POST" action="{{ route('login.attempt') }}" class="mt-8 grid gap-5">
                @csrf
                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Email
                    <input name="email" type="email" value="{{ old('email') }}" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <label class="grid gap-2 text-sm font-medium text-slate-700">
                    Password
                    <input name="password" type="password" required class="rounded-lg border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input name="remember" type="checkbox" class="rounded border-slate-300 text-sky-700">
                    Remember me
                </label>
                <button class="rounded-lg bg-sky-800 px-5 py-3 font-semibold text-white shadow-sm hover:bg-sky-900">Login</button>
            </form>
            <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                Seeded accounts use password <strong>password</strong>: admin@chapel.test, treasurer@chapel.test, viewer@chapel.test.
            </div>
        </div>
    </section>
</main>
@endsection
