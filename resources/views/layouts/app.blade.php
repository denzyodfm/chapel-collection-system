<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Princess Homes Fatima Chapel Collection System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    @auth
        <div class="min-h-screen lg:flex">
            <aside class="bg-white border-r border-slate-200 lg:fixed lg:inset-y-0 lg:w-72">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <a href="{{ route('dashboard') }}" class="leading-tight">
                        <span class="block text-sm font-semibold uppercase tracking-wide text-amber-600">Princess Homes</span>
                        <span class="block text-lg font-bold text-sky-900">Fatima Chapel</span>
                    </a>
                    <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ ucfirst(auth()->user()->role) }}</span>
                </div>

                <nav class="grid gap-1 px-4 py-5 text-sm font-medium sm:grid-cols-2 lg:grid-cols-1">
                    @php
                        $nav = [
                            ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                            ['Balik Gasa Monitor', 'balik-gasa.index', 'balik-gasa*', 'calendar'],
                            ['Donation Monitor', 'donations.index', 'donations*', 'gift'],
                            ['Offering Monitor', 'offerings.index', 'offerings*', 'coins'],
                            ['Reports', 'reports.index', 'reports*', 'report'],
                            ['Ledger', 'ledger.index', 'ledger*', 'ledger'],
                        ];
                        if (auth()->user()->hasAnyRole(['admin', 'treasurer'])) {
                            $nav[] = ['Members', 'members.index', 'members*', 'users'];
                            $nav[] = ['Hugpong Banay', 'hugpong-banays.index', 'hugpong-banays*', 'home'];
                            $nav[] = ['Collections', 'collections.index', 'collections*', 'plus'];
                        }
                        if (auth()->user()->role === 'admin') {
                            $nav[] = ['User Accounts', 'users.index', 'users*', 'users'];
                        }
                    @endphp

                    @foreach ($nav as [$label, $route, $active, $icon])
                        <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-lg px-4 py-3 {{ request()->routeIs($active) ? 'bg-sky-100 text-sky-900' : 'text-slate-600 hover:bg-slate-100 hover:text-sky-900' }}">
                            <x-icon :name="$icon" class="h-4 w-4 shrink-0" />
                            {{ $label }}
                        </a>
                    @endforeach
                </nav>

                <div class="px-4 pb-5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full rounded-lg border border-slate-200 px-4 py-3 text-left text-sm font-semibold text-slate-600 hover:border-amber-300 hover:bg-amber-50">Logout</button>
                    </form>
                </div>
            </aside>

            <main class="min-h-screen flex-1 lg:pl-72">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">@yield('eyebrow', 'Chapel Collection')</p>
                            <h1 class="text-2xl font-bold text-sky-950 sm:text-3xl">@yield('page-title')</h1>
                        </div>
                        @yield('page-actions')
                    </header>

                    @if (session('success'))
                        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <strong>Please check the form.</strong>
                            <ul class="mt-2 list-inside list-disc">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    @else
        @yield('content')
    @endauth
</body>
</html>
