const CONSULTATION_URL = '../../public/routes/consultas.php';

document.addEventListener('DOMContentLoaded', loadMatch);

async function loadMatch() {
    try {
        const id = new URLSearchParams(window.location.search).get('id');
        if (!id) throw new Error('No se indico un partido valido.');
        const data = await requestJson(`${CONSULTATION_URL}?action=match&id=${encodeURIComponent(id)}`);
        renderMatch(data.match || {}, data.players || []);
    } catch (error) { showMessage(error.message); }
}

function renderMatch(match, players) {
    document.getElementById('matchHero').innerHTML = `<span class="eyebrow">${escapeHtml(match.NOMBRE_CATEGORIA || 'Partido')}</span><h2>Academia Leiva <span aria-hidden="true">vs.</span> ${escapeHtml(match.RIVAL || 'Rival')}</h2><div class="match-score">${escapeHtml(match.GOLES_FAVOR ?? 0)} <span>-</span> ${escapeHtml(match.GOLES_CONTRA ?? 0)}</div><p class="match-meta">${escapeHtml(formatDate(match.FECHA))} | ${escapeHtml(`${match.TIPO_TEMPORADA || ''} ${match.TEMPORADA_ANIO || ''}`)} | ${escapeHtml(match.RESULTADO || '')}</p>`;
    const stats = [['Tiros', match.TIROS], ['Tiros a porteria', match.TIROS_PORTERIA], ['Pases exitosos', match.PASES_EXITOSOS], ['Pases fallidos', match.PASES_FALLIDOS], ['Posesion', match.POSESION_BALON], ['Asistencias', match.TOTAL_ASISTENCIA]];
    document.getElementById('teamStatsGrid').innerHTML = stats.map(([label, value]) => `<article class="analysis-stat"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value ?? 0)}</strong></article>`).join('');
    document.getElementById('matchPlayersTable').innerHTML = players.length ? players.map((player) => `<tr><td>${escapeHtml(player.NOMBRE_COMPLETO)}</td><td>${escapeHtml(player.POSICIONES || 'Sin posicion')}</td><td>${escapeHtml(player.MINUTOS_JUGADOS ?? 0)}</td><td>${escapeHtml(player.GOLES ?? 0)}</td><td>${escapeHtml(player.ASISTENCIAS ?? 0)}</td><td>${escapeHtml(player.PASES_EXITOSOS ?? 0)}</td><td>${escapeHtml(player.ENTRADAS_EXITOSAS ?? 0)}</td><td><a class="table-link" href="jugador-partido.html?player_id=${encodeURIComponent(player.ID_JUGADOR)}&match_id=${encodeURIComponent(player.ID_PARTIDO)}">Ver detalle</a></td></tr>`).join('') : '<tr><td colspan="8" class="empty-result">No hay estadisticas individuales para este partido.</td></tr>';
    document.getElementById('matchDetailContent').classList.remove('hidden');
}

function formatDate(value) { return value ? String(value).slice(0, 10) : 'Fecha no disponible'; }
function showMessage(message) { const element = document.getElementById('matchDetailMessage'); element.textContent = message; element.classList.remove('hidden'); }
async function requestJson(url) { const response = await fetch(url); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.'); return data; }
function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }
