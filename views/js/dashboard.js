const CONSULTATION_URL = '../routes/consultas.php';

document.addEventListener('DOMContentLoaded', initDashboard);

async function initDashboard() {
    document.getElementById('dashboardFilters').addEventListener('submit', (event) => {
        event.preventDefault();
        loadDashboard();
    });

    try {
        await loadCatalogs();
        await loadDashboard();
    } catch (error) {
        showMessage(error.message);
    }
}

async function loadCatalogs() {
    const data = await requestJson(`${CONSULTATION_URL}?action=catalogos`);
    const seasons = data.seasons || [];
    const categories = data.categories || [];
    document.getElementById('dashboardSeason').insertAdjacentHTML(
        'beforeend',
        seasons.map((season) => `<option value="${escapeHtml(season.ID_TEMPORADA)}">${escapeHtml(`${season.TIPO_TEMPORADA} ${season.ANIO}`)}</option>`).join('')
    );
    document.getElementById('dashboardCategory').insertAdjacentHTML(
        'beforeend',
        categories.map((category) => `<option value="${escapeHtml(category.ID_CATEGORIA)}">${escapeHtml(category.NOMBRE_CATEGORIA)}</option>`).join('')
    );
}

async function loadDashboard() {
    clearMessage();
    const params = new URLSearchParams({ action: 'dashboard' });
    new FormData(document.getElementById('dashboardFilters')).forEach((value, key) => {
        if (String(value).trim() !== '') params.set(key, String(value).trim());
    });
    const data = await requestJson(`${CONSULTATION_URL}?${params}`);
    const record = data.record || {};
    setText('dashboardPlayers', record.TOTAL_JUGADORES);
    setText('dashboardMatches', record.TOTAL_PARTIDOS);
    setText('dashboardWins', record.PARTIDOS_GANADOS);
    setText('dashboardGoals', record.GOLES_FAVOR);
    setText('dashboardAssists', record.TOTAL_ASISTENCIAS);
    setText('dashboardInjuries', record.TOTAL_LESIONES);
    setText('dashboardTopScorer', record.TOP_GOLEADOR_NOMBRE || 'Sin datos');
    setText('dashboardTopScorerValue', `${record.TOP_GOLEADOR_GOLES ?? 0} goles`);
    setText('dashboardTopAssister', record.TOP_ASISTENTE_NOMBRE || 'Sin datos');
    setText('dashboardTopAssisterValue', `${record.TOP_ASISTENTE_ASISTENCIAS ?? 0} asistencias`);
    setText('dashboardAvailable', record.TOTAL_DISPONIBLES);
}

function setText(id, value) {
    document.getElementById(id).textContent = String(value ?? 0);
}

function showMessage(message) {
    const element = document.getElementById('dashboardMessage');
    element.textContent = message;
    element.classList.remove('hidden');
}

function clearMessage() {
    const element = document.getElementById('dashboardMessage');
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
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
