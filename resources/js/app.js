import DOMPurify from 'dompurify';
import mammoth from 'mammoth/mammoth.browser';

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
    if (event.target.closest('[data-demo-login]')) {
        const username = document.querySelector('[data-login-username]');
        const password = document.querySelector('[data-login-password]');

        if (username && password) {
            username.value = 'demo';
            password.value = 'demo';
            username.dispatchEvent(new Event('input', { bubbles: true }));
            password.dispatchEvent(new Event('input', { bubbles: true }));
        }

        return;
    }

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

const templatePreviewModal = document.getElementById('template-preview-modal');
const templatePreviewTitle = document.getElementById('template-preview-title');
const templatePreviewFilename = document.getElementById('template-preview-filename');
const templatePreviewBody = document.getElementById('template-preview-body');
const templatePreviewDownload = document.getElementById('template-preview-download');
let templatePreviewObjectUrl = null;

function closeTemplatePreview() {
    if (!templatePreviewModal) {
        return;
    }

    templatePreviewModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');

    if (templatePreviewObjectUrl) {
        URL.revokeObjectURL(templatePreviewObjectUrl);
        templatePreviewObjectUrl = null;
    }

    if (templatePreviewBody) {
        templatePreviewBody.innerHTML = '';
    }
}

async function openTemplatePreview(trigger) {
    if (!templatePreviewModal || !templatePreviewTitle || !templatePreviewFilename || !templatePreviewBody || !templatePreviewDownload) {
        return;
    }

    closeTemplatePreview();
    templatePreviewTitle.textContent = trigger.dataset.templateName || 'Document preview';
    templatePreviewFilename.textContent = trigger.dataset.templateFilename || '';
    templatePreviewDownload.href = trigger.dataset.templateDownloadUrl;
    templatePreviewBody.innerHTML = '<div class="flex min-h-80 items-center justify-center text-sm font-semibold text-slate-500">Loading document preview...</div>';
    templatePreviewModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    try {
        const response = await fetch(trigger.dataset.templateViewUrl, {
            headers: { Accept: '*/*' },
        });

        if (!response.ok) {
            throw new Error('The document could not be loaded.');
        }

        const blob = await response.blob();
        const filename = (trigger.dataset.templateFilename || '').toLowerCase();
        const mimeType = (blob.type || trigger.dataset.templateMime || '').toLowerCase();
        const isDocx = filename.endsWith('.docx')
            || mimeType.includes('application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        if (isDocx) {
            const result = await mammoth.convertToHtml({ arrayBuffer: await blob.arrayBuffer() });
            templatePreviewBody.innerHTML = `
                <div class="mx-auto min-h-full max-w-4xl bg-white p-6 text-slate-900 shadow-sm sm:p-10">
                    <div class="template-document-content">${DOMPurify.sanitize(result.value)}</div>
                </div>
            `;
            return;
        }

        templatePreviewObjectUrl = URL.createObjectURL(blob);

        if (mimeType === 'application/pdf' || filename.endsWith('.pdf')) {
            templatePreviewBody.innerHTML = `<iframe src="${templatePreviewObjectUrl}" title="Document preview" class="h-[70vh] w-full border-0"></iframe>`;
            return;
        }

        if (mimeType.startsWith('image/')) {
            templatePreviewBody.innerHTML = `<div class="flex min-h-80 items-center justify-center p-4"><img src="${templatePreviewObjectUrl}" alt="Document preview" class="max-h-[70vh] max-w-full object-contain"></div>`;
            return;
        }

        if (mimeType.startsWith('text/') || filename.match(/\.(txt|csv|log|md|json|xml)$/)) {
            const pre = document.createElement('pre');
            pre.className = 'min-h-80 whitespace-pre-wrap break-words bg-white p-6 font-mono text-sm text-slate-800';
            pre.textContent = await blob.text();
            templatePreviewBody.replaceChildren(pre);
            return;
        }

        templatePreviewBody.innerHTML = `
            <div class="flex min-h-80 flex-col items-center justify-center p-8 text-center">
                <p class="font-semibold text-slate-800">A browser preview is not available for this file type.</p>
                <p class="mt-2 text-sm text-slate-500">Use the Download button to open it with an application on your device.</p>
            </div>
        `;
    } catch (error) {
        templatePreviewBody.innerHTML = `
            <div class="flex min-h-80 flex-col items-center justify-center p-8 text-center">
                <p class="font-semibold text-rose-700">The document preview could not be loaded.</p>
                <p class="mt-2 text-sm text-slate-500">${DOMPurify.sanitize(error.message)}</p>
            </div>
        `;
    }
}

