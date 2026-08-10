// Comparador: limita la selección a tres jugadores antes de solicitar la comparación al backend.
const CONSULTATION_URL = '../../public/routes/consultas.php';
const selectedPlayers = new Set();

document.addEventListener('DOMContentLoaded', initComparator);

async function initComparator() {
    document.getElementById('compareSearch').addEventListener('submit', (event) => {
        event.preventDefault();
        loadCandidates();
    });
    document.getElementById('compareButton').addEventListener('click', compareSelected);
    document.getElementById('compareCandidates').addEventListener('change', togglePlayer);

    try {
        await loadCatalogs();
    } catch (error) {
        showMessage(error.message);
    }
}

async function loadCatalogs() {
    const data = await requestJson(`${CONSULTATION_URL}?action=catalogos`);
    document.getElementById('compareCategory').insertAdjacentHTML(
        'beforeend',
        (data.categories || []).map((category) => `<option value="${escapeHtml(category.ID_CATEGORIA)}">${escapeHtml(category.NOMBRE_CATEGORIA)}</option>`).join('')
    );
    document.getElementById('comparePosition').insertAdjacentHTML(
        'beforeend',
        (data.positions || []).map((position) => `<option value="${escapeHtml(position.ID_POSICION)}">${escapeHtml(position.NOMBRE_POSICION)}</option>`).join('')
    );
}

async function loadCandidates() {
    // La categoría reduce candidatos y mejora la legibilidad de la selección.
    clearMessage();
    const params = new URLSearchParams({ action: 'players' });
    new FormData(document.getElementById('compareSearch')).forEach((value, key) => {
        if (String(value).trim() !== '') params.set(key, String(value).trim());
    });
    const data = await requestJson(`${CONSULTATION_URL}?${params}`);
    renderCandidates(data.records || []);
}

function renderCandidates(records) {
    const container = document.getElementById('compareCandidates');
    container.innerHTML = records.length ? records.map((record) => {
        const name = fullName(record);
        const id = String(record.ID_JUGADOR);
        return `<label class="compare-candidate">
            <input type="checkbox" data-player-id="${escapeHtml(id)}" ${selectedPlayers.has(id) ? 'checked' : ''}>
            <span><strong>${escapeHtml(name)}</strong><small>${escapeHtml(record.NOMBRE_CATEGORIA || 'Sin categoria')} | ${escapeHtml(record.POSICIONES || 'Sin posicion')}</small></span>
        </label>`;
    }).join('') : '<div class="empty-result player-empty">No se encontraron jugadores.</div>';
    updateSelectionState();
}

function togglePlayer(event) {
    // Set evita duplicados y conserva únicamente los ids necesarios para la consulta.
    const checkbox = event.target;
    if (!checkbox.matches('[data-player-id]')) return;
    const id = checkbox.dataset.playerId;
    if (checkbox.checked && selectedPlayers.size >= 3) {
        checkbox.checked = false;
        showMessage('Puedes seleccionar como maximo tres jugadores.');
        return;
    }
    checkbox.checked ? selectedPlayers.add(id) : selectedPlayers.delete(id);
    updateSelectionState();
}

async function compareSelected() {
    // La comparación reutiliza estadísticas agregadas en Oracle para mantener una única fuente de verdad.
    if (selectedPlayers.size < 2) return;
    clearMessage();
    const params = new URLSearchParams({ action: 'compare', ids: [...selectedPlayers].join(',') });
    const data = await requestJson(`${CONSULTATION_URL}?${params}`);
    const result = document.getElementById('comparisonResult');
    document.getElementById('comparisonTable').innerHTML = (data.records || []).map((record) => `
        <tr><td>${escapeHtml(fullName(record))}</td><td>${escapeHtml(record.PARTIDOS_JUGADOS)}</td><td>${escapeHtml(record.GOLES)}</td><td>${escapeHtml(record.ASISTENCIAS)}</td><td>${escapeHtml(record.PROMEDIO_PASES_EXITOSOS)}</td><td>${escapeHtml(record.PROMEDIO_ENTRADAS_EXITOSAS)}</td><td>${escapeHtml(record.PROMEDIO_MINUTOS)}</td></tr>
    `).join('') || '<tr><td colspan="7" class="empty-result">No hay datos comparables.</td></tr>';
    result.classList.remove('hidden');
}

function updateSelectionState() {
    document.getElementById('selectedCount').textContent = `${selectedPlayers.size} de 3 seleccionados`;
    document.getElementById('compareButton').disabled = selectedPlayers.size < 2;
}

function fullName(record) {
    return `${record.NOMBRE || ''} ${record.APELLIDO_PATERNO || ''} ${record.APELLIDO_MATERNO || ''}`.trim();
}

function showMessage(message) {
    const element = document.getElementById('compareMessage');
    element.textContent = message;
    element.classList.remove('hidden');
}

function clearMessage() {
    const element = document.getElementById('compareMessage');
    element.textContent = '';
    element.classList.add('hidden');
}

async function requestJson(url) {
    const response = await fetch(url);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.');
    return data;
}

function escapeHtml(value) {
    return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
}
