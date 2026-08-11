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
        document.getElementById('injuryTitle').textContent = 'Historial de lesiones';
        document.getElementById('injurySubtitle').textContent = 'Lesiones registradas y estado de recuperacion.';
        document.getElementById('injuryCount').textContent = `${records.length} registros`;
        document.getElementById('injuryTable').innerHTML = records.length ? records.map((record) => `<tr><td>${escapeHtml(formatDate(record.FECHA_PARTE_MEDICO))}</td><td>${escapeHtml(record.NOMBRE_TIPO_LESION || 'Sin tipo')}</td><td>${escapeHtml(record.DESCRIPCION || 'Sin descripcion')}<br><small>${escapeHtml(record.MEDICO || 'Medico no indicado')}</small></td><td>${escapeHtml(formatDate(record.FECHA_RECUPERACION) || 'Pendiente')}</td><td><span class="status-pill status-lesionado">Activa</span></td></tr>`).join('') : '<tr><td colspan="5" class="empty-result">No se encontraron lesiones.</td></tr>';
        document.getElementById('injuryContent').classList.remove('hidden');
    } catch (error) { showMessage(error.message); }
}

function formatDate(value) { return value ? String(value).slice(0, 10) : ''; }
function showMessage(message) { const element = document.getElementById('injuryMessage'); element.textContent = message; element.classList.remove('hidden'); }
async function requestJson(url) { const response = await fetch(url); const data = await response.json(); if (!response.ok) throw new Error(data.error || 'No fue posible completar la consulta.'); return data; }
function escapeHtml(value) { return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;'); }
