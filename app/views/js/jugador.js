// Perfil individual: el id se obtiene de la URL para que cada tarjeta enlace a su jugador.
const CONSULTATION_URL = '../../public/routes/consultas.php';

document.addEventListener('DOMContentLoaded', loadPlayerProfile);

async function loadPlayerProfile() {
    // Validación temprana: sin id no se hace una consulta inválida al backend.
    const playerId = new URLSearchParams(window.location.search).get('id');
    const message = document.getElementById('profileMessage');

    if (!playerId) {
        showMessage(message, 'No se indico el jugador que deseas consultar.');
        return;
    }

    try {
        const response = await fetch(`${CONSULTATION_URL}?action=player&id=${encodeURIComponent(playerId)}`);
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'No fue posible cargar el perfil.');
        renderProfile(data.record);
    } catch (error) {
        showMessage(message, error.message);
    }
}

function renderProfile(record) {
    // El perfil separa datos personales de métricas; las métricas se reciben ya agregadas por Oracle.
    const fullName = `${record.NOMBRE || ''} ${record.APELLIDO_PATERNO || ''} ${record.APELLIDO_MATERNO || ''}`.trim();
    document.title = `Academia Leiva | ${fullName}`;
    document.getElementById('profileName').textContent = fullName;
    document.getElementById('personalData').innerHTML = [
        ['Cedula', record.CEDULA],
        ['Categoria', record.NOMBRE_CATEGORIA],
        ['Posicion', record.POSICIONES],
        ['Dorsal', record.DORSAL],
        ['Fecha de nacimiento', formatDate(record.FECHA_NACIMIENTO)],
    ].map(([label, value]) => `<div><dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value || 'No disponible')}</dd></div>`).join('');

    const statistics = [
        ['Goles totales', record.GOLES],
        ['Asistencias totales', record.ASISTENCIAS],
        ['Partidos jugados', record.PARTIDOS_JUGADOS, `jugador-partidos.html?id=${encodeURIComponent(record.ID_JUGADOR)}`],
        ['Partidos ganados', record.PARTIDOS_GANADOS],
        ['Partidos perdidos', record.PARTIDOS_PERDIDOS],
        ['Pases totales', record.PASES_TOTALES],
        ['Pases exitosos totales', record.PASES_EXITOSOS_TOTALES],
        ['Pases exitosos promedio', record.PROMEDIO_PASES_EXITOSOS],
        ['Regates totales', record.REGATES_TOTALES],
        ['Regates exitosos promedio', record.PROMEDIO_REGATES_EXITOSOS],
        ['Tiros totales', record.TIROS_TOTALES],
        ['Tiros exitosos promedio', record.PROMEDIO_TIROS_EXITOSOS],
        ['Entradas totales', record.ENTRADAS_TOTALES],
        ['Entradas exitosas promedio', record.PROMEDIO_ENTRADAS_EXITOSAS],
        ['Minutos totales', record.MINUTOS_TOTALES],
        ['Minutos jugados promedio', record.PROMEDIO_MINUTOS],
        ['Tarjetas totales', record.TARJETAS],
        ['Tarjetas amarillas', record.TARJETAS_AMARILLAS],
        ['Tarjetas rojas', record.TARJETAS_ROJAS],
        ['Lesiones', record.LESIONES, `jugador-lesiones.html?id=${encodeURIComponent(record.ID_JUGADOR)}`],
    ];
    document.getElementById('statisticsGrid').innerHTML = statistics.map(([label, value, link]) => `
        <article class="profile-stat-card">
            <span>${escapeHtml(label)}</span>
            <strong>${escapeHtml(formatNumber(value))}</strong>
            ${link && Number(value) > 0 ? `<a class="stat-card-link" href="${link}">Ver detalle</a>` : ''}
        </article>
    `).join('');
    document.getElementById('profileContent').classList.remove('hidden');
}

function formatDate(value) {
    return value ? String(value).slice(0, 10) : '';
}

function formatNumber(value) {
    if (value === null || value === undefined || value === '') return '0';
    return String(value);
}

function showMessage(element, message) {
    element.textContent = message;
    element.classList.remove('hidden');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
