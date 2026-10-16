import * as pdfjsLib from 'pdfjs-dist';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;

const RENDER_SCALE = 1.5;
const MIN_WIDTH_PT = 40;
const MIN_HEIGHT_PT = 22;
const HANDLE_SIZE_PX = 10;
const DEFAULT_BOX = { x: 72, y: 700, width: 150, height: 50 };
const HANDLE_DIRS = ['nw', 'ne', 'sw', 'se'];

function clamp(value, min, max) {
    return Math.min(Math.max(value, min), max);
}

function round2(value) {
    return Math.round(value * 100) / 100;
}

function toPt(px) {
    return px / RENDER_SCALE;
}

function toPx(pt) {
    return pt * RENDER_SCALE;
}

function normalizeBox(box, pageWidthPt, pageHeightPt) {
    const width = clamp(box.width, MIN_WIDTH_PT, pageWidthPt);
    const height = clamp(box.height, MIN_HEIGHT_PT, pageHeightPt);

    return {
        x: round2(clamp(box.x, 0, Math.max(0, pageWidthPt - width))),
        y: round2(clamp(box.y, 0, Math.max(0, pageHeightPt - height))),
        width: round2(width),
        height: round2(height),
    };
}

function moveBox(start, dx, dy, pageWidthPt, pageHeightPt) {
    return normalizeBox(
        { ...start, x: start.x + dx, y: start.y + dy },
        pageWidthPt,
        pageHeightPt,
    );
}

function resizeBox(start, dir, dx, dy, pageWidthPt, pageHeightPt) {
    const left0 = start.x;
    const right0 = start.x + start.width;
    const top0 = start.y;
    const bottom0 = start.y + start.height;

    let left = left0;
    let right = right0;
    let top = top0;
    let bottom = bottom0;

    if (dir.includes('w')) {
        left = clamp(left0 + dx, 0, right0 - MIN_WIDTH_PT);
    }
    if (dir.includes('e')) {
        right = clamp(right0 + dx, left0 + MIN_WIDTH_PT, pageWidthPt);
    }
    if (dir.includes('n')) {
        top = clamp(top0 + dy, 0, bottom0 - MIN_HEIGHT_PT);
    }
    if (dir.includes('s')) {
        bottom = clamp(bottom0 + dy, top0 + MIN_HEIGHT_PT, pageHeightPt);
    }

    return normalizeBox(
        { x: left, y: top, width: right - left, height: bottom - top },
        pageWidthPt,
        pageHeightPt,
    );
}

