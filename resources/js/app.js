document.addEventListener('input', (event) => {
    const filter = event.target.closest('[data-member-filter-target]');

    if (!filter) {
        return;
    }

    const select = document.getElementById(filter.dataset.memberFilterTarget);

    if (!select) {
        return;
    }

    const search = filter.value.trim().toLowerCase();

    Array.from(select.options).forEach((option) => {
        if (!option.value) {
            option.hidden = false;

            return;
        }

        option.hidden = search.length > 0 && !option.textContent.toLowerCase().includes(search);
    });

    const visibleMatch = Array.from(select.options).find((option) => option.value && !option.hidden);

    if (visibleMatch && search.length > 0) {
        select.value = visibleMatch.value;
    }
});

document.addEventListener('input', (event) => {
    const filter = event.target.closest('[data-table-filter-target]');

    if (!filter) {
        return;
    }

    const table = document.getElementById(filter.dataset.tableFilterTarget);

    if (!table) {
        return;
    }

    const search = filter.value.trim().toLowerCase();

    table.querySelectorAll('tbody tr').forEach((row) => {
        if (row.querySelector('td[colspan]')) {
            return;
        }

        row.hidden = search.length > 0 && !row.textContent.toLowerCase().includes(search);
    });
});

document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-modal-open]');
    const closer = event.target.closest('[data-modal-close]');

    if (opener) {
        document.getElementById(opener.dataset.modalOpen)?.classList.remove('hidden');
        return;
    }

    if (closer) {
        document.getElementById(closer.dataset.modalClose)?.classList.add('hidden');
    }
});

let balikGasaPlotState = null;

async function loadBalikGasaPlot() {
    if (!balikGasaPlotState) {
        return;
    }

    const modal = document.getElementById('balik-gasa-modal');
    const title = document.getElementById('balik-gasa-modal-title');
    const yearLabel = document.getElementById('balik-gasa-modal-year');
    const grid = document.getElementById('balik-gasa-modal-grid');

    if (!modal || !title || !yearLabel || !grid) {
        return;
    }

    grid.innerHTML = '<div class="col-span-full rounded-lg bg-slate-50 p-4 text-sm text-slate-500">Loading...</div>';
    modal.classList.remove('hidden');

    const response = await fetch(`${balikGasaPlotState.url}?year=${balikGasaPlotState.year}`, {
        headers: { Accept: 'application/json' },
    });
    const data = await response.json();

    title.textContent = `${data.member.member_id} - ${data.member.name}`;
    yearLabel.textContent = data.year;
    grid.innerHTML = data.months.map((month) => {
        const paidClass = month.paid ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800';
        const status = month.paid ? `Paid PHP ${Number(month.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : 'Unpaid';
        const date = month.date || '-';

        return `
            <div class="rounded-lg border ${paidClass} p-4">
                <p class="text-sm font-bold">${month.label}</p>
                <p class="mt-2 text-lg font-bold">${status}</p>
                <p class="mt-1 text-xs opacity-80">${date}</p>
            </div>
        `;
    }).join('');
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-balik-gasa-member-url]');

    if (trigger) {
        balikGasaPlotState = {
            url: trigger.dataset.balikGasaMemberUrl,
            year: Number(trigger.dataset.balikGasaYear) || new Date().getFullYear(),
        };
        loadBalikGasaPlot();
        return;
    }

    if (event.target.closest('[data-balik-gasa-close]')) {
        document.getElementById('balik-gasa-modal')?.classList.add('hidden');
        return;
    }

    if (event.target.closest('[data-balik-gasa-prev]') && balikGasaPlotState) {
        balikGasaPlotState.year -= 1;
        loadBalikGasaPlot();
        return;
    }

    if (event.target.closest('[data-balik-gasa-next]') && balikGasaPlotState) {
        balikGasaPlotState.year += 1;
        loadBalikGasaPlot();
    }
});
