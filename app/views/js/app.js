const AUTH_URL = '../../public/routes/auth.php';
const MODULE_URL = '../../public/routes/admin.php';

let tables = {};



let currentTable = 'estados';
let records = [];
let filteredRecords = [];
let selectedRecord = null;
const tableCache = new Map();
const stateCache = new Map();
const locationCache = new Map();
const moduleGroups = window.APP_MODULES || {};

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
const recordIdInput = document.getElementById('recordId');
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

function init() {
    bindTabs();
    loadStates();
    loginForm.addEventListener('submit', login);
    logoutBtn.addEventListener('click', logout);
    tableForm.addEventListener('submit', saveTable);
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

    tableTitle.textContent = table.title;
    recordsPanel.setAttribute('aria-label', `Registros de ${table.title}`);
    contentGrid.classList.toggle('expanded-form', isExpanded);
    tableForm.classList.toggle('expanded', isExpanded);
    recordIdField.classList.toggle('hidden', table.autoId);
    recordIdInput.required = !table.autoId;
    recordIdLabel.textContent = firstPk.label;
    recordIdInput.type = table.autoId ? 'hidden' : firstPk.type;
    recordIdInput.step = firstPk.type === 'number' ? 'any' : '';
    recordIdInput.placeholder = firstPk.label;
    renderDynamicInputs(extraKeyFields, table.pkFields.slice(1));

    if (firstField) {
        ensurePrimaryFieldControl(firstField);
        recordNameField.classList.remove('hidden');
        recordNameInput.required = true;
        recordNameLabel.textContent = firstField.label;
        recordNameInput.type = firstField.type;
        recordNameInput.step = firstField.type === 'number' ? 'any' : '';
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
    recordStatusField.classList.toggle('hidden', !table.hasEstado);
    deleteBtn.classList.toggle('hidden', currentTable === 'estados');
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
    recordNameInput.replaceWith(replacement);
    recordNameInput = replacement;
}

function renderDynamicInputs(container, fields) {
    container.innerHTML = fields.map((field) => `
        <div class="field-control ${isWideField(field) ? 'wide-field' : ''} ${field.editOnly ? 'edit-only-field hidden' : ''}">
            <label for="field_${field.key}">${escapeHtml(field.label)}</label>
            ${renderFieldControl(field)}
        </div>
    `).join('');
}

function renderFieldControl(field) {
    if (field.type === 'select') {
        return `
            <select id="field_${field.key}" class="form-select dynamic-field uniform-input" data-field-key="${escapeHtml(field.key)}" required>
                <option value="">Seleccione una opcion</option>
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
    const required = field.editOnly ? '' : ' required';
    return `
        <input
            type="${escapeHtml(field.type)}"
            id="field_${field.key}"
            class="form-control dynamic-field uniform-input"
            data-field-key="${escapeHtml(field.key)}"
            placeholder="${escapeHtml(field.label)}"
            ${step}
            ${required}>
    `;
}

async function loadDynamicSelectOptions(table) {
    const selectFields = [...(table.pkFields || []), ...(table.fields || [])]
        .filter((field) => field.type === 'select' && field.optionsTable);

    await Promise.all(selectFields.map(async (field) => {
        const input = getDynamicInput(field.key);
        if (!input) return;

        try {
            if (!tableCache.has(`options:${field.optionsTable}`)) {
                const data = await requestJson(tableUrl(field.optionsTable));
                tableCache.set(`options:${field.optionsTable}`, data.records || []);
            }
            const options = tableCache.get(`options:${field.optionsTable}`) || [];
            const selectedValue = input.value;
            input.innerHTML = ['<option value="">Seleccione una opcion</option>', ...options.map((option) =>
                `<option value="${escapeHtml(option[field.optionValue])}">${escapeHtml(option[field.optionLabel])}</option>`
            )].join('');
            input.value = selectedValue;
        } catch (error) {
            showMessage(messageBox, error.message, true);
        }
    }));
}

function isComplexTable(table) {
    return table.hasAddress || table.pkFields.length > 1 || table.fields.length > 2;
}

function isWideField(field) {
    return ['observaciones', 'descripcion', 'otras_senias'].includes(field.key);
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
            <select id="field_id_canton" class="form-select dynamic-field uniform-input" data-field-key="id_canton" required>
                <option value="">Seleccione un canton</option>
            </select>
        </div>
        <div class="field-control">
            <label for="field_id_distrito">Distrito</label>
            <select id="field_id_distrito" class="form-select dynamic-field uniform-input" data-field-key="id_distrito" required>
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
        populateLocationSelects();
    } catch (error) {
        showMessage(messageBox, error.message, true);
    }
}

function populateLocationSelects() {
    const locationFields = [
        ['id_provincia', 'ID_PROVINCIA', 'NOMBRE_PROVINCIA', 'Seleccione una provincia'],
        ['id_canton', 'ID_CANTON', 'NOMBRE_CANTON', 'Seleccione un canton'],
        ['id_distrito', 'ID_DISTRITO', 'NOMBRE_DISTRITO', 'Seleccione un distrito'],
    ];

    locationFields.forEach(([key, idColumn, nameColumn, placeholder]) => {
        const input = getDynamicInput(key);
        const records = locationCache.get(key === 'id_provincia' ? 'provincias' : key === 'id_canton' ? 'cantones' : 'distritos') || [];
        if (!input) return;

        const selectedValue = input.dataset.pendingValue || input.value;
        if (!records.length) {
            input.dataset.pendingValue = selectedValue;
            return;
        }

        input.innerHTML = [`<option value="">${placeholder}</option>`, ...records
            .sort((a, b) => String(a[nameColumn] || '').localeCompare(String(b[nameColumn] || '')))
            .map((record) => `<option value="${escapeHtml(record[idColumn])}">${escapeHtml(record[nameColumn] || record[idColumn])}</option>`)]
            .join('');
        input.value = selectedValue || '';
        delete input.dataset.pendingValue;
    });
}

async function loadRecords(forceRefresh = false) {
    if (!forceRefresh && tableCache.has(currentTable)) {
        records = tableCache.get(currentTable);
        applyRecordFilters();
        showMessage(messageBox, 'Registros cargados desde memoria.');
        return;
    }

    try {
        const data = await requestJson(tableUrl(currentTable));
        records = data.records || [];
        tableCache.set(currentTable, records);
        applyRecordFilters();
        showMessage(messageBox, data.message || 'Registros cargados correctamente.');
    } catch (error) {
        records = [];
        renderRecords();
        if (error.status === 401) showAccess();
        showMessage(messageBox, error.message, true);
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
    const canDeactivate = currentTable !== 'estados';
    recordsHead.innerHTML = `
        <tr>
            ${columns.map((field) => `<th>${escapeHtml(field.label)}</th>`).join('')}
            ${hasEstado ? '<th>Estado</th>' : ''}
            <th>Acciones</th>
        </tr>
    `;
    recordsTable.innerHTML = '';

    if (filteredRecords.length === 0) {
        recordsTable.innerHTML = `<tr><td colspan="${columns.length + (hasEstado ? 2 : 1)}">No hay registros para mostrar.</td></tr>`;
    }

    filteredRecords.forEach((record) => {
        const row = document.createElement('tr');
        const recordIndex = records.indexOf(record);
        const estadoNombre = record.NOMBRE_ESTADO || 'Sin estado';
        const isInactive = String(record.ID_ESTADO) === '2' || String(estadoNombre).toLowerCase().includes('inactivo');

        row.innerHTML = `
            ${columns.map((field) => `<td>${escapeHtml(formatRecordValue(getRecordValue(record, field, true), field))}</td>`).join('')}
            ${hasEstado ? `<td><span class="badge-status ${isInactive ? 'inactive' : ''}">${escapeHtml(estadoNombre)}</span></td>` : ''}
            <td><div class="row-actions"><button class="small-btn" data-action="edit" data-index="${recordIndex}">Editar</button>${canDeactivate ? `<button class="small-btn" data-action="delete" data-index="${recordIndex}">Desactivar</button>` : ''}</div></td>
        `;
        recordsTable.appendChild(row);
    });
}

function getTableColumns(table, includeStatus = false, includeAutoId = true) {
    const pkFields = table.autoId && !includeAutoId ? table.pkFields.slice(1) : table.pkFields;
    const columns = [...pkFields, ...table.fields, ...(table.displayFields || [])]
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
    const method = modeInput.value === 'edit' ? 'PUT' : 'POST';

    try {
        const data = await requestJson(tableUrl(currentTable), {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        showMessage(messageBox, data.message || 'Guardado correctamente.');
        clearForm();
        tableCache.delete(currentTable);
        tableCache.delete(`options:${currentTable}`);
        activateRecordTab('records', false);
        loadRecords(true);
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
    recordIdInput.value = formatRecordValue(getRecordValue(record, table.pkFields[0]), table.pkFields[0]);
    recordIdInput.readOnly = true;

    setDynamicInputValues(table.pkFields.slice(1), record, true);

    if (table.fields[0]) {
        recordNameInput.value = formatRecordValue(getRecordValue(record, table.fields[0]), table.fields[0]);
    }

    setDynamicInputValues(table.fields.slice(1), record, false);
    if (table.hasAddress) {
        setAddressInputValues(record, false);
    }
    setEditOnlyFields(true);
    recordStatusSelect.value = String(record.ID_ESTADO || getDefaultStateId());
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
    recordNameInput.value = '';
    [...extraKeyFields.querySelectorAll('.dynamic-field'), ...extraDataFields.querySelectorAll('.dynamic-field')].forEach((input) => {
        input.value = '';
        input.readOnly = false;
    });
    table.pkFields.slice(1).forEach((field) => {
        const input = getDynamicInput(field.key);
        if (input) input.readOnly = false;
    });
    if (table.hasAddress) {
        resetAddressInputs();
    }
    setEditOnlyFields(false);
    recordStatusSelect.value = getDefaultStateId();
    deleteBtn.disabled = true;
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
        input.value = formatRecordValue(getRecordValue(record, field), field);
        input.readOnly = readOnly;
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
    populateLocationSelects();
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

function getDynamicInput(key) {
    return document.querySelector(`[data-field-key="${key}"]`);
}

function readInputValue(input, field) {
    if (!input) return null;
    const value = input.value.trim();
    if (field.type === 'number') return value === '' ? null : Number(value);
    return value;
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, options);
    const data = await response.json();
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

function formatMoney(value) {
    return Number(value || 0).toLocaleString('es-CR', { style: 'currency', currency: 'CRC' });
}

function escapeHtml(value) {
    return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}

init();
