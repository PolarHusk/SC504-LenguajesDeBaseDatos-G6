const CONSULTATION_URL = '../routes/consultas.php';

document.addEventListener('DOMContentLoaded', loadPlayerMatch);

async function loadPlayerMatch() {
    try {
        const params = new URLSearchParams(window.location.search);
        const playerId = params.get('player_id');
        const matchId = params.get('match_id');
        if (!playerId || !matchId) throw new Error('No se indico un jugador y partido validos.');
        const data = await requestJson(`${CONSULTATION_URL}?action=player_match&player_id=${encodeURIComponent(playerId)}&match_id=${encodeURIComponent(matchId)}`);
        renderPlayerMatch(data.record || {});
    } catch (error) { showMessage(error.message); }
}

function renderPlayerMatch(record) {
    document.getElementById('playerMatchHero').innerHTML = `<span class="eyebrow">${escapeHtml(record.NOMBRE_CATEGORIA || 'Estadistica individual')}</span><h2>${escapeHtml(record.NOMBRE_COMPLETO || 'Jugador')} | ${escapeHtml(record.RIVAL || 'Partido')}</h2><p class="match-meta">${escapeHtml(formatDate(record.FECHA))} | Resultado: ${escapeHtml(record.RESULTADO || '')}</p>`;
    const stats = [['Minutos jugados', record.MINUTOS_JUGADOS], ['Goles', record.GOLES], ['Asistencias', record.ASISTENCIAS], ['Pases', record.PASES], ['Pases exitosos', record.PASES_EXITOSOS], ['Regates', record.REGATES], ['Regates exitosos', record.REGATES_EXITOSOS], ['Tiros', record.TIROS], ['Tiros exitosos', record.TIROS_EXITOSOS], ['Entradas', record.ENTRADAS], ['Entradas exitosas', record.ENTRADAS_EXITOSAS]];
    document.getElementById('playerMatchStats').innerHTML = stats.map(([label, value]) => `<article class="profile-stat-card"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value ?? 0)}</strong></article>`).join('');
    document.getElementById('playerMatchContent').classList.remove('hidden');
}

function formatDate(value) { return value ? String(value).slice(0, 10) : 'Fecha no disponible'; }
function showMessage(message) { const element = document.getElementById('playerMatchMessage'); element.textContent = message; element.classList.remove('hidden'); }
async function requestJson(url) { const response = await fetch(url); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.'); return data; }
function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }
