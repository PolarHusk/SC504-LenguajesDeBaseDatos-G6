const AUTH_URL = '../../public/routes/auth.php';
const MODULE_URL = '../../public/routes/admin.php';
const PAYMENT_URL = '../../public/routes/pagos.php';

let tables = {};



let currentTable = 'estados';
let records = [];
let filteredRecords = [];
let selectedRecord = null;
const tableCache = new Map();
const stateCache = new Map();
const locationCache = new Map();
const locationApiCache = new Map();
const moduleGroups = window.APP_MODULES || {};
const COSTA_RICA_LOCATIONS_API = 'https://raw.githubusercontent.com/lfbvyo/ubicaciones/main/public';

const accessView = document.getElementById('accessView');
const adminView = document.getElementById('adminView');
const loginForm = document.getElementById('loginForm');
const loginMessage = document.getElementById('loginMessage');
const logoutBtn = document.getElementById('logoutBtn');
const sessionUser = document.getElementById('sessionUser');
const recordIdField = document.getElementById('recordIdField');
const tableMenu = document.getElementById('tableMenu');
const tableTitle = document.getElementById('tableTitle');
const contentGrid = document.getElementById('contentGrid');
const tableForm = document.getElementById('tableForm');
const recordsPanel = document.getElementById('recordsPanel');
const recordsTab = document.getElementById('recordsTab');
const newRecordTab = document.getElementById('newRecordTab');
const formTitle = document.getElementById('formTitle');
const modeInput = document.getElementById('mode');
const recordIdLabel = document.querySelector('label[for="recordId"]');
let recordIdInput = document.getElementById('recordId');
const extraKeyFields = document.getElementById('extraKeyFields');
const recordNameField = document.getElementById('recordNameField');
let recordNameInput = document.getElementById('recordName');
const extraDataFields = document.getElementById('extraDataFields');
const recordStatusField = document.getElementById('recordStatusField');
const recordStatusSelect = document.getElementById('recordStatus');
const statusLabel = document.getElementById('statusLabel');
const recordNameLabel = document.getElementById('recordNameLabel');
const nameColumnHeader = document.getElementById('nameColumnHeader');
const recordsHead = document.getElementById('recordsHead');
const recordsTable = document.getElementById('recordsTable');
const clearBtn = document.getElementById('clearBtn');
const deleteBtn = document.getElementById('deleteBtn');
const reloadBtn = document.getElementById('reloadBtn');
const messageBox = document.getElementById('messageBox');
const recordSearch = document.getElementById('recordSearch');
const clearFiltersBtn = document.getElementById('clearFiltersBtn');
const totalRecords = document.getElementById('totalRecords');
const activeRecordsLabel = document.getElementById('activeRecordsLabel');
const activeRecords = document.getElementById('activeRecords');
const visibleRecords = document.getElementById('visibleRecords');
const paymentSearchForm = document.getElementById('paymentSearchForm');
const payRegistrationForm = document.getElementById('payRegistrationForm');
const paymentMessage = document.getElementById('paymentMessage');
const paymentResults = document.getElementById('paymentResults');
const paymentDetail = document.getElementById('paymentDetail');
let selectedPaymentPlayer = null;

function init() {
    bindTabs();
    loginForm.addEventListener('submit', login);
    paymentSearchForm.addEventListener('submit', searchPaymentPlayers);
    payRegistrationForm.addEventListener('submit', registerPayment);
    logoutBtn.addEventListener('click', logout);
    tableForm.addEventListener('submit', saveTable);
    tableForm.addEventListener('invalid', showFormValidationError, true);
    tableForm.addEventListener('input', keepDigitsOnly);
    recordsTable.addEventListener('click', handleTableClick);
    deleteBtn.addEventListener('click', deleteRecord);
    clearBtn.addEventListener('click', clearForm);
    recordsTab.addEventListener('click', () => activateRecordTab('records', false));
    newRecordTab.addEventListener('click', () => {
        clearForm();
        activateRecordTab('form', true);
    });
    bindRecordTabKeyboard();
    reloadBtn.addEventListener('click', () => loadRecords(true));
    recordSearch.addEventListener('input', applyRecordFilters);
    clearFiltersBtn.addEventListener('click', clearRecordFilters);
    checkSession();
}

async function searchPaymentPlayers(event) {
    event.preventDefault();
    const cedulaInput = document.getElementById('paymentCedula');
    const cedula = cedulaInput.value.replace(/\D/g, '');
    cedulaInput.value = cedula;
    const nombre = '';
    if (!/^\d{9}$/.test(cedula)) return showMessage(paymentMessage, 'Digite una cedula de 9 digitos.', true);
    if (!cedula && !nombre) return showMessage(paymentMessage, 'Digite una cédula o el nombre completo.', true);
    try {
        const params = new URLSearchParams({ action: 'buscar', cedula });
        const data = await requestJson(`${PAYMENT_URL}?${params}`);
        paymentDetail.classList.add('hidden');
        selectedPaymentPlayer = null;
        paymentResults.classList.remove('hidden');
        const records = data.records || [];
        paymentResults.innerHTML = records.length ? records.map((player) => `<button type="button" class="payment-player-option" data-player-id="${escapeHtml(player.ID_JUGADOR)}"><strong>${escapeHtml(player.NOMBRE_COMPLETO)}</strong><span>Cédula: ${escapeHtml(player.CEDULA)} · ${escapeHtml(player.NOMBRE_CATEGORIA)}</span></button>`).join('') : '<p class="hint">No se encontraron jugadores con esos datos.</p>';
        paymentResults.querySelectorAll('[data-player-id]').forEach((button) => button.addEventListener('click', () => loadPaymentPlayer(button.dataset.playerId)));
        clearMessage(paymentMessage);
    } catch (error) { showMessage(paymentMessage, error.message, true); }
}

async function loadPaymentPlayer(playerId) {
    try {
        const data = await requestJson(`${PAYMENT_URL}?action=detalle&jugador_id=${encodeURIComponent(playerId)}`);
        selectedPaymentPlayer = data.player;
        paymentResults.classList.add('hidden');
        paymentDetail.classList.remove('hidden');
        document.getElementById('paymentPlayer').innerHTML = `<h2>${escapeHtml(data.player.NOMBRE_COMPLETO)}</h2><p>Cédula: ${escapeHtml(data.player.CEDULA)} · Categoría: ${escapeHtml(data.player.NOMBRE_CATEGORIA)}</p>`;
        const payments = data.payments || [];
        const pendingPayments = payments;
        renderPendingPayments(pendingPayments);
        preparePaymentForm(pendingPayments);
        clearMessage(paymentMessage);
    } catch (error) { showMessage(paymentMessage, error.message, true); }
}

