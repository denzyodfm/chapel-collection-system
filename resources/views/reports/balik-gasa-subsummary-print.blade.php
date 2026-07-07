<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Balik Gasa Subsummary - {{ $monthLabel }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 antialiased">
    <main class="mx-auto max-w-5xl px-6 py-8">
        <div class="mb-6 flex items-center justify-between gap-4 print:hidden">
            <a href="{{ route('reports.index', ['month' => $month]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Reports</a>
            <button onclick="window.print()" class="rounded-lg bg-sky-800 px-5 py-2 text-sm font-semibold text-white hover:bg-sky-900">Print Subsummary</button>
        </div>

        <header class="border-b-2 border-slate-900 pb-5 text-center">
            <p class="text-lg font-bold uppercase tracking-wide text-slate-700">Princess Homes Fatima Chapel</p>
            <h1 class="mt-2 text-xl font-bold text-slate-950">Balik Gasa Subsummary by Hugpong Banay</h1>
            <p class="mt-1 text-base font-semibold text-slate-600">{{ $monthLabel }}</p>
        </header>

        <section class="mt-6 grid grid-cols-4 gap-3">
            <div class="border border-slate-300 p-3">
                <p class="text-xs font-semibold uppercase text-slate-500">Paid Members</p>
                <p class="mt-1 text-lg font-bold">{{ $balikGasaSubsummary['grand']['members_paid'] }}</p>
            </div>
            <div class="border border-slate-300 p-3">
                <p class="text-xs font-semibold uppercase text-slate-500">Grand Total</p>
                <p class="mt-1 text-lg font-bold">PHP {{ number_format((float) $balikGasaSubsummary['grand']['total'], 2) }}</p>
            </div>
            <div class="border border-slate-300 p-3">
                <p class="text-xs font-semibold uppercase text-slate-500">ICP 60%</p>
                <p class="mt-1 text-lg font-bold">PHP {{ number_format((float) $balikGasaSubsummary['grand']['icp_share'], 2) }}</p>
            </div>
            <div class="border border-slate-900 bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase text-slate-600">Chapel 40%</p>
                <p class="mt-1 text-lg font-bold">PHP {{ number_format((float) $balikGasaSubsummary['grand']['chapel_share'], 2) }}</p>
            </div>
        </section>

        <section class="mt-6 space-y-6">
            @forelse ($balikGasaSubsummary['groups'] as $group)
                <div>
                    <div class="mb-2 flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-bold">{{ $group['name'] }}</h2>
                            <p class="text-xs text-slate-500">{{ $group['members_paid'] }} paid members</p>
                        </div>
                        <div class="text-right text-sm">
                            <p class="font-bold">Subtotal PHP {{ number_format((float) $group['total'], 2) }}</p>
                            <p class="text-xs text-slate-500">ICP PHP {{ number_format((float) $group['icp_share'], 2) }} / Chapel PHP {{ number_format((float) $group['chapel_share'], 2) }}</p>
                        </div>
                    </div>
                    <div class="mb-3 grid grid-cols-2 gap-10 text-xs">
                        <div class="border-t border-slate-500 pt-1 text-center">Signature</div>
                        <div class="border-t border-slate-500 pt-1 text-center">Signature</div>
                    </div>
                    <table class="w-full border-collapse text-left text-sm">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-300 px-3 py-2">Date</th>
                                <th class="border border-slate-300 px-3 py-2">Member ID</th>
                                <th class="border border-slate-300 px-3 py-2">Member</th>
                                <th class="border border-slate-300 px-3 py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($group['entries'] as $collection)
                                <tr>
                                    <td class="border border-slate-300 px-3 py-2">{{ $collection->collection_date->format('M d, Y') }}</td>
                                    <td class="border border-slate-300 px-3 py-2">{{ $collection->member?->member_id ?: '-' }}</td>
                                    <td class="border border-slate-300 px-3 py-2">{{ $collection->member?->full_name ?: 'Unknown member' }}</td>
                                    <td class="border border-slate-300 px-3 py-2 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 font-bold">
                                <td colspan="3" class="border border-slate-300 px-3 py-2 text-right">Subtotal</td>
                                <td class="border border-slate-300 px-3 py-2 text-right">PHP {{ number_format((float) $group['total'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="border border-slate-300 px-3 py-6 text-center text-slate-500">No Balik Gasa payments for this month.</p>
            @endforelse
        </section>

        <section class="mt-12 grid grid-cols-2 gap-12 text-sm">
            <div>
                <div class="border-t border-slate-900 pt-2 text-center">Prepared by / Treasurer</div>
            </div>
            <div>
                <div class="border-t border-slate-900 pt-2 text-center">Reviewed by / Chapel Officer</div>
            </div>
        </section>

        <p class="mt-8 text-center text-xs text-slate-500">Generated {{ now()->format('M d, Y h:i A') }}</p>
    </main>
</body>
</html>