function initSignaturePlacement(root) {
    if (!root || root.dataset.sigInit === 'true') {
        return;
    }
    root.dataset.sigInit = 'true';

    const previewUrl = root.dataset.previewUrl;
    const pagesEl = root.querySelector('#sig-pages');
    const selectEl = root.querySelector('#sig-page-select');
    const statusEl = root.querySelector('#sig-status');
    const errorEl = root.querySelector('#sig-error');
    const form = document.getElementById(root.dataset.formId || 'sign-form');

    if (!previewUrl || !pagesEl || !selectEl || !form) {
        return;
    }

    const inputs = {
        pageNumber: form.querySelector('[name="page_number"]'),
        x: form.querySelector('[name="coord_x"]'),
        y: form.querySelector('[name="coord_y"]'),
        width: form.querySelector('[name="width"]'),
        height: form.querySelector('[name="height"]'),
    };

    const readouts = {
        page: root.querySelector('#sig-readout-page'),
        x: root.querySelector('#sig-readout-x'),
        y: root.querySelector('#sig-readout-y'),
        width: root.querySelector('#sig-readout-width'),
        height: root.querySelector('#sig-readout-height'),
    };

    const initialState = {
        pageNumber: parseInt(inputs.pageNumber?.value, 10) || 1,
        x: parseFloat(inputs.x?.value),
        y: parseFloat(inputs.y?.value),
        width: parseFloat(inputs.width?.value),
        height: parseFloat(inputs.height?.value),
    };

    const state = {
        pages: [],
        activePage: initialState.pageNumber,
        pdf: null,
        destroyed: false,
    };

    function setStatus(message) {
        if (statusEl) {
            statusEl.textContent = message;
            statusEl.hidden = !message;
        }
    }

    function setError(message) {
        if (errorEl) {
            errorEl.textContent = message;
            errorEl.hidden = !message;
        }
    }

    function syncForm(page) {
        const { box } = page;

        if (inputs.pageNumber) {
            inputs.pageNumber.value = String(page.pageNumber);
        }
        if (inputs.x) {
            inputs.x.value = box.x.toFixed(2);
        }
        if (inputs.y) {
            inputs.y.value = box.y.toFixed(2);
        }
        if (inputs.width) {
            inputs.width.value = box.width.toFixed(2);
        }
        if (inputs.height) {
            inputs.height.value = box.height.toFixed(2);
        }

        if (readouts.page) {
            readouts.page.textContent = String(page.pageNumber);
        }
        if (readouts.x) {
            readouts.x.textContent = box.x.toFixed(2);
        }
        if (readouts.y) {
            readouts.y.textContent = box.y.toFixed(2);
        }
        if (readouts.width) {
            readouts.width.textContent = box.width.toFixed(2);
        }
        if (readouts.height) {
            readouts.height.textContent = box.height.toFixed(2);
        }
    }

    function paintBox(page) {
        const { box, boxEl } = page;
        const handle = HANDLE_SIZE_PX / 2;

        boxEl.style.left = `${toPx(box.x)}px`;
        boxEl.style.top = `${toPx(box.y)}px`;
        boxEl.style.width = `${toPx(box.width)}px`;
        boxEl.style.height = `${toPx(box.height)}px`;

        for (const dir of HANDLE_DIRS) {
            const handleEl = boxEl.querySelector(`[data-dir="${dir}"]`);
            if (!handleEl) {
                continue;
            }
            handleEl.style.left = dir.includes('w') ? `${-handle}px` : dir.includes('e') ? `calc(100% - ${handle}px)` : '50%';
            handleEl.style.top = dir.includes('n') ? `${-handle}px` : dir.includes('s') ? `calc(100% - ${handle}px)` : '50%';
        }
    }

    function applyBox(page, box) {
        page.box = normalizeBox(box, page.widthPt, page.heightPt);
        paintBox(page);
        syncForm(page);
    }

    function setActivePage(pageNumber, { scroll = false } = {}) {
        const page = state.pages.find((entry) => entry.pageNumber === pageNumber);
        if (!page) {
            return;
        }

        state.activePage = pageNumber;

        for (const entry of state.pages) {
            const active = entry.pageNumber === pageNumber;
            entry.surfaceEl.classList.toggle('ring-2', active);
            entry.surfaceEl.classList.toggle('ring-indigo-500', active);
            entry.labelEl.classList.toggle('bg-indigo-600', active);
            entry.labelEl.classList.toggle('text-white', active);
            entry.labelEl.classList.toggle('bg-gray-700', !active);
            entry.boxEl.classList.toggle('pointer-events-none', !active);
            entry.boxEl.classList.toggle('opacity-40', !active);
            entry.boxEl.classList.toggle('opacity-100', active);
        }

        if (selectEl.value !== String(pageNumber)) {
            selectEl.value = String(pageNumber);
        }

        syncForm(page);

        if (scroll) {
            page.surfaceEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function startPointerOperation(event, page, dir) {
        if (page.pageNumber !== state.activePage || event.button > 0) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const target = event.currentTarget;
        const startX = event.clientX;
        const startY = event.clientY;
        const startBox = { ...page.box };

        target.setPointerCapture(event.pointerId);

        const onMove = (moveEvent) => {
            const dx = toPt(moveEvent.clientX - startX);
            const dy = toPt(moveEvent.clientY - startY);
            const next = dir === 'drag'
                ? moveBox(startBox, dx, dy, page.widthPt, page.heightPt)
                : resizeBox(startBox, dir, dx, dy, page.widthPt, page.heightPt);

            applyBox(page, next);
        };

        const onEnd = (upEvent) => {
            target.removeEventListener('pointermove', onMove);
            target.removeEventListener('pointerup', onEnd);
            target.removeEventListener('pointercancel', onEnd);

            if (target.hasPointerCapture(upEvent.pointerId)) {
                target.releasePointerCapture(upEvent.pointerId);
            }
        };

        target.addEventListener('pointermove', onMove);
        target.addEventListener('pointerup', onEnd);
        target.addEventListener('pointercancel', onEnd);
    }

    function buildBox(page) {
        const boxEl = document.createElement('div');
        boxEl.className = 'absolute bg-indigo-500/40 border-4 border-indigo-600 cursor-move select-none shadow opacity-100';
        boxEl.style.touchAction = 'none';

        const labelEl = document.createElement('span');
        labelEl.className = 'absolute -top-7 left-0 bg-indigo-600 text-white text-xs font-medium px-2 py-0.5 rounded shadow whitespace-nowrap';
        labelEl.textContent = 'TTD';
        boxEl.appendChild(labelEl);

        boxEl.addEventListener('pointerdown', (event) => startPointerOperation(event, page, 'drag'));

        for (const dir of HANDLE_DIRS) {
            const handleEl = document.createElement('div');
            handleEl.dataset.dir = dir;
            handleEl.className = 'absolute w-3 h-3 bg-white border-2 border-indigo-600 rounded-sm shadow';
            handleEl.style.touchAction = 'none';
            handleEl.style.cursor = dir === 'nw' || dir === 'se' ? 'nwse-resize' : 'nesw-resize';
            handleEl.addEventListener('pointerdown', (event) => startPointerOperation(event, page, dir));
            boxEl.appendChild(handleEl);
        }

        return boxEl;
    }

    function buildPage(pdfPage, pageNumber) {
        const pageWidthPt = pdfPage.getViewport({ scale: 1 }).width;
        const pageHeightPt = pdfPage.getViewport({ scale: 1 }).height;
        const cssViewport = pdfPage.getViewport({ scale: RENDER_SCALE });
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        const wrapperEl = document.createElement('div');
        wrapperEl.className = 'space-y-1';

        const labelEl = document.createElement('button');
        labelEl.type = 'button';
        labelEl.className = 'px-2 py-0.5 text-xs font-medium bg-gray-700 text-gray-200 rounded hover:bg-gray-600';
        labelEl.textContent = `Halaman ${pageNumber}`;
        labelEl.addEventListener('click', () => setActivePage(pageNumber, { scroll: false }));

        const surfaceEl = document.createElement('div');
        surfaceEl.className = 'relative shadow bg-white mx-auto';
        surfaceEl.style.width = `${cssViewport.width}px`;
        surfaceEl.style.height = `${cssViewport.height}px`;
        surfaceEl.addEventListener('pointerdown', (event) => {
            if (event.target === surfaceEl) {
                setActivePage(pageNumber, { scroll: false });
            }
        });

        const canvas = document.createElement('canvas');
        canvas.className = 'block';
        canvas.width = Math.floor(cssViewport.width * dpr);
        canvas.height = Math.floor(cssViewport.height * dpr);
        canvas.style.width = `${cssViewport.width}px`;
        canvas.style.height = `${cssViewport.height}px`;

        surfaceEl.appendChild(canvas);

        const page = {
            pageNumber,
            widthPt: pageWidthPt,
            heightPt: pageHeightPt,
            renderViewport: pdfPage.getViewport({ scale: RENDER_SCALE * dpr }),
            pdfPage,
            canvas,
            wrapperEl,
            labelEl,
            surfaceEl,
            boxEl: null,
            box: null,
        };

        page.boxEl = buildBox(page);
        surfaceEl.appendChild(page.boxEl);

        let initial = DEFAULT_BOX;
        if (pageNumber === initialState.pageNumber) {
            initial = {
                x: Number.isFinite(initialState.x) ? initialState.x : DEFAULT_BOX.x,
                y: Number.isFinite(initialState.y) ? initialState.y : DEFAULT_BOX.y,
                width: Number.isFinite(initialState.width) ? initialState.width : DEFAULT_BOX.width,
                height: Number.isFinite(initialState.height) ? initialState.height : DEFAULT_BOX.height,
            };
        }

        page.box = normalizeBox(initial, pageWidthPt, pageHeightPt);

        wrapperEl.appendChild(labelEl);
        wrapperEl.appendChild(surfaceEl);

        return page;
    }

    async function renderPage(page) {
        const ctx = page.canvas.getContext('2d');
        await page.pdfPage.render({
            canvasContext: ctx,
            canvas: page.canvas,
            viewport: page.renderViewport,
            background: '#ffffff',
        }).promise;
    }

    async function load() {
        if (!previewUrl) {
            return;
        }

        setStatus('Memuat PDF…');
        setError('');

        try {
            const loadingTask = pdfjsLib.getDocument({ url: previewUrl });

            loadingTask.onProgress = ({ loaded, total }) => {
                if (total > 0) {
                    const percent = Math.min(100, Math.round((loaded / total) * 100));
                    setStatus(`Memuat PDF… ${percent}%`);
                } else {
                    setStatus('Memuat PDF…');
                }
            };

            const pdf = await loadingTask.promise;
            if (state.destroyed) {
                return;
            }

            state.pdf = pdf;
            pagesEl.innerHTML = '';
            selectEl.innerHTML = '';

            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber++) {
                const pdfPage = await pdf.getPage(pageNumber);
                const page = buildPage(pdfPage, pageNumber);
                state.pages.push(page);
                pagesEl.appendChild(page.wrapperEl);

                const option = document.createElement('option');
                option.value = String(pageNumber);
                option.textContent = `Halaman ${pageNumber}`;
                selectEl.appendChild(option);
            }

            for (const page of state.pages) {
                paintBox(page);
                await renderPage(page);
            }

            setActivePage(
                Math.min(Math.max(state.activePage, 1), pdf.numPages),
                { scroll: false },
            );

            setStatus(`PDF siap — ${pdf.numPages} halaman. Seret kotak tanda tangan untuk mengatur posisi.`);
        } catch (error) {
            console.error('Gagal memuat PDF preview:', error);
            setStatus('');
            setError('Gagal memuat pratinjau PDF. Muat ulang halaman atau coba lagi nanti.');
        }
    }

    selectEl.addEventListener('change', () => {
        const pageNumber = parseInt(selectEl.value, 10);
        if (Number.isFinite(pageNumber)) {
            setActivePage(pageNumber, { scroll: true });
        }
    });

    load();

    return {
        getState: () => state,
        destroy: () => {
            state.destroyed = true;
            state.pdf?.destroy?.();
        },
    };
}

function autoInit() {
    const root = document.getElementById('signature-viewer');
    if (root) {
        initSignaturePlacement(root);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInit);
} else {
    autoInit();
}

window.initSignaturePlacement = initSignaturePlacement;

export { initSignaturePlacement };