function renderPendingPayments(pendingPayments) {
    const pendingList = document.getElementById('paymentPendingList');
    pendingList.innerHTML = pendingPayments.length
        ? pendingPayments.map((payment) => `<button type="button" class="payment-pending-option" data-invoice-id="${escapeHtml(invoiceNumber(payment.ID_FACTURACION_INSCRIPCION))}"><strong>${monthName(payment.MES)} ${escapeHtml(payment.ANIO)}</strong><span>Factura #${escapeHtml(invoiceNumber(payment.ID_FACTURACION_INSCRIPCION))} · ${formatCurrency(payment.MONTO)}</span></button>`).join('')
        : '<p class="hint">No tiene mensualidades pendientes por pagar.</p>';

    pendingList.querySelectorAll('[data-invoice-id]').forEach((button) => button.addEventListener('click', () => {
        const option = document.querySelector(`#paymentMonth option[data-invoice-id="${button.dataset.invoiceId}"]`);
        if (!option) return;
        document.getElementById('paymentMonth').value = option.value;
        updatePaymentAmount();
        document.getElementById('payRegistrationForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }));
}

function preparePaymentForm(pendingPayments) {
    const form = document.getElementById('payRegistrationForm');
    const month = document.getElementById('paymentMonth');
    form.classList.toggle('hidden', !pendingPayments.length);
    month.innerHTML = pendingPayments.map((payment) => `<option value="${escapeHtml(payment.MES)}" data-year="${escapeHtml(payment.ANIO)}" data-amount="${escapeHtml(payment.MONTO)}" data-invoice-id="${escapeHtml(invoiceNumber(payment.ID_FACTURACION_INSCRIPCION))}">${monthName(payment.MES)} ${escapeHtml(payment.ANIO)}</option>`).join('');
    month.onchange = updatePaymentAmount;
    updatePaymentAmount();
    document.getElementById('paymentMethod').value = '';
    document.getElementById('paymentReference').value = '';
    document.getElementById('paymentNotes').value = '';
}

async function registerPayment(event) {
    event.preventDefault();
    if (!selectedPaymentPlayer) return;
    const period = document.getElementById('paymentMonth').options[document.getElementById('paymentMonth').selectedIndex];
    const payload = {
        jugador_id: selectedPaymentPlayer.ID_JUGADOR,
        mes: period?.value,
        anio: period?.dataset.year,
        metodo_pago: document.getElementById('paymentMethod').value,
        referencia: document.getElementById('paymentReference').value.trim(),
        observaciones: document.getElementById('paymentNotes').value.trim(),
    };
    try {
        const data = await requestJson(`${PAYMENT_URL}?action=pagar`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        showMessage(paymentMessage, data.message);
        printInvoice(data.invoice);
        await loadPaymentPlayer(selectedPaymentPlayer.ID_JUGADOR);
    } catch (error) { showMessage(paymentMessage, error.message, true); }
}

function printInvoice(invoice) {
    const popup = window.open('', '_blank', 'width=760,height=800');
    if (!popup) return showMessage(paymentMessage, 'Pago registrado. Permita las ventanas emergentes para imprimir la factura.', false);
    const invoiceId = invoiceNumber(invoice.id);
    const logoUrl = new URL('logo.jpg', window.location.href).href;
    popup.document.write(`<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Factura ${escapeHtml(invoiceId)}</title><style>
        @page{margin:14mm}*{box-sizing:border-box}body{margin:0;background:#edf2f8;color:#111a2e;font-family:Inter,Arial,sans-serif;padding:32px}.invoice{max-width:720px;margin:auto;background:#fff;border:1px solid #d5dce7;box-shadow:0 18px 42px rgba(16,26,50,.16);overflow:hidden}.invoice-header{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px 30px;background:linear-gradient(135deg,#101a32,#245a9b);color:#fff;border-bottom:5px solid #c89432}.invoice-brand{display:flex;align-items:center;gap:14px}.invoice-logo{width:64px;height:64px;object-fit:contain;border-radius:50%;background:#fff;padding:4px}.invoice-brand h1{margin:0;font-size:23px}.invoice-brand p,.invoice-number{margin:4px 0 0;color:#dceafd;font-size:13px}.invoice-number{text-align:right}.invoice-number strong{display:block;color:#f2d27d;font-size:18px;letter-spacing:.04em}.invoice-body{padding:30px}.invoice-body h2{margin:0 0 6px;color:#245a9b;font-size:22px}.invoice-body>p{margin:0 0 24px;color:#66748a}.details{margin:0;border-top:1px solid #d5dce7}.detail{display:grid;grid-template-columns:190px 1fr;gap:18px;padding:12px 0;border-bottom:1px solid #e7ecf3}.detail dt{font-weight:700;color:#66748a}.detail dd{margin:0;color:#111a2e}.total{display:flex;justify-content:space-between;align-items:center;margin-top:26px;padding:18px 20px;background:#edf2f8;border-left:5px solid #c89432;color:#245a9b;font-size:18px;font-weight:800}.total strong{font-size:24px}.invoice-footer{padding:18px 30px;background:#101a32;color:#dceafd;font-size:12px;text-align:center}.print-button{display:block;margin:24px auto 0;padding:11px 20px;border:0;border-radius:7px;background:#245a9b;color:#fff;font-weight:700;cursor:pointer}@media print{body{background:#fff;padding:0}.invoice{box-shadow:none;border:0}.print-button{display:none}}</style></head><body><article class="invoice"><header class="invoice-header"><div class="invoice-brand"><img class="invoice-logo" src="${logoUrl}" alt="Logo Academia Leiva"><div><h1>Academia Leiva</h1><p>Formación deportiva</p></div></div><div class="invoice-number">Factura de inscripción<strong>#${escapeHtml(invoiceId)}</strong></div></header><main class="invoice-body"><h2>Comprobante de pago</h2><p>Gracias por formar parte de nuestra academia.</p><dl class="details"><div class="detail"><dt>Jugador</dt><dd>${escapeHtml(invoice.player.NOMBRE_COMPLETO)}</dd></div><div class="detail"><dt>Cédula</dt><dd>${escapeHtml(invoice.player.CEDULA)}</dd></div><div class="detail"><dt>Categoría</dt><dd>${escapeHtml(invoice.player.NOMBRE_CATEGORIA)}</dd></div><div class="detail"><dt>Período</dt><dd>${monthName(invoice.mes)} ${escapeHtml(invoice.anio)}</dd></div><div class="detail"><dt>Fecha de pago</dt><dd>${formatDate(invoice.fecha_pago)}</dd></div><div class="detail"><dt>Método de pago</dt><dd>${escapeHtml(invoice.metodo_pago)}</dd></div><div class="detail"><dt>Referencia</dt><dd>${escapeHtml(invoice.referencia || 'No indicada')}</dd></div></dl><div class="total"><span>Total pagado</span><strong>${formatCurrency(invoice.monto)}</strong></div></main><footer class="invoice-footer">Academia Leiva · Comprobante generado por el sistema administrativo</footer></article><button class="print-button" onclick="window.print()">Imprimir factura</button></body></html>`);
    popup.document.close();
    popup.focus();
}

function monthName(month) { return ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'][Number(month) - 1] || ''; }
function formatCurrency(amount) { return new Intl.NumberFormat('es-CR', { style: 'currency', currency: 'CRC', maximumFractionDigits: 2 }).format(Number(amount || 0)); }
function formatDate(value) { if (!value) return '—'; const [year, month, day] = String(value).slice(0, 10).split('-'); return year && month && day ? `${day}/${month}/${year}` : String(value); }

function bindTabs() {
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = document.querySelector(tab.dataset.bsTarget);
            if (!target) return;

            document.querySelectorAll('[data-bs-toggle="pill"]').forEach((item) => item.classList.remove('active'));
            document.querySelectorAll('.tab-pane').forEach((pane) => pane.classList.remove('show', 'active'));

            tab.classList.add('active');
            target.classList.add('show', 'active');
        });
    });
}

async function checkSession() {
    try {
        const data = await requestJson(`${AUTH_URL}?action=status`);
        if (data.authenticated) showAdmin(data.user);
    } catch (error) {
        showAccess();
    }
}

async function loadStates() {
    try {
        const data = await requestJson(`${MODULE_URL}?modulo=configuracion&accion=estados`);
        const states = data.records || [];
        stateCache.clear();
        states.forEach((state) => {
            stateCache.set(String(state.ID_ESTADO), state.NOMBRE_ESTADO || `Estado ${state.ID_ESTADO}`);
        });
        renderStateOptions();
    } catch (error) {
        renderStateOptions();
    }
}

function renderStateOptions() {
    const currentValue = recordStatusSelect.value || '1';
    const options = [...stateCache.entries()]
        .sort((a, b) => Number(a[0]) - Number(b[0]))
        .map(([id, name]) => `<option value="${escapeHtml(id)}">${escapeHtml(name)}</option>`);

    recordStatusSelect.innerHTML = options.length
        ? options.join('')
        : '<option value="1">Activo</option><option value="2">Inactivo</option>';
    recordStatusSelect.value = stateCache.has(currentValue) ? currentValue : getDefaultStateId();
}

function getDefaultStateId() {
    if (stateCache.has('1')) return '1';
    const firstState = stateCache.keys().next().value;
    return firstState ? String(firstState) : '1';
}

async function login(event) {
    event.preventDefault();
    const payload = {
        usuario: document.getElementById('loginUser').value.trim(),
        contrasenia: document.getElementById('loginPassword').value.trim(),
    };

    try {
        const data = await requestJson(`${AUTH_URL}?action=login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        loginForm.reset();
        showMessage(loginMessage, data.message);
        showAdmin(data.user);
    } catch (error) {
        showMessage(loginMessage, error.message, true);
    }
}

async function logout() {
    await fetch(`${AUTH_URL}?action=logout`, { method: 'POST' });
    showAccess();
}

async function showAdmin(user) {
    accessView.classList.add('hidden');
    adminView.classList.remove('hidden');
    sessionUser.textContent = user?.nombre || user?.usuario || 'Panel administrativo';

    try {
        const schemaResponse = await requestJson(`${MODULE_URL}?accion=esquema`);
        tables = Object.values(schemaResponse.modules || {}).reduce((allTables, module) => ({
            ...allTables,
            ...(module.tables || {}),
        }), {});
        await loadStates();
        buildMenu();
        applyTableLabels();
        activateRecordTab('records', false);
        loadRecords();
    } catch (error) {
        showMessage(messageBox, error.message, true);
    }
}

function showAccess() {
    adminView.classList.add('hidden');
    accessView.classList.remove('hidden');
}

function buildMenu() {
    tableMenu.innerHTML = '';
    Object.entries(moduleGroups).forEach(([moduleKey, module]) => {
        const availableTables = module.tables.filter((key) => tables[key]);
        if (!availableTables.length) return;

        const moduleMenu = document.createElement('details');
        moduleMenu.className = `module-menu module-${moduleKey}`;
        moduleMenu.open = availableTables.includes(currentTable);

        const moduleId = `module-menu-${moduleKey}`;
        const toggle = document.createElement('summary');
        toggle.className = 'module-toggle';
        toggle.setAttribute('aria-controls', moduleId);

        const toggleText = document.createElement('span');
        toggleText.className = 'module-toggle-text';
        toggleText.textContent = module.label;

        toggle.append(toggleText);

        const submenu = document.createElement('div');
        submenu.id = moduleId;
        submenu.className = 'submenu';
        submenu.setAttribute('role', 'group');
        submenu.setAttribute('aria-label', `Tablas de ${module.label}`);

        moduleMenu.append(toggle, submenu);
        tableMenu.appendChild(moduleMenu);

        availableTables.forEach((key) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = tables[key].title;
            button.className = `submenu-button${key === currentTable ? ' active' : ''}`;
            if (key === currentTable) button.setAttribute('aria-current', 'page');
            button.addEventListener('click', () => changeTable(key));
            submenu.appendChild(button);
        });
    });
}

function bindRecordTabKeyboard() {
    const tabs = [recordsTab, newRecordTab];

    tabs.forEach((tab, index) => {
        tab.addEventListener('keydown', (event) => {
            let nextIndex = index;
            if (event.key === 'ArrowRight' || event.key === 'ArrowDown') nextIndex = (index + 1) % tabs.length;
            if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') nextIndex = (index - 1 + tabs.length) % tabs.length;
            if (event.key === 'Home') nextIndex = 0;
            if (event.key === 'End') nextIndex = tabs.length - 1;
            if (nextIndex === index) return;

            event.preventDefault();
            tabs[nextIndex].focus();
            activateRecordTab(nextIndex === 0 ? 'records' : 'form', false);
        });
    });
}

function activateRecordTab(tabName, moveFocus = false) {
    const showForm = tabName === 'form';

    recordsTab.classList.toggle('active', !showForm);
    newRecordTab.classList.toggle('active', showForm);
    recordsTab.setAttribute('aria-selected', String(!showForm));
    newRecordTab.setAttribute('aria-selected', String(showForm));
    recordsTab.tabIndex = showForm ? -1 : 0;
    newRecordTab.tabIndex = showForm ? 0 : -1;
    recordsPanel.classList.toggle('hidden', showForm);
    tableForm.classList.toggle('hidden', !showForm);

    if (moveFocus) {
        window.requestAnimationFrame(() => {
            const focusTarget = showForm
                ? (recordNameField.classList.contains('hidden') ? recordIdInput : recordNameInput)
                : recordSearch;
            focusTarget?.focus();
        });
    }
}

function getModuleForTable(tableKey) {
    const moduleEntry = Object.entries(moduleGroups).find(([, module]) => module.tables.includes(tableKey));
    return moduleEntry ? moduleEntry[0] : '';
}

function tableUrl(tableKey) {
    const moduleKey = getModuleForTable(tableKey);
    return `${MODULE_URL}?modulo=${encodeURIComponent(moduleKey)}&tabla=${encodeURIComponent(tableKey)}`;
}

function changeTable(key) {
    currentTable = key;
    clearRecordFilters();
    clearForm();
    buildMenu();
    applyTableLabels();
    activateRecordTab('records', false);
    loadRecords();
}

function applyTableLabels() {
    const table = tables[currentTable];
    const firstPk = table.pkFields[0];
    const firstField = table.fields[0];
    const isExpanded = isComplexTable(table);
    const readOnly = table.readOnly === true;

    tableTitle.textContent = table.title;
    recordsPanel.setAttribute('aria-label', `Registros de ${table.title}`);
    contentGrid.classList.toggle('expanded-form', isExpanded);
    tableForm.classList.toggle('expanded', isExpanded);
    recordIdField.classList.toggle('hidden', table.autoId);
    ensurePrimaryKeyControl(firstPk, table.autoId);
    recordIdInput.required = !table.autoId;
    recordIdLabel.textContent = firstPk.label;
    recordIdInput.placeholder = firstPk.label;
    recordIdInput.dataset.fieldKey = firstPk.key;
    renderDynamicInputs(extraKeyFields, table.pkFields.slice(1));

    if (firstField) {
        ensurePrimaryFieldControl(firstField);
        recordNameField.classList.remove('hidden');
        recordNameInput.required = true;
        recordNameLabel.textContent = firstField.label;
        if (recordNameInput.tagName !== 'SELECT') {
            recordNameInput.type = firstField.type;
            recordNameInput.step = firstField.type === 'number' ? 'any' : '';
        }
        recordNameInput.placeholder = firstField.label;
        recordNameInput.dataset.fieldKey = firstField.key;
    } else {
        recordNameField.classList.add('hidden');
        recordNameInput.required = false;
        recordNameInput.dataset.fieldKey = '';
    }

    renderDynamicInputs(extraDataFields, table.fields.slice(1));
    loadDynamicSelectOptions(table);
    if (table.hasAddress) {
        extraDataFields.insertAdjacentHTML('beforeend', renderAddressFields());
        loadLocationOptions();
    }
    updateStatusFieldVisibility();
    deleteBtn.classList.toggle('hidden', currentTable === 'estados' || readOnly);
    newRecordTab.classList.toggle('hidden', readOnly);
    newRecordTab.setAttribute('aria-hidden', String(readOnly));
    if (readOnly) activateRecordTab('records', false);
    setEditOnlyFields(false);
}

function ensurePrimaryFieldControl(field) {
    const wantsSelect = field.type === 'select';
    const isSelect = recordNameInput.tagName === 'SELECT';
    if (wantsSelect === isSelect) return;

    const replacement = document.createElement(wantsSelect ? 'select' : 'input');
    replacement.id = 'recordName';
    replacement.className = wantsSelect ? 'form-select' : 'form-control';
    replacement.dataset.fieldKey = field.key;
    replacement.required = true;
    if (wantsSelect) {
        replacement.innerHTML = '<option value="">Seleccione una opcion</option>';
    } else {
        replacement.type = field.type || 'text';
        replacement.maxLength = 100;
        replacement.placeholder = 'Digite el valor';
    }
    const currentControl = recordNameInput.parentElement?.classList.contains('searchable-select')
        ? recordNameInput.parentElement
        : recordNameInput;
    currentControl.replaceWith(replacement);
    recordNameInput = replacement;
}

function renderDynamicInputs(container, fields) {
    container.innerHTML = fields.map((field) => `
        <div class="field-control ${isFullRowField(field) ? 'wide-field' : ''} ${field.type === 'checkboxes' ? 'checklist-field' : ''} ${field.editOnly ? 'edit-only-field hidden' : ''} ${field.createOnly ? 'create-only-field' : ''}">
            <label ${field.type === 'checkboxes' ? `id="label_${escapeHtml(field.key)}"` : `for="field_${escapeHtml(field.key)}"`}>${escapeHtml(field.label)}</label>
            ${renderFieldControl(field)}
        </div>
    `).join('');
}

function invoiceNumber(value) {
    const invoiceId = String(value ?? '').trim();
    return invoiceId || 'No disponible';
}

function renderFieldControl(field) {
    if (field.type === 'checkboxes') {
        return `
            <div id="field_${field.key}" class="checklist dynamic-field uniform-input" data-field-key="${escapeHtml(field.key)}" data-checkboxes="true" role="group" aria-labelledby="label_${escapeHtml(field.key)}">
                <p class="checklist-hint">Seleccione una o varias posiciones.</p>
                <div class="checklist-options"></div>
            </div>
        `;
    }

    if (field.type === 'select' || field.type === 'multiselect') {
        return `
            <select id="field_${field.key}" class="form-select dynamic-field uniform-input ${field.type === 'multiselect' ? 'multi-select' : ''}" data-field-key="${escapeHtml(field.key)}" ${field.type === 'multiselect' ? 'multiple size="6"' : ''} required>
                ${field.type === 'multiselect' ? '' : '<option value="">Seleccione una opcion</option>'}
            </select>
        `;
    }

    if (isWideField(field)) {
        return `
            <textarea
                id="field_${field.key}"
                class="form-control dynamic-field uniform-input"
                data-field-key="${escapeHtml(field.key)}"
                placeholder="${escapeHtml(field.label)}"
                rows="3"
                required></textarea>
        `;
    }

    const step = field.type === 'number' ? ' step="any"' : '';
    const pattern = field.pattern ? ` pattern="${escapeHtml(field.pattern)}"` : '';
    const maxLength = field.maxLength ? ` maxlength="${Number(field.maxLength)}"` : '';
    const inputMode = field.inputMode ? ` inputmode="${escapeHtml(field.inputMode)}"` : '';
    const digitsOnly = field.digitsOnly ? ' data-digits-only="true"' : '';
    const required = field.editOnly ? '' : ' required';
    return `
        <input
            type="${escapeHtml(field.type)}"
            id="field_${field.key}"
            class="form-control dynamic-field uniform-input"
            data-field-key="${escapeHtml(field.key)}"
            placeholder="${escapeHtml(field.label)}"
            ${step}
            ${pattern}
            ${maxLength}
            ${inputMode}
            ${digitsOnly}
            ${required}>
    `;
}

async function loadDynamicSelectOptions(table) {
    const selectFields = [...(table.pkFields || []), ...(table.fields || [])]
        .filter((field) => ['select', 'multiselect', 'checkboxes'].includes(field.type) && (field.optionsTable || Array.isArray(field.options)));

    await Promise.all(selectFields.map(async (field) => {
        const input = getDynamicInput(field.key);
        if (!input) return;

        try {
            if (Array.isArray(field.options)) {
                populateSelectOptions(input, field.options, field, 'value', 'label');
                return;
            }

            const optionsCacheKey = `options:${field.optionsModule || ''}:${field.optionsTable}`;
            if (!tableCache.has(optionsCacheKey)) {
                const optionsUrl = field.optionsModule
                    ? `${MODULE_URL}?modulo=${encodeURIComponent(field.optionsModule)}&tabla=${encodeURIComponent(field.optionsTable)}`
                    : tableUrl(field.optionsTable);
                const data = await requestJson(optionsUrl);
                tableCache.set(optionsCacheKey, data.records || []);
            }
            const options = tableCache.get(optionsCacheKey) || [];
            if (field.type === 'checkboxes') {
                const selectedValues = [...input.querySelectorAll('input:checked')].map((option) => option.value);
                const optionsContainer = input.querySelector('.checklist-options');
                optionsContainer.innerHTML = options.map((option) => {
                    const value = String(option[field.optionValue]);
                    const checked = selectedValues.includes(value) ? ' checked' : '';
                    const checkboxId = `field_${field.key}_${value}`;
                    return `<div class="checklist-option"><input id="${escapeHtml(checkboxId)}" name="${escapeHtml(field.key)}[]" type="checkbox" value="${escapeHtml(value)}"${checked}><label for="${escapeHtml(checkboxId)}">${escapeHtml(option[field.optionLabel])}</label></div>`;
                }).join('');
                configureChecklistValidation(input);
                return;
            }

            populateSelectOptions(input, options, field, field.optionValue, field.optionLabel);
        } catch (error) {
            showMessage(messageBox, error.message, true);
        }
    }));
}

function updatePaymentAmount() {
    const month = document.getElementById('paymentMonth');
    const option = month.options[month.selectedIndex];
    document.getElementById('paymentAmount').value = option?.dataset.amount || '';
    document.getElementById('paymentYear').value = option?.dataset.year || '';
}

function populateSelectOptions(input, options, field, valueColumn, labelColumn) {
    const selectedValues = input.multiple
        ? [...input.selectedOptions].map((option) => option.value)
        : [input.value];
    const placeholder = input.multiple ? '' : '<option value="">Seleccione una opcion</option>';
    input.innerHTML = [placeholder, ...options.map((option) => {
        const label = getOptionLabel(option, { ...field, optionValue: valueColumn, optionLabel: labelColumn });
        return `<option value="${escapeHtml(option[valueColumn])}" data-search-label="${escapeHtml(label)}">${escapeHtml(label)}</option>`;
    })].filter(Boolean).join('');
    if (input.multiple) {
        [...input.options].forEach((option) => {
            option.selected = selectedValues.includes(option.value);
        });
    } else {
        input.value = selectedValues[0] || '';
    }
    ensureSelectSearch(input);
}

function ensureSelectSearch(select) {
    if (!select || select.tagName !== 'SELECT' || select.multiple) return;

    let wrapper = select.parentElement?.classList.contains('searchable-select') ? select.parentElement : null;
    let search;
    let suggestionList;
    if (!wrapper) {
        wrapper = document.createElement('div');
        wrapper.className = 'searchable-select';
        search = document.createElement('input');
        search.type = 'text';
        search.className = 'select-search';
        search.placeholder = 'Escriba para buscar';
        search.required = select.required;
        search.setAttribute('aria-label', `Buscar en ${select.dataset.fieldKey || 'opciones'}`);
        suggestionList = document.createElement('div');
        suggestionList.className = 'combo-results hidden';
        suggestionList.setAttribute('role', 'listbox');

        select.replaceWith(wrapper);
        wrapper.append(search, suggestionList, select);
        select.hidden = true;
        select.required = false;
        select.dataset.searchReady = 'true';

        search.addEventListener('input', () => {
            const term = search.value.trim().toLocaleLowerCase('es-CR');
            const match = [...select.options].find((option) => option.value
                && (option.dataset.searchLabel || option.textContent || '').toLocaleLowerCase('es-CR') === term);
            select.value = match?.value || '';
            search.setCustomValidity(search.value && !match ? 'Seleccione una opcion de la lista.' : '');
            renderComboResults(select, search.value);
        });
        search.addEventListener('focus', () => renderComboResults(select, search.value));
        suggestionList.addEventListener('click', (event) => {
            const option = event.target.closest('[data-option-value]');
            if (!option) return;
            select.value = option.dataset.optionValue;
            search.value = option.dataset.optionLabel;
            search.setCustomValidity('');
            suggestionList.classList.add('hidden');
        });
    } else {
        search = wrapper.querySelector('.select-search');
        suggestionList = wrapper.querySelector('.combo-results');
    }

    const selectedOption = [...select.options].find((option) => option.value === select.value);
    search.value = selectedOption?.dataset.searchLabel || selectedOption?.textContent || '';
}

function renderComboResults(select, searchTerm) {
    const wrapper = select.parentElement;
    const results = wrapper?.querySelector('.combo-results');
    if (!results) return;

    const term = String(searchTerm || '').trim().toLocaleLowerCase('es-CR');
    const matches = [...select.options]
        .filter((option) => option.value && (!term || (option.dataset.searchLabel || option.textContent || '').toLocaleLowerCase('es-CR').includes(term)))
        .slice(0, 8);
    results.innerHTML = matches.length
        ? matches.map((option) => `<button type="button" class="combo-option" role="option" data-option-value="${escapeHtml(option.value)}" data-option-label="${escapeHtml(option.dataset.searchLabel || option.textContent || '')}">${escapeHtml(option.dataset.searchLabel || option.textContent || '')}</button>`).join('')
        : '<p class="combo-empty">No hay coincidencias.</p>';
    results.classList.remove('hidden');
}

function resetSelectSearch(select) {
    if (!select || select.tagName !== 'SELECT') return;
    const wrapper = select.parentElement?.classList.contains('searchable-select') ? select.parentElement : null;
    const search = wrapper?.querySelector('.select-search');
    if (search) {
        search.value = '';
        search.setCustomValidity('');
    }
    wrapper?.querySelector('.combo-results')?.classList.add('hidden');
}

function ensurePrimaryKeyControl(field, isAutoId) {
    const wantsSelect = !isAutoId && field.type === 'select';
    const isSelect = recordIdInput.tagName === 'SELECT';
    if (wantsSelect !== isSelect) {
        const replacement = document.createElement(wantsSelect ? 'select' : 'input');
        replacement.id = 'recordId';
        replacement.className = wantsSelect ? 'form-select' : 'form-control';
        replacement.required = !isAutoId;
        if (wantsSelect) {
            replacement.innerHTML = '<option value="">Seleccione una opcion</option>';
        }
        const currentControl = recordIdInput.parentElement?.classList.contains('searchable-select')
            ? recordIdInput.parentElement
            : recordIdInput;
        currentControl.replaceWith(replacement);
        recordIdInput = replacement;
    }

    if (!wantsSelect) {
        recordIdInput.type = isAutoId ? 'hidden' : (field.type || 'text');
        recordIdInput.step = field.type === 'number' ? 'any' : '';
    }
}

function getOptionLabel(option, field) {
    const directLabel = option[field.optionLabel];
    if (directLabel !== undefined && directLabel !== null && String(directLabel).trim() !== '') {
        return directLabel;
    }

    const labelParts = (field.optionLabelParts || [])
        .map((column) => option[column])
        .filter((part) => part !== undefined && part !== null && String(part).trim() !== '');
    if (labelParts.length) {
        return labelParts.join(' ');
    }

    const fullName = [option.NOMBRE, option.APELLIDO_PATERNO, option.APELLIDO_MATERNO]
        .filter((part) => part !== undefined && part !== null && String(part).trim() !== '')
        .join(' ');
    if (fullName) {
        return option.CEDULA ? `${fullName} - Cedula: ${option.CEDULA}` : fullName;
    }

    return option[field.optionValue] ?? '';
}

function isComplexTable(table) {
    return table.hasAddress || table.pkFields.length > 1 || table.fields.length > 2;
}

function isWideField(field) {
    return ['observaciones', 'descripcion', 'otras_senias'].includes(field.key);
}

function isFullRowField(field) {
    return isWideField(field) || field.type === 'checkboxes';
}

function renderAddressFields() {
    return `
        <div class="address-section-title">Direccion</div>
        <div class="field-control">
            <label for="field_id_provincia">Provincia</label>
            <select id="field_id_provincia" class="form-select dynamic-field uniform-input" data-field-key="id_provincia" required>
                <option value="">Seleccione una provincia</option>
            </select>
        </div>
        <div class="field-control">
            <label for="field_id_canton">Canton</label>
            <select id="field_id_canton" class="form-select dynamic-field uniform-input" data-field-key="id_canton" required disabled>
                <option value="">Seleccione un canton</option>
            </select>
        </div>
        <div class="field-control">
            <label for="field_id_distrito">Distrito</label>
            <select id="field_id_distrito" class="form-select dynamic-field uniform-input" data-field-key="id_distrito" required disabled>
                <option value="">Seleccione un distrito</option>
            </select>
        </div>
        <div class="field-control wide-field">
            <label for="field_otras_senias">Otras senias</label>
            <textarea id="field_otras_senias" class="form-control dynamic-field uniform-input" data-field-key="otras_senias" rows="3" required></textarea>
        </div>
    `;
}

async function loadLocationOptions() {
    const keys = ['provincias', 'cantones', 'distritos'];
    try {
        await Promise.all(keys.map(async (key) => {
            if (!locationCache.has(key)) {
                const data = await requestJson(tableUrl(key));
                locationCache.set(key, data.records || []);
            }
        }));
        await loadApiProvinces();
        bindLocationDependencies();
    } catch (error) {
        showMessage(messageBox, error.message, true);
    }
}

async function loadApiProvinces() {
    const provinceInput = getDynamicInput('id_provincia');
    if (!provinceInput) return;
    const provinces = await fetchLocationApi('/provincias.json');
    populateApiLocationSelect(provinceInput, provinces, 'provincias', 'Seleccione una provincia');
    const selected = provinceInput.selectedOptions[0];
    if (selected?.dataset.apiId) await loadApiCantons(selected.dataset.apiId, selected.textContent);
}

function bindLocationDependencies() {
    const provinceInput = getDynamicInput('id_provincia');
    const cantonInput = getDynamicInput('id_canton');
    if (!provinceInput || !cantonInput) return;

    provinceInput.onchange = async () => {
        const selected = provinceInput.selectedOptions[0];
        resetLocationSelect('id_canton', 'Seleccione un canton');
        resetLocationSelect('id_distrito', 'Seleccione un distrito');
        if (selected?.dataset.apiId) await loadApiCantons(selected.dataset.apiId, selected.textContent);
    };
    cantonInput.onchange = async () => {
        const province = provinceInput.selectedOptions[0];
        const canton = cantonInput.selectedOptions[0];
        resetLocationSelect('id_distrito', 'Seleccione un distrito');
        if (province?.dataset.apiId && canton?.dataset.apiId) {
            await loadApiDistricts(province.dataset.apiId, canton.dataset.apiId);
        }
    };
}

async function loadApiCantons(provinceApiId, provinceName) {
    const cantonInput = getDynamicInput('id_canton');
    if (!cantonInput) return;
    const cantons = await fetchLocationApi(`/provincia/${provinceApiId}/cantones.json`);
    populateApiLocationSelect(cantonInput, cantons, 'cantones', 'Seleccione un canton', provinceName);
    cantonInput.disabled = false;
    const selected = cantonInput.selectedOptions[0];
    if (selected?.dataset.apiId) await loadApiDistricts(provinceApiId, selected.dataset.apiId);
}

async function loadApiDistricts(provinceApiId, cantonApiId) {
    const districtInput = getDynamicInput('id_distrito');
    if (!districtInput) return;
    const districts = await fetchLocationApi(`/provincia/${provinceApiId}/canton/${cantonApiId}/distritos.json`);
    populateApiLocationSelect(districtInput, districts, 'distritos', 'Seleccione un distrito');
    districtInput.disabled = false;
}

async function fetchLocationApi(path) {
    const url = `${COSTA_RICA_LOCATIONS_API}${path}`;
    if (!locationApiCache.has(url)) {
        const response = await fetch(url);
        if (!response.ok) throw new Error('No fue posible cargar las ubicaciones de Costa Rica.');
        locationApiCache.set(url, response.json());
    }
    return locationApiCache.get(url);
}

function populateApiLocationSelect(input, apiRecords, cacheKey, placeholder, provinceName = '') {
    const selectedValue = input.dataset.pendingValue || input.value;
    const options = Object.entries(apiRecords).map(([apiId, name]) => {
        const localId = findLocalLocationId(cacheKey, name, provinceName, selectedValue);
        return localId ? { apiId, localId, name } : null;
    }).filter(Boolean);
    input.innerHTML = [`<option value="">${placeholder}</option>`, ...options.map((option) =>
        `<option value="${escapeHtml(option.localId)}" data-api-id="${escapeHtml(option.apiId)}">${escapeHtml(option.name)}</option>`
    )].join('');
    input.value = selectedValue || '';
    delete input.dataset.pendingValue;
}

function findLocalLocationId(cacheKey, apiName, provinceName = '', selectedValue = '') {
    const columns = {
        provincias: ['ID_PROVINCIA', 'NOMBRE_PROVINCIA'],
        cantones: ['ID_CANTON', 'NOMBRE_CANTON'],
        distritos: ['ID_DISTRITO', 'NOMBRE_DISTRITO'],
    };
    const [idColumn, nameColumn] = columns[cacheKey];
    const targetName = cacheKey === 'cantones' && normalizeLocation(apiName) === 'CENTRAL'
        ? provinceName
        : apiName;
    const matches = (locationCache.get(cacheKey) || []).filter((record) =>
        normalizeLocation(record[nameColumn]) === normalizeLocation(targetName)
    );
    const match = matches.find((record) => String(record[idColumn]) === String(selectedValue)) || matches[0];
    return match?.[idColumn] ?? '';
}

function normalizeLocation(value) {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toUpperCase().trim();
}

function resetLocationSelect(key, placeholder) {
    const input = getDynamicInput(key);
    if (!input) return;
    input.innerHTML = `<option value="">${placeholder}</option>`;
    input.value = '';
    input.disabled = true;
}

async function loadRecords(forceRefresh = false) {
    if (!forceRefresh && tableCache.has(currentTable)) {
        records = tableCache.get(currentTable);
        applyRecordFilters();
        showMessage(messageBox, 'Registros cargados desde memoria.');
        return true;
    }

    try {
        const data = await requestJson(tableUrl(currentTable));
        records = data.records || [];
        tableCache.set(currentTable, records);
        applyRecordFilters();
        showMessage(messageBox, data.message || 'Registros cargados correctamente.');
        return true;
    } catch (error) {
        records = [];
        renderRecords();
        if (error.status === 401) showAccess();
        showMessage(messageBox, error.message, true);
        return false;
    }
}

function applyRecordFilters() {
    const table = tables[currentTable];
    if (!table) return;

    const searchTerm = recordSearch.value.trim().toLowerCase();
    filteredRecords = records.filter((record) => {
        const matchesSearch = searchTerm === '' || Object.values(record)
            .filter((value) => value !== null && value !== undefined)
            .some((value) => String(value).toLowerCase().includes(searchTerm));

        return matchesSearch;
    });

    updateDashboard();
    renderRecords();
}

function clearRecordFilters() {
    recordSearch.value = '';
    applyRecordFilters();
}

function updateDashboard() {
    const table = tables[currentTable];
    const hasStatusColumn = table?.statusColumn ?? table?.hasEstado;
    const activeCount = hasStatusColumn
        ? records.filter((record) => String(record.ID_ESTADO ?? '') === '1').length
        : records.length;

    totalRecords.textContent = String(records.length);
    activeRecordsLabel.textContent = hasStatusColumn ? 'Activos' : 'Registros';
    activeRecords.textContent = String(activeCount);
    visibleRecords.textContent = String(filteredRecords.length);
}

function normalizeRecords(items) {
    const table = tables[currentTable];
    return items.map((record) => ({
        ...record,
        ID: record.ID ?? record[findKey(record, 'ID_')],
        NOMBRE: record.NOMBRE ?? record[findNameKey(record)] ?? '',
        ID_ESTADO: record.ID_ESTADO ?? (table.hasEstado ? null : 1),
        NOMBRE_ESTADO: record.NOMBRE_ESTADO ?? '',
    }));
}

function findKey(record, prefix) {
    return Object.keys(record).find((key) => key.startsWith(prefix));
}

function findNameKey(record) {
    return Object.keys(record).find((key) => key.includes('NOMBRE') || key === 'COLOR' || key.includes('TIPO'));
}

function renderRecords() {
    const table = tables[currentTable];
    const columns = getTableColumns(table, false, false);
    const hasEstado = table.statusColumn ?? table.hasEstado;
    const canManage = table.readOnly !== true;
    const canDeactivate = canManage && currentTable !== 'estados';
    recordsHead.innerHTML = `
        <tr>
            ${columns.map((field) => `<th>${escapeHtml(field.label)}</th>`).join('')}
            ${hasEstado ? '<th>Estado</th>' : ''}
            ${canManage ? '<th>Acciones</th>' : ''}
        </tr>
    `;
    recordsTable.innerHTML = '';

    if (filteredRecords.length === 0) {
        recordsTable.innerHTML = `<tr><td colspan="${columns.length + (hasEstado ? 1 : 0) + (canManage ? 1 : 0)}">No hay registros para mostrar.</td></tr>`;
    }

    filteredRecords.forEach((record) => {
        const row = document.createElement('tr');
        const recordIndex = records.indexOf(record);
        const estadoNombre = record.NOMBRE_ESTADO || 'Sin estado';
        const isInactive = String(record.ID_ESTADO) === '2' || String(estadoNombre).toLowerCase().includes('inactivo');

        row.innerHTML = `
            ${columns.map((field) => `<td>${escapeHtml(formatRecordValue(getRecordValue(record, field, true), field))}</td>`).join('')}
            ${hasEstado ? `<td><span class="badge-status ${isInactive ? 'inactive' : ''}">${escapeHtml(estadoNombre)}</span></td>` : ''}
            ${canManage ? '<td><div class="row-actions"><button class="small-btn" data-action="edit" data-index="' + recordIndex + '">Editar</button>' + (canDeactivate ? '<button class="small-btn" data-action="delete" data-index="' + recordIndex + '">Desactivar</button>' : '') + '</div></td>' : ''}
        `;
        recordsTable.appendChild(row);
    });
}

function getTableColumns(table, includeStatus = false, includeAutoId = true) {
    const pkFields = table.autoId && !includeAutoId ? table.pkFields.slice(1) : table.pkFields;
    const displayFields = table.displayFields || [];
    const columns = [...pkFields, ...displayFields.filter((field) => field.tableBefore), ...table.fields, ...displayFields.filter((field) => !field.tableBefore)]
        .filter((field) => field.table !== false);
    return includeStatus && table.hasEstado ? [...columns, { key: 'id_estado', column: 'ID_ESTADO', label: 'Estado', type: 'number' }] : columns;
}

function getRecordValue(record, field, display = false) {
    if (!field) return '';
    const sourceColumn = display && field.displayColumn ? field.displayColumn : (field.sourceColumn || field.column);
    return record[sourceColumn] ?? record[field.column] ?? record[field.key] ?? '';
}

function formatRecordValue(value, field) {
    if (!value || field.type !== 'date') return value ?? '';
    return String(value).slice(0, 10);
}

async function saveTable(event) {
    event.preventDefault();
    const payload = readTableForm();
    const isUpdate = modeInput.value === 'edit';
    const method = isUpdate ? 'PUT' : 'POST';

    try {
        const data = await requestJson(tableUrl(currentTable), {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        clearForm();
        tableCache.delete(currentTable);
        tableCache.delete(`options:${currentTable}`);
        activateRecordTab('records', false);
        const recordsLoaded = await loadRecords(true);
        if (recordsLoaded) {
            showMessage(messageBox, data.message || (isUpdate ? 'El registro se actualizó correctamente.' : 'El registro se agregó correctamente.'));
        }
    } catch (error) {
        showMessage(messageBox, error.message, true);
    }
}

function handleTableClick(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const record = records[Number(button.dataset.index)];
    if (!record) return;
    if (button.dataset.action === 'edit') fillForm(record);
    if (button.dataset.action === 'delete') {
        fillForm(record);
        deleteRecord();
    }
}

function fillForm(record) {
    const table = tables[currentTable];
    selectedRecord = record;
    modeInput.value = 'edit';
    formTitle.textContent = 'Modificar registro';
    resetSelectSearch(recordIdInput);
    recordIdInput.value = formatRecordValue(getRecordValue(record, table.pkFields[0]), table.pkFields[0]);
    ensureSelectSearch(recordIdInput);
    recordIdInput.readOnly = true;

    setDynamicInputValues(table.pkFields.slice(1), record, true);

    if (table.fields[0]) {
        resetSelectSearch(recordNameInput);
        recordNameInput.value = formatRecordValue(getRecordValue(record, table.fields[0]), table.fields[0]);
        ensureSelectSearch(recordNameInput);
    }

    setDynamicInputValues(table.fields.slice(1), record, false);
    if (table.hasAddress) {
        setAddressInputValues(record, false);
    }
    setEditOnlyFields(true);
    setCreateOnlyFields(true);
    recordStatusSelect.value = String(record.ID_ESTADO || getDefaultStateId());
    updateStatusFieldVisibility();
    deleteBtn.disabled = false;
    activateRecordTab('form', true);
}

function clearForm() {
    const table = tables[currentTable];
    selectedRecord = null;
    modeInput.value = 'create';
    formTitle.textContent = 'Nuevo registro';
    recordIdInput.value = '';
    recordIdInput.readOnly = false;
    resetSelectSearch(recordIdInput);
    recordNameInput.value = '';
    resetSelectSearch(recordNameInput);
    [...extraKeyFields.querySelectorAll('.dynamic-field'), ...extraDataFields.querySelectorAll('.dynamic-field')].forEach((input) => {
        if (input.dataset.checkboxes === 'true') {
            input.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.checked = false;
                checkbox.disabled = false;
            });
            return;
        }
        input.value = '';
        input.readOnly = false;
        input.disabled = false;
        input.parentElement?.querySelector('.select-search')?.removeAttribute('disabled');
        resetSelectSearch(input);
    });
    table.pkFields.slice(1).forEach((field) => {
        const input = getDynamicInput(field.key);
        if (input) input.readOnly = false;
    });
    if (table.hasAddress) {
        resetAddressInputs();
    }
    setEditOnlyFields(false);
    setCreateOnlyFields(false);
    recordStatusSelect.value = getDefaultStateId();
    updateStatusFieldVisibility();
    deleteBtn.disabled = true;
}

function updateStatusFieldVisibility() {
    const table = tables[currentTable];
    const isCreating = modeInput.value === 'create';
    recordStatusField.classList.toggle('hidden', !table?.hasEstado || isCreating);
}

async function deleteRecord() {
    const table = tables[currentTable];
    const payload = selectedRecord ? buildPayloadFromRecord(selectedRecord) : readTableForm();
    const message = currentTable === 'estados'
        ? 'Esto cambiara el nombre del estado a Inactivo. Desea continuar?'
        : table.hasEstado
            ? 'Esto cambiara el ID_ESTADO del registro a Inactivo. Desea continuar?'
            : 'Esto ejecutara el procedimiento de eliminacion del registro. Desea continuar?';
    if (!confirm(message)) return;

    try {
        const data = await requestJson(tableUrl(currentTable), {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        showMessage(messageBox, data.message || 'Registro desactivado correctamente.');
        clearForm();
        tableCache.delete(currentTable);
        loadRecords(true);
    } catch (error) {
        showMessage(messageBox, error.message, true);
    }
}

function readTableForm() {
    const table = tables[currentTable];
    const payload = {};
    const firstPk = table.pkFields[0];

    if (!table.autoId || modeInput.value === 'edit') {
        payload[firstPk.key] = readInputValue(recordIdInput, firstPk);
    }

    table.pkFields.slice(1).forEach((field) => {
        const input = getDynamicInput(field.key);
        payload[field.key] = readInputValue(input, field);
    });

    if (table.fields[0]) {
        payload[table.fields[0].key] = readInputValue(recordNameInput, table.fields[0]);
    }

    table.fields.slice(1).forEach((field) => {
        const input = getDynamicInput(field.key);
        payload[field.key] = readInputValue(input, field);
    });

    if (table.hasAddress) {
        payload.direccion = readAddressForm();
        if (modeInput.value === 'edit' && selectedRecord?.ID_DIRECCION_EXACTA) {
            payload.id_direccion_exacta = Number(selectedRecord.ID_DIRECCION_EXACTA);
        }
    }

    if (table.hasEstado) {
        payload.id_estado = Number(recordStatusSelect.value);
    }

    return payload;
}

function buildPayloadFromRecord(record) {
    const table = tables[currentTable];
    const payload = {};

    getTableColumns(table, true).forEach((field) => {
        payload[field.key] = getRecordValue(record, field);
    });

    if (table.hasEstado) {
        payload.id_estado = Number(record.ID_ESTADO || 2);
    }

    return payload;
}

function setDynamicInputValues(fields, record, readOnly) {
    fields.forEach((field) => {
        const input = getDynamicInput(field.key);
        if (!input) return;
        if (field.type === 'checkboxes') {
            const selectedValues = new Set((Array.isArray(getRecordValue(record, field)) ? getRecordValue(record, field) : [])
                .map((value) => String(value)));
            input.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.checked = selectedValues.has(checkbox.value);
                checkbox.disabled = readOnly;
            });
            configureChecklistValidation(input);
            return;
        }
        input.value = formatRecordValue(getRecordValue(record, field), field);
        input.readOnly = readOnly;
        if (input.tagName === 'SELECT') {
            input.disabled = readOnly;
            const search = input.parentElement?.querySelector('.select-search');
            if (search) search.disabled = readOnly;
            ensureSelectSearch(input);
        }
    });
}

function setAddressInputValues(record, readOnly) {
    const values = {
        id_provincia: record.DIRECCION_ID_PROVINCIA,
        id_canton: record.DIRECCION_ID_CANTON,
        id_distrito: record.DIRECCION_ID_DISTRITO,
        otras_senias: record.DIRECCION_OTRAS_SENIAS,
    };

    Object.entries(values).forEach(([key, value]) => {
        const input = getDynamicInput(key);
        if (!input) return;
        input.dataset.pendingValue = value ?? '';
        input.value = value ?? '';
        input.readOnly = readOnly && input.tagName !== 'SELECT';
        input.disabled = false;
    });
    loadLocationOptions();
}

function resetAddressInputs() {
    ['id_provincia', 'id_canton', 'id_distrito', 'otras_senias'].forEach((key) => {
        const input = getDynamicInput(key);
        if (!input) return;
        input.value = '';
        input.readOnly = false;
        input.disabled = false;
        delete input.dataset.pendingValue;
    });
}

function readAddressForm() {
    return {
        id_provincia: readInputValue(getDynamicInput('id_provincia'), { type: 'number' }),
        id_canton: readInputValue(getDynamicInput('id_canton'), { type: 'number' }),
        id_distrito: readInputValue(getDynamicInput('id_distrito'), { type: 'number' }),
        otras_senias: readInputValue(getDynamicInput('otras_senias'), { type: 'text' }),
    };
}

function setEditOnlyFields(isEdit) {
    document.querySelectorAll('.edit-only-field').forEach((fieldBox) => {
        fieldBox.classList.toggle('hidden', !isEdit);
        fieldBox.querySelectorAll('input, textarea, select').forEach((input) => {
            input.required = isEdit;
            input.disabled = !isEdit;
        });
    });
}

function showFormValidationError(event) {
    const input = event.target;
    if (!(input instanceof HTMLInputElement || input instanceof HTMLSelectElement || input instanceof HTMLTextAreaElement)) return;

    const checklist = input.closest('[data-checkboxes="true"]');
    const labelId = checklist?.getAttribute('aria-labelledby');
    const label = labelId
        ? document.getElementById(labelId)
        : (input.id ? document.querySelector(`label[for="${input.id}"]`) : null);
    const fieldName = label?.textContent?.trim() || input.getAttribute('placeholder') || 'este campo';
    const detail = input.validationMessage || 'Complete este campo para continuar.';

    showMessage(messageBox, `Revise ${fieldName}: ${detail}`, true);
}

function keepDigitsOnly(event) {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.dataset.digitsOnly !== 'true') return;

    const maxLength = Number(input.maxLength) || 9;
    input.value = input.value.replace(/\D/g, '').slice(0, maxLength);
}

function setCreateOnlyFields(isEdit) {
    document.querySelectorAll('.create-only-field').forEach((fieldBox) => {
        fieldBox.classList.toggle('hidden', isEdit);
        fieldBox.querySelectorAll('input, textarea, select').forEach((input) => {
            if (input.type === 'checkbox') {
                input.required = false;
                input.disabled = isEdit;
                return;
            }
            input.required = !isEdit;
            input.disabled = isEdit;
        });
        fieldBox.querySelectorAll('[data-checkboxes="true"]').forEach((checklist) => configureChecklistValidation(checklist));
    });
}

function configureChecklistValidation(checklist) {
    const checkboxes = [...checklist.querySelectorAll('input[type="checkbox"]')];
    if (!checkboxes.length) return;

    const updateValidity = () => {
        const isRequired = !checkboxes.every((checkbox) => checkbox.disabled);
        const hasSelection = checkboxes.some((checkbox) => checkbox.checked);
        checkboxes[0].setCustomValidity(isRequired && !hasSelection ? 'Seleccione al menos una posicion.' : '');
    };

    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateValidity));
    updateValidity();
}

function getDynamicInput(key) {
    return document.querySelector(`[data-field-key="${key}"]`);
}

function readInputValue(input, field) {
    if (!input) return null;
    if (field.type === 'checkboxes') {
        return [...input.querySelectorAll('input:checked')].map((checkbox) => Number(checkbox.value));
    }
    if (input.multiple) {
        return [...input.selectedOptions]
            .map((option) => option.value)
            .filter(Boolean)
            .map((value) => field.type === 'multiselect' ? Number(value) : value);
    }
    const value = input.value.trim();
    if (field.type === 'number') return value === '' ? null : Number(value);
    return value;
}

async function requestJson(url, options = {}) {
    let response;
    try {
        response = await fetch(url, options);
    } catch {
        throw new Error('No fue posible conectar con el servidor. Verifique su conexión e intente nuevamente.');
    }

    let data;
    try {
        data = await response.json();
    } catch {
        throw new Error('El servidor no pudo procesar la solicitud. Intente nuevamente o contacte al administrador.');
    }
    if (!response.ok) {
        const error = new Error(data.error || 'No fue posible completar la solicitud.');
        error.status = response.status;
        throw error;
    }
    return data;
}

function showMessage(box, message, isError = false) {
    box.textContent = message;
    box.classList.remove('hidden', 'error');
    if (isError) box.classList.add('error');
}

function clearMessage(box) {
    box.textContent = '';
    box.classList.add('hidden');
    box.classList.remove('error');
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString('es-CR', { style: 'currency', currency: 'CRC' });
}

function escapeHtml(value) {
    return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}

init();
