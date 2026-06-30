<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Princess Homes Fatima Chapel Collection System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    <div class="pointer-events-none fixed inset-y-0 right-0 z-0 hidden w-[min(44vw,620px)] bg-[url('/images/our-lady-of-fatima.jpg')] bg-contain bg-right-bottom bg-no-repeat opacity-[0.08] lg:block"></div>
    @auth
        <div data-sidebar-shell class="relative z-10 min-h-screen lg:flex">
            <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-950/40 lg:hidden"></div>
            <aside data-sidebar class="fixed inset-y-0 left-0 z-40 w-72 -translate-x-full overflow-y-auto border-r border-slate-200 bg-white transition-transform duration-200">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <a href="{{ route('dashboard') }}" class="leading-tight">
                        <span class="block text-sm font-semibold uppercase tracking-wide text-amber-600">Princess Homes</span>
                        <span class="block text-lg font-bold text-sky-900">Fatima Chapel</span>
                    </a>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{{ ucfirst(auth()->user()->role) }}</span>
                    </div>
                </div>

                <nav class="grid gap-1 px-4 py-5 text-sm font-medium">
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

            <main data-main-content class="min-h-screen flex-1 transition-all duration-200 lg:pl-72">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <header class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div class="flex items-start gap-3">
                            <button type="button" data-sidebar-toggle class="mt-1 rounded-lg border border-slate-200 bg-white p-2 text-slate-600 shadow-sm hover:bg-slate-50" aria-label="Toggle menu">
                                <x-icon name="menu" class="h-5 w-5" />
                            </button>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <a href="{{ route('dashboard') }}" class="leading-tight">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-amber-600">Princess Homes</span>
                                        <span class="block text-base font-bold text-sky-900">Fatima Chapel</span>
                                    </a>
                                    <span class="hidden h-8 w-px bg-slate-200 sm:block"></span>
                                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-600">@yield('eyebrow', 'Chapel Collection')</p>
                                </div>
                                <h1 class="mt-1 text-2xl font-bold text-sky-950 sm:text-3xl">@yield('page-title')</h1>
                            </div>
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
