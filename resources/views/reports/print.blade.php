<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Report - {{ $monthLabel }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-900 antialiased">
    <main class="mx-auto max-w-5xl px-6 py-8">
        <div class="mb-6 flex items-center justify-between gap-4 print:hidden">
            <a href="{{ route('reports.index', ['month' => $month]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Reports</a>
            <button onclick="window.print()" class="rounded-lg bg-sky-800 px-5 py-2 text-sm font-semibold text-white hover:bg-sky-900">Print Report</button>
        </div>

        <header class="border-b-2 border-slate-900 pb-5 text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-slate-600">Princess Homes Fatima Chapel</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">Monthly Collection Report</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $monthLabel }}{{ $collectionType ? ' - '.$types[$collectionType] : '' }}</p>
        </header>

        <section class="mt-6 grid grid-cols-4 gap-3">
            @php $grand = 0; @endphp
            @foreach ($types as $type => $label)
                @php $total = (float) ($summary[$type] ?? 0); $grand += $total; @endphp
                <div class="border border-slate-300 p-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-lg font-bold">PHP {{ number_format($total, 2) }}</p>
                </div>
            @endforeach
            <div class="border border-slate-900 bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase text-slate-600">Grand Total</p>
                <p class="mt-1 text-lg font-bold">PHP {{ number_format($grand, 2) }}</p>
            </div>
        </section>

        <section class="mt-6">
            <h2 class="text-lg font-bold">Balik Gasa ICP / Chapel Share by Hugpong Banay</h2>
            <table class="mt-3 w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border border-slate-300 px-3 py-2">Hugpong Banay</th>
                        <th class="border border-slate-300 px-3 py-2 text-right">Paid Members</th>
                        <th class="border border-slate-300 px-3 py-2 text-right">Balik Gasa Total</th>
                        <th class="border border-slate-300 px-3 py-2 text-right">ICP 60%</th>
                        <th class="border border-slate-300 px-3 py-2 text-right">Chapel 40%</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($balikGasaShares['rows'] as $row)
                        <tr>
                            <td class="border border-slate-300 px-3 py-2">{{ $row['name'] }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-right">{{ $row['members_paid'] }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-right font-semibold">PHP {{ number_format((float) $row['total'], 2) }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-right">PHP {{ number_format((float) $row['icp_share'], 2) }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-right">PHP {{ number_format((float) $row['chapel_share'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="border border-slate-300 px-3 py-6 text-center text-slate-500">No Balik Gasa payments for this month.</td></tr>
                    @endforelse
                    <tr class="bg-slate-50 font-bold">
                        <td class="border border-slate-300 px-3 py-2">Grand Total</td>
                        <td class="border border-slate-300 px-3 py-2 text-right">{{ $balikGasaShares['grand']['members_paid'] }}</td>
                        <td class="border border-slate-300 px-3 py-2 text-right">PHP {{ number_format((float) $balikGasaShares['grand']['total'], 2) }}</td>
                        <td class="border border-slate-300 px-3 py-2 text-right">PHP {{ number_format((float) $balikGasaShares['grand']['icp_share'], 2) }}</td>
                        <td class="border border-slate-300 px-3 py-2 text-right">PHP {{ number_format((float) $balikGasaShares['grand']['chapel_share'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="mt-6">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="bg-slate-100">
                        <th class="border border-slate-300 px-3 py-2">Date</th>
                        <th class="border border-slate-300 px-3 py-2">Member ID</th>
                        <th class="border border-slate-300 px-3 py-2">Member</th>
                        <th class="border border-slate-300 px-3 py-2">Type</th>
                        <th class="border border-slate-300 px-3 py-2">Reference</th>
                        <th class="border border-slate-300 px-3 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($collections as $collection)
                        <tr>
                            <td class="border border-slate-300 px-3 py-2">{{ $collection->collection_date->format('M d, Y') }}</td>
                            <td class="border border-slate-300 px-3 py-2">{{ $collection->member?->member_id ?: '-' }}</td>
                            <td class="border border-slate-300 px-3 py-2">{{ $collection->member?->full_name ?: 'All members / Mass collection' }}</td>
                            <td class="border border-slate-300 px-3 py-2">{{ $collection->typeLabel() }}</td>
                            <td class="border border-slate-300 px-3 py-2">{{ $collection->reference_no ?: '-' }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-right font-semibold">PHP {{ number_format((float) $collection->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="border border-slate-300 px-3 py-6 text-center text-slate-500">No collection entries for this report.</td></tr>
                    @endforelse
                </tbody>
            </table>
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