document.addEventListener('click', (event) => {
    const previewTrigger = event.target.closest('[data-template-preview]');

    if (previewTrigger) {
        event.preventDefault();
        openTemplatePreview(previewTrigger);
        return;
    }

    if (event.target.closest('[data-template-preview-close]')
        || event.target.matches('[data-template-preview-backdrop]')) {
        closeTemplatePreview();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && templatePreviewModal && !templatePreviewModal.classList.contains('hidden')) {
        closeTemplatePreview();
    }
});

const sidebar = document.querySelector('[data-sidebar]');
const sidebarOverlay = document.querySelector('[data-sidebar-overlay]');
const mainContent = document.querySelector('[data-main-content]');
const headerBrand = document.querySelector('[data-header-brand]');

function setSidebarOpen(isOpen) {
    if (!sidebar || !mainContent || !sidebarOverlay) {
        return;
    }

    sidebar.classList.toggle('-translate-x-full', !isOpen);
    sidebar.classList.toggle('translate-x-0', isOpen);
    sidebarOverlay.classList.toggle('hidden', !isOpen);
    mainContent.classList.toggle('lg:pl-72', isOpen);
    mainContent.classList.toggle('lg:pl-0', !isOpen);

    if (headerBrand) {
        headerBrand.hidden = isOpen;
        headerBrand.classList.toggle('hidden', isOpen);
        headerBrand.classList.toggle('flex', !isOpen);
        headerBrand.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
    }

    if (window.matchMedia('(min-width: 1024px)').matches) {
        localStorage.setItem('chapelSidebarOpen', isOpen ? '1' : '0');
    }
}

if (sidebar && mainContent && sidebarOverlay) {
    const storedSidebarState = localStorage.getItem('chapelSidebarOpen');

    if (window.matchMedia('(min-width: 1024px)').matches) {
        setSidebarOpen(storedSidebarState !== '0');
    } else {
        setSidebarOpen(false);
    }

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-sidebar-toggle]')) {
            setSidebarOpen(sidebar.classList.contains('-translate-x-full'));
            return;
        }

        if (event.target.closest('[data-sidebar-overlay]')) {
            setSidebarOpen(false);
        }
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            setSidebarOpen(localStorage.getItem('chapelSidebarOpen') !== '0');
        } else {
            setSidebarOpen(false);
        }
    });
}

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
        const paidClass = month.excluded_from_totals
            ? 'border-amber-200 bg-amber-50 text-amber-800'
            : 'border-emerald-200 bg-emerald-50 text-emerald-800';
        const blankClass = month.can_record_historical
            ? 'border-slate-200 bg-white text-slate-800'
            : 'border-slate-200 bg-slate-50 text-slate-700';
        const cardClass = month.paid ? paidClass : blankClass;
        const amount = Number(month.amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rawAmount = Number(month.amount).toFixed(2);
        const date = month.date || '-';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const paymentContent = month.paid && month.excluded_from_totals && month.can_edit_historical
            ? `
                <form method="POST" action="${month.historical_update_url}" class="mt-3 grid gap-2">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="PATCH">
                    <input name="amount" type="number" min="0.01" step="0.01" value="${rawAmount}" required class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-semibold text-amber-900">
                    <button class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white">Update</button>
                </form>
                <p class="mt-2 text-xs opacity-80">Excluded from totals</p>
            `
            : month.paid
                ? `
                    <p class="mt-2 text-lg font-bold">PHP ${amount}</p>
                    <p class="mt-1 text-xs opacity-80">${date}</p>
                    ${month.excluded_from_totals ? '<p class="mt-2 text-xs opacity-80">Excluded from totals</p>' : ''}
                `
                : '';
        const historicalForm = month.can_record_historical
            ? `
                <form method="POST" action="${data.historical_store_url}" class="mt-3 flex gap-2">
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="collection_month" value="${month.month}">
                    <input name="amount" type="number" min="0.01" step="0.01" placeholder="Amount" required class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900">
                    <button class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white">Save</button>
                </form>
            `
            : '';

        return `
            <div class="rounded-lg border ${cardClass} p-4">
                <p class="text-sm font-bold">${month.label}</p>
                ${paymentContent}
                ${historicalForm}
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
