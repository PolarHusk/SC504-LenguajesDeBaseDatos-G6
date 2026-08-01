const CONSULTATION_URL = '../../public/routes/consultas.php';

document.addEventListener('DOMContentLoaded', initAvailability);

async function initAvailability() {
    document.getElementById('availabilityFilters').addEventListener('submit', (event) => {
        event.preventDefault();
        loadAvailability();
    });
    document.getElementById('availabilityCategory').addEventListener('change', loadAvailability);

    try {
        await loadCatalogs();
        await loadAvailability();
    } catch (error) {
        showMessage(error.message);
    }
}

async function loadCatalogs() {
    const data = await requestJson(`${CONSULTATION_URL}?action=catalogos`);
    document.getElementById('availabilityCategory').insertAdjacentHTML(
        'beforeend',
        (data.categories || []).map((category) => `<option value="${escapeHtml(category.ID_CATEGORIA)}">${escapeHtml(category.NOMBRE_CATEGORIA)}</option>`).join('')
    );
}

async function loadAvailability() {
    clearMessage();
    const params = new URLSearchParams({ action: 'availability' });
    new FormData(document.getElementById('availabilityFilters')).forEach((value, key) => {
        if (String(value).trim() !== '') params.set(key, String(value).trim());
    });
    const data = await requestJson(`${CONSULTATION_URL}?${params}`);
    renderAvailability(data.records || []);
}

function renderAvailability(records) {
    const available = records.filter((record) => record.ESTADO === 'DISPONIBLE').length;
    const injured = records.filter((record) => record.ESTADO === 'LESIONADO').length;
    const suspended = records.filter((record) => record.ESTADO === 'SUSPENDIDO').length;
    document.getElementById('availableCount').textContent = String(available);
    document.getElementById('injuredCount').textContent = String(injured);
    document.getElementById('suspendedCount').textContent = String(suspended);
    document.getElementById('availabilityCount').textContent = `${records.length} registros`;
    document.getElementById('availabilityTable').innerHTML = records.length ? records.map((record) => `
        <tr><td><a class="table-link" href="jugador.html?id=${encodeURIComponent(record.ID_JUGADOR)}">${escapeHtml(record.NOMBRE_COMPLETO)}</a></td><td>${escapeHtml(record.NOMBRE_CATEGORIA)}</td><td><span class="status-pill status-${String(record.ESTADO || '').toLowerCase()}">${escapeHtml(record.ESTADO)}</span></td><td>${Number(record.LESIONES) > 0 ? `<a class="table-link" href="jugador-lesiones.html?id=${encodeURIComponent(record.ID_JUGADOR)}">${escapeHtml(record.LESIONES)}</a>` : '0'}</td><td>${escapeHtml(record.TARJETAS_AMARILLAS)}</td><td>${escapeHtml(record.TARJETAS_ROJAS)}</td><td><a class="table-link" href="jugador.html?id=${encodeURIComponent(record.ID_JUGADOR)}">Perfil</a></td></tr>
    `).join('') : '<tr><td colspan="7" class="empty-result">No hay jugadores para estos filtros.</td></tr>';
}

function showMessage(message) { const element = document.getElementById('availabilityMessage'); element.textContent = message; element.classList.remove('hidden'); }
function clearMessage() { const element = document.getElementById('availabilityMessage'); element.textContent = ''; element.classList.add('hidden'); }
async function requestJson(url) { const response = await fetch(url); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.'); return data; }
function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }
