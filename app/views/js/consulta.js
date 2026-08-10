// Vista principal de consulta deportiva: el frontend consume un único endpoint
// y el servidor decide qué función del paquete Oracle ejecutar mediante `action`.
const CONSULTATION_URL = '../../public/routes/consultas.php';

const tabs = document.querySelectorAll('[data-consultation-tab]');
const views = document.querySelectorAll('.consultation-view');
const matchFilters = document.getElementById('matchFilters');
const playerFilters = document.getElementById('playerFilters');
const matchesTable = document.getElementById('matchesTable');
const playersGrid = document.getElementById('playersGrid');
const matchMessage = document.getElementById('matchMessage');
const playerMessage = document.getElementById('playerMessage');
let matchesLoaded = false;
let playerSearchTimer = null;
let matchSearchTimer = null;

document.addEventListener('DOMContentLoaded', initConsultation);

async function initConsultation() {
    // Se intercepta submit para evitar recargar la página; los filtros se aplican por AJAX.
    bindTabs();
    matchFilters.addEventListener('submit', (event) => {
        event.preventDefault();
        loadMatches();
    });
    matchFilters.querySelectorAll('input, select').forEach((control) => {
        control.addEventListener(control.tagName === 'SELECT' ? 'change' : 'input', scheduleMatchSearch);
    });
    playerFilters.addEventListener('submit', (event) => {
        event.preventDefault();
        loadPlayers();
    });
    document.getElementById('clearMatchFilters').addEventListener('click', () => {
        window.clearTimeout(matchSearchTimer);
        matchFilters.reset();
        loadMatches();
    });
    document.getElementById('clearPlayerFilters').addEventListener('click', clearPlayerSearch);
    document.getElementById('playerCategory').addEventListener('change', loadPlayers);
    document.getElementById('playerPosition').addEventListener('change', loadPlayers);
    document.getElementById('playerName').addEventListener('input', scheduleTextSearch);
    document.getElementById('playerCedula').addEventListener('input', scheduleTextSearch);

    try {
        await loadCatalogs();
        if (window.location.hash === '#matchesView') await loadMatches();
    } catch (error) {
        showMessage(playerMessage, error.message);
        showMessage(matchMessage, error.message);
    }
}

function bindTabs() {
    const requestedView = window.location.hash.slice(1);
    if (requestedView && [...views].some((view) => view.id === requestedView)) {
        tabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.consultationTab === requestedView));
        views.forEach((view) => view.classList.toggle('active', view.id === requestedView));
    }

    tabs.forEach((tab) => tab.addEventListener('click', async () => {
        tabs.forEach((item) => item.classList.toggle('active', item === tab));
        views.forEach((view) => view.classList.toggle('active', view.id === tab.dataset.consultationTab));

        if (tab.dataset.consultationTab === 'matchesView' && !matchesLoaded) {
            try {
                await loadMatches();
            } catch (error) {
                showMessage(matchMessage, error.message);
            }
        }
    }));
}

async function loadCatalogs() {
    // Catálogos reutilizables para selectores; se cargan una vez al iniciar la pantalla.
    const data = await requestJson(`${CONSULTATION_URL}?action=catalogos`);
    const seasons = data.seasons || [];
    const categories = data.categories || [];
    const positions = data.positions || [];
    const seasonOptions = seasons.map((season) => `<option value="${escapeHtml(season.ID_TEMPORADA)}">${escapeHtml(`${season.TIPO_TEMPORADA} ${season.ANIO}`)}</option>`).join('');
    document.getElementById('matchSeason').insertAdjacentHTML('beforeend', seasonOptions);
    document.getElementById('playerCategory').insertAdjacentHTML('beforeend', categories.map((category) => `<option value="${escapeHtml(category.ID_CATEGORIA)}">${escapeHtml(category.NOMBRE_CATEGORIA)}</option>`).join(''));
    document.getElementById('playerPosition').insertAdjacentHTML('beforeend', positions.map((position) => `<option value="${escapeHtml(position.ID_POSICION)}">${escapeHtml(position.NOMBRE_POSICION)}</option>`).join(''));
}

async function loadMatches() {
    clearMessage(matchMessage);
    const params = formParams(matchFilters, 'matches');
    const data = await requestJson(`${CONSULTATION_URL}?${params}`);
    matchesLoaded = true;
    renderMatches(data.records || []);
}

function scheduleMatchSearch() {
    // Debounce: espera 350 ms para no solicitar datos en cada pulsación del usuario.
    window.clearTimeout(matchSearchTimer);
    matchSearchTimer = window.setTimeout(() => loadMatches(), 350);
}

async function loadPlayers() {
    // No se consulta sin filtros para evitar mostrar toda la población de jugadores por defecto.
    clearMessage(playerMessage);
    const hasFilters = [...new FormData(playerFilters).values()].some((value) => String(value).trim() !== '');
    if (!hasFilters) {
        renderPlayerIdle();
        return;
    }

    const params = formParams(playerFilters, 'players');
    const data = await requestJson(`${CONSULTATION_URL}?${params}`);
    renderPlayers(data.records || []);
}

function scheduleTextSearch(event) {
    const value = String(event.target.value || '').trim();
    if (value !== '' && value.length < 3) return;

    window.clearTimeout(playerSearchTimer);
    playerSearchTimer = window.setTimeout(() => loadPlayers(), 350);
}

function clearPlayerSearch() {
    window.clearTimeout(playerSearchTimer);
    playerFilters.reset();
    clearMessage(playerMessage);
    renderPlayerIdle();
}

function formParams(form, action) {
    // Solo se envían campos con valor; así los parámetros nulos funcionan como filtros opcionales.
    const params = new URLSearchParams({ action });
    new FormData(form).forEach((value, key) => {
        if (String(value).trim() !== '') params.set(key, String(value).trim());
    });
    return params;
}

function renderMatches(records) {
    // Las tarjetas resumen se calculan sobre los mismos resultados filtrados que se muestran en la tabla.
    const wins = records.filter((record) => Number(record.GOLES_FAVOR) > Number(record.GOLES_CONTRA)).length;
    const losses = records.filter((record) => Number(record.GOLES_FAVOR) < Number(record.GOLES_CONTRA)).length;
    document.getElementById('matchTotal').textContent = String(records.length);
    document.getElementById('matchWins').textContent = String(wins);
    document.getElementById('matchLosses').textContent = String(losses);
    document.getElementById('matchDraws').textContent = String(records.length - wins - losses);
    document.getElementById('matchResultCount').textContent = `${records.length} registros`;
    matchesTable.innerHTML = records.length ? records.map((record) => `
        <tr>
            <td>${escapeHtml(formatDate(record.FECHA))}</td>
            <td>${escapeHtml(record.RIVAL || '')}</td>
            <td>${escapeHtml(record.RESULTADO || '')}</td>
            <td>${escapeHtml(`${record.GOLES_FAVOR ?? 0} - ${record.GOLES_CONTRA ?? 0}`)}</td>
            <td>${escapeHtml(`${record.TIPO_TEMPORADA || ''} ${record.TEMPORADA_ANIO || ''}`)}</td>
            <td>${escapeHtml(record.TOTAL_ASISTENCIA ?? '0')}</td>
            <td><a class="table-link" href="partido.html?id=${encodeURIComponent(record.ID_PARTIDO)}">Ver detalle</a></td>
        </tr>
    `).join('') : emptyRow(7, 'No se encontraron partidos.');
}

function renderPlayers(records) {
    // Se escapa todo dato recibido antes de insertarlo en HTML para prevenir XSS.
    document.getElementById('playerResultCount').textContent = `${records.length} registros`;
    playersGrid.innerHTML = records.length ? records.map((record) => {
        const fullName = `${record.NOMBRE || ''} ${record.APELLIDO_PATERNO || ''} ${record.APELLIDO_MATERNO || ''}`.trim();
        const relatedLinks = [
            Number(record.PARTIDOS_JUGADOS) > 0 ? `<a href="jugador-partidos.html?id=${encodeURIComponent(record.ID_JUGADOR)}">${escapeHtml(record.PARTIDOS_JUGADOS)} partidos</a>` : '',
            Number(record.LESIONES) > 0 ? `<a href="jugador-lesiones.html?id=${encodeURIComponent(record.ID_JUGADOR)}">${escapeHtml(record.LESIONES)} lesiones</a>` : '',
        ].filter(Boolean).join(' <span aria-hidden="true">|</span> ');

        return `
            <article class="player-card">
                <div class="player-card-topline">
                    <span class="player-category">${escapeHtml(record.NOMBRE_CATEGORIA || 'Sin categoria')}</span>
                    <span class="player-dorsal">${escapeHtml(record.DORSAL ?? '-')}</span>
                </div>
                <h4>${escapeHtml(fullName)}</h4>
                <dl class="player-card-data">
                    <div><dt>Cedula</dt><dd>${escapeHtml(record.CEDULA || 'No disponible')}</dd></div>
                    <div><dt>Posicion</dt><dd>${escapeHtml(record.POSICIONES || 'Sin posicion')}</dd></div>
                </dl>
                ${relatedLinks ? `<div class="player-card-links">${relatedLinks}</div>` : ''}
                <a class="btn btn-success player-card-action" href="jugador.html?id=${encodeURIComponent(record.ID_JUGADOR)}">Vista completa</a>
            </article>
        `;
    }).join('') : '<div class="empty-result player-empty">No se encontraron jugadores con esos filtros.</div>';
}

function renderPlayerIdle() {
    document.getElementById('playerResultCount').textContent = '0 registros';
    playersGrid.innerHTML = '<div class="empty-result player-empty">Configura un filtro o presiona buscar para consultar jugadores.</div>';
}

function formatDate(value) {
    return value ? String(value).slice(0, 10) : '';
}

function emptyRow(columns, message) {
    return `<tr><td colspan="${columns}" class="empty-result">${escapeHtml(message)}</td></tr>`;
}

function showMessage(element, message) {
    element.textContent = message;
    element.classList.remove('hidden');
}

function clearMessage(element) {
    element.textContent = '';
    element.classList.add('hidden');
}

async function requestJson(url) {
    // Centraliza la comunicación y convierte respuestas de error del backend en errores visibles.
    const response = await fetch(url);
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.');
    return data;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
