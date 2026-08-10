// Historial clínico del jugador: muestra lesiones, médico y fecha estimada de recuperación.
const CONSULTATION_URL = '../../public/routes/consultas.php';

document.addEventListener('DOMContentLoaded', loadInjuries);

async function loadInjuries() {
    // El id del jugador mantiene la navegación contextual desde la ficha deportiva.
    try {
        const id = new URLSearchParams(window.location.search).get('id');
        if (!id) throw new Error('No se indico un jugador valido.');
        const data = await requestJson(`${CONSULTATION_URL}?action=player_injuries&id=${encodeURIComponent(id)}`);
        const records = data.records || [];
        document.getElementById('injuryTitle').textContent = records[0]?.NOMBRE_COMPLETO || 'Historial de lesiones';
        document.getElementById('injurySubtitle').textContent = 'Lesiones registradas y estado de recuperacion.';
        document.getElementById('injuryCount').textContent = `${records.length} registros`;
        document.getElementById('injuryTable').innerHTML = records.length ? records.map((record) => `<tr><td>${escapeHtml(formatDate(record.FECHA))}</td><td>${escapeHtml(record.DESCRIPCION || 'Sin descripcion')}</td><td>${escapeHtml(record.OBSERVACIONES || 'Sin observaciones')}</td><td>${escapeHtml(formatDate(record.FECHA_RECUPERACION) || 'Pendiente')}</td><td><span class="status-pill ${record.ESTADO === 'ACTIVA' ? 'status-lesionado' : 'status-disponible'}">${escapeHtml(record.ESTADO)}</span></td></tr>`).join('') : '<tr><td colspan="5" class="empty-result">No se encontraron lesiones.</td></tr>';
        document.getElementById('injuryContent').classList.remove('hidden');
    } catch (error) { showMessage(error.message); }
}

function formatDate(value) { return value ? String(value).slice(0, 10) : ''; }
function showMessage(message) { const element = document.getElementById('injuryMessage'); element.textContent = message; element.classList.remove('hidden'); }
async function requestJson(url) { const response = await fetch(url); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.'); return data; }
function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }
