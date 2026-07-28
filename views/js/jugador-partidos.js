const CONSULTATION_URL = '../routes/consultas.php';

document.addEventListener('DOMContentLoaded', loadPlayerMatches);

async function loadPlayerMatches() {
    try {
        const id = new URLSearchParams(window.location.search).get('id');
        if (!id) throw new Error('No se indico un jugador valido.');
        const data = await requestJson(`${CONSULTATION_URL}?action=player_matches&id=${encodeURIComponent(id)}`);
        const records = data.records || [];
        document.getElementById('playerMatchesTitle').textContent = records[0]?.NOMBRE_COMPLETO || 'Partidos del jugador';
        document.getElementById('playerMatchesSubtitle').textContent = 'Partidos en los que existe una participacion registrada.';
        document.getElementById('playerMatchesCount').textContent = `${records.length} registros`;
        document.getElementById('playerMatchesTable').innerHTML = records.length ? records.map((record) => `<tr><td>${escapeHtml(formatDate(record.FECHA))}</td><td>${escapeHtml(record.RIVAL)}</td><td>${escapeHtml(record.RESULTADO)}</td><td>${escapeHtml(`${record.GOLES_FAVOR ?? 0} - ${record.GOLES_CONTRA ?? 0}`)}</td><td>${escapeHtml(record.MINUTOS_JUGADOS ?? 0)}</td><td>${escapeHtml(record.GOLES ?? 0)}</td><td>${escapeHtml(record.ASISTENCIAS ?? 0)}</td><td><a class="table-link" href="partido.html?id=${encodeURIComponent(record.ID_PARTIDO)}">Ver partido</a></td></tr>`).join('') : '<tr><td colspan="8" class="empty-result">No se encontraron partidos.</td></tr>';
        document.getElementById('playerMatchesContent').classList.remove('hidden');
    } catch (error) { showMessage(error.message); }
}

function formatDate(value) { return value ? String(value).slice(0, 10) : ''; }
function showMessage(message) { const element = document.getElementById('playerMatchesMessage'); element.textContent = message; element.classList.remove('hidden'); }
async function requestJson(url) { const response = await fetch(url); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.'); return data; }
function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }
