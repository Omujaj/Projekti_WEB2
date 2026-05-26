/**
 * 
 * Handles: live search, modals, form validation, charts helper, pagination
 */

/* ---- NAVIGATION TOGGLE ---- */
function toggleNav() {
    document.getElementById('navLinks')?.classList.toggle('open');
}

/* ---- MODAL HELPERS ---- */
function openModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) { overlay.classList.add('active'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    const overlay = document.getElementById(id);
    if (overlay) { overlay.classList.remove('active'); document.body.style.overflow = ''; }
}
// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});
// Close with ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => {
            m.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
});

/* ---- LIVE SEARCH ---- */
function initLiveSearch(inputId, tableBodyId, columns) {
    const input = document.getElementById(inputId);
    const tbody = document.getElementById(tableBodyId);
    if (!input || !tbody) return;
    input.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        const rows = tbody.querySelectorAll('tr');
        let visible = 0;
        rows.forEach(row => {
            const text = Array.from(row.querySelectorAll('td'))
                .filter((_, i) => !columns || columns.includes(i))
                .map(td => td.textContent.toLowerCase())
                .join(' ');
            if (text.includes(q)) { row.style.display = ''; visible++; }
            else row.style.display = 'none';
        });
        // Show empty state
        const emptyRow = tbody.querySelector('.empty-row');
        if (emptyRow) emptyRow.style.display = visible === 0 ? '' : 'none';
    });
}

/* ---- BOOK CARD SEARCH  ---- */
function initCatalogSearch() {
    const input = document.getElementById('catalogSearch');
    const grid  = document.getElementById('booksGrid');
    if (!input || !grid) return;
    input.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        const cards = grid.querySelectorAll('.book-card');
        cards.forEach(card => {
            const text = card.dataset.search || card.textContent.toLowerCase();
            card.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

/* ---- CLIENT-SIDE PAGINATION ---- */
function initPagination(tableBodyId, perPage = 10) {
    const tbody = document.getElementById(tableBodyId);
    const container = document.getElementById(tableBodyId + 'Pagination');
    if (!tbody || !container) return;

    const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / perPage);

    function render() {
        rows.forEach((row, i) => {
            row.style.display = (i >= (currentPage-1)*perPage && i < currentPage*perPage) ? '' : 'none';
        });
        container.innerHTML = '';
        if (totalPages <= 1) return;

        const prev = document.createElement('button');
        prev.className = 'page-btn'; prev.textContent = '←';
        prev.disabled = currentPage === 1;
        prev.onclick = () => { currentPage--; render(); };
        container.appendChild(prev);

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'page-btn' + (i === currentPage ? ' active' : '');
            btn.textContent = i;
            btn.onclick = () => { currentPage = i; render(); };
            container.appendChild(btn);
        }

        const next = document.createElement('button');
        next.className = 'page-btn'; next.textContent = '→';
        next.disabled = currentPage === totalPages;
        next.onclick = () => { currentPage++; render(); };
        container.appendChild(next);
    }
    render();
}

/* ---- PASSWORD STRENGTH ---- */
function initPasswordStrength() {
    const pw = document.getElementById('password');
    const bar = document.getElementById('strengthBar');
    if (!pw || !bar) return;
    pw.addEventListener('input', function() {
        const v = this.value;
        let score = 0;
        if (v.length >= 8)  score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        const colors = ['#ef4444','#f59e0b','#22c55e','#16a34a'];
        const widths = ['25%','50%','75%','100%'];
        bar.style.width  = widths[score-1] || '0%';
        bar.style.background = colors[score-1] || 'transparent';
    });
}

/* ---- IMAGE PREVIEW ---- */
function previewImage(inputId, previewId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
            };
            reader.readAsDataURL(this.files[0]);
        }
    });
}

/* ---- CONFIRM DELETE ---- */
function confirmDelete(form, itemName) {
    if (confirm('Are you sure you want to delete "' + itemName + '"? This action cannot be undone.')) {
        form.submit();
    }
}

/* ---- SORT TABLE ---- */
function sortTable(tableId, colIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr:not(.empty-row)'));
    const asc   = table.dataset.sortCol == colIndex && table.dataset.sortDir === 'asc';
    rows.sort((a, b) => {
        const ta = a.cells[colIndex]?.textContent.trim().toLowerCase() || '';
        const tb = b.cells[colIndex]?.textContent.trim().toLowerCase() || '';
        return asc ? tb.localeCompare(ta) : ta.localeCompare(tb);
    });
    rows.forEach(r => tbody.appendChild(r));
    table.dataset.sortCol = colIndex;
    table.dataset.sortDir = asc ? 'desc' : 'asc';
}

/* ---- INIT ALL ---- */
document.addEventListener('DOMContentLoaded', function() {
    initCatalogSearch();
    initPasswordStrength();
    initLiveSearch('userSearch', 'usersTableBody');
    initLiveSearch('bookSearch', 'booksTableBody');
    initPagination('booksTableBody', 10);
    initPagination('usersTableBody', 10);
    initPagination('borrowsTableBody', 10);
    previewImage('coverInput', 'coverPreview');
});
s