/* ═══════════════════════════════════════════════════════════════
   DEYBIS SYSTEM — app.js v2
   SPA controller con diseño renovado
═══════════════════════════════════════════════════════════════ */
'use strict';

const API  = window.DS.base;
const USR  = window.DS.usuario;
const PERM = window.DS.permisos;

/* ── Helpers globales ──────────────────────────────────────── */
const $  = id  => document.getElementById(id);
const $$ = sel => document.querySelectorAll(sel);

function fmtNum(n) {
  return Number(n || 0).toLocaleString('es-PE', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
function fmtDate(s) {
  if (!s) return '—';
  const d = new Date(s);
  return isNaN(d) ? s : d.toLocaleDateString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric' });
}

/* ── Toast ─────────────────────────────────────────────────── */
const TOAST_ICONS = { success:'bi-check-circle-fill', danger:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill' };
function toast(msg, type = 'success', dur = 3500) {
  const stack = $('toastStack');
  const el    = document.createElement('div');
  el.className = `ds-toast ds-toast-${type}`;
  el.innerHTML = `<i class="bi ${TOAST_ICONS[type]||'bi-bell'}"></i>
    <span class="ds-toast-msg">${msg}</span>
    <button class="ds-toast-close"><i class="bi bi-x"></i></button>`;
  stack.appendChild(el);
  const close = () => {
    el.classList.add('out');
    setTimeout(() => el.remove(), 220);
  };
  el.querySelector('.ds-toast-close').addEventListener('click', close);
  setTimeout(close, dur);
}

/* ── API fetch wrapper ─────────────────────────────────────── */
async function api(method, path, body = null) {
  const opts = { method, headers: { 'Content-Type': 'application/json' } };
  let url = API + path;
  if (method === 'GET' && body) {
    url += '?' + new URLSearchParams(Object.fromEntries(
      Object.entries(body).filter(([,v]) => v !== null && v !== undefined)
    ));
  } else if (body) {
    opts.body = JSON.stringify(body);
  }
  try {
    const res = await fetch(url, opts);
    if (res.status === 401) { window.location.href = API + '/'; return null; }
    return await res.json();
  } catch {
    toast('Error de conexión.', 'danger');
    return null;
  }
}

/* ── Cliente activo ────────────────────────────────────────── */
function clienteActual() {
  const s = $('selectCliente');
  return s ? s.value : (USR.cliente_codigo || '');
}

/* ── Skeletons ─────────────────────────────────────────────── */
function skelRows(n = 4) {
  return Array(n).fill(`<div class="skel skel-row"></div>`).join('');
}

/* ── Stock badge/bar ───────────────────────────────────────── */
function stockBar(actual, min) {
  if (actual <= 0) {
    return `<div class="stock-bar-wrap">
      <div class="stock-bar-track"><div class="stock-bar-fill empty" style="width:100%"></div></div>
      <span class="stock-val" style="color:var(--danger)">${fmtNum(actual)}</span>
      <span class="badge badge-empty">Sin stock</span>
    </div>`;
  }
  if (min > 0 && actual <= min) {
    const pct = Math.min(100, Math.round((actual / min) * 100));
    return `<div class="stock-bar-wrap">
      <div class="stock-bar-track"><div class="stock-bar-fill low" style="width:${pct}%"></div></div>
      <span class="stock-val" style="color:var(--warning)">${fmtNum(actual)}</span>
      <span class="badge badge-low">Stock bajo</span>
    </div>`;
  }
  const pct = min > 0 ? Math.min(100, Math.round((actual / (min * 2)) * 100)) : 75;
  return `<div class="stock-bar-wrap">
    <div class="stock-bar-track"><div class="stock-bar-fill ok" style="width:${pct}%"></div></div>
    <span class="stock-val" style="color:var(--success)">${fmtNum(actual)}</span>
    <span class="badge badge-ok">Normal</span>
  </div>`;
}

/* ── Sparkline SVG inline ──────────────────────────────────── */
function sparkline(data, color = '#2563EB') {
  if (!data || data.length < 2) return '';
  const W = 80, H = 24;
  const max = Math.max(...data, 1);
  const pts = data.map((v, i) => {
    const x = Math.round((i / (data.length - 1)) * W);
    const y = Math.round(H - (v / max) * H);
    return `${x},${y}`;
  }).join(' ');
  return `<svg width="${W}" height="${H}" viewBox="0 0 ${W} ${H}" fill="none" xmlns="http://www.w3.org/2000/svg" class="stat-sparkline">
    <polyline points="${pts}" stroke="${color}" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>
  </svg>`;
}

/* ── Page header helper ────────────────────────────────────── */
function setPageHeader(title, sub = '', actionsHTML = '') {
  const ph = $('pageHeader');
  ph.style.display = 'flex';
  $('pageTitle').textContent   = title;
  $('pageSub').textContent     = sub;
  $('pageActions').innerHTML   = actionsHTML;
  $('topbar-breadcrumb').textContent = title;
}

/* ══════════════════════════════════════════════════════════════
   ROUTER
══════════════════════════════════════════════════════════════ */
const tabs = {};
let activeTab = null;

function registerTab(slug, fn) { tabs[slug] = fn; }

function showTab(slug) {
  if (!PERM[slug]) return;
  activeTab = slug;

  $$('.sb-link').forEach(a => a.classList.toggle('active', a.dataset.tab === slug));

  const area = $('tabContent');
  area.innerHTML = `<div class="ds-card" style="padding:32px">${skelRows(5)}</div>`;
  $('pageHeader').style.display = 'none';
  $('pageActions').innerHTML = '';

  if (tabs[slug]) tabs[slug](area);
}

/* ── Sidebar nav ───────────────────────────────────────────── */
$$('.sb-link').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    showTab(a.dataset.tab);
    // cerrar en mobile
    if (window.innerWidth <= 900) {
      $('sidebar').classList.remove('mobile-open');
      $('sidebarOverlay').classList.remove('visible');
    }
  });
});

/* ── Sidebar collapse (desktop) ────────────────────────────── */
$('btnCollapseNav').addEventListener('click', () => {
  const sb = $('sidebar');
  const mc = $('mainContent');
  sb.classList.toggle('collapsed');
  mc.classList.toggle('collapsed');
});

/* ── Mobile menu ───────────────────────────────────────────── */
$('btnMobileMenu').addEventListener('click', () => {
  $('sidebar').classList.toggle('mobile-open');
  $('sidebarOverlay').classList.toggle('visible');
});
$('sidebarOverlay').addEventListener('click', () => {
  $('sidebar').classList.remove('mobile-open');
  $('sidebarOverlay').classList.remove('visible');
});

/* ── Theme toggle ──────────────────────────────────────────── */
const savedTheme = localStorage.getItem('ds-theme') || 'dark';
document.documentElement.setAttribute('data-theme', savedTheme);
$('btnTheme').addEventListener('click', () => {
  const cur  = document.documentElement.getAttribute('data-theme');
  const next = cur === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('ds-theme', next);
  $('btnTheme').innerHTML = next === 'dark'
    ? '<i class="bi bi-moon-stars"></i>'
    : '<i class="bi bi-sun"></i>';
});
$('btnTheme').innerHTML = savedTheme === 'dark'
  ? '<i class="bi bi-moon-stars"></i>'
  : '<i class="bi bi-sun"></i>';

/* ── Logout ────────────────────────────────────────────────── */
$('btnLogout').addEventListener('click', async () => {
  await api('POST', '/api/logout');
  window.location.href = API + '/';
});

/* ── Selector de cliente ───────────────────────────────────── */
const selCli = $('selectCliente');
if (selCli) {
  api('GET', '/api/clientes', { activos: '1' }).then(r => {
    if (!r?.ok) return;
    r.data.forEach(c => {
      const o = document.createElement('option');
      o.value = c.codigo; o.textContent = c.nombre;
      selCli.appendChild(o);
    });
  });
  selCli.addEventListener('change', () => { if (activeTab) showTab(activeTab); });
}

/* ── Modal helper ──────────────────────────────────────────── */
function openModal(title, bodyHTML, footerHTML = '') {
  $('dsModalTitle').innerHTML  = title;
  $('dsModalBody').innerHTML   = bodyHTML;
  $('dsModalFooter').innerHTML = footerHTML;
  $('dsModal').style.display   = 'flex';
}
function closeModal() { $('dsModal').style.display = 'none'; }
$('dsModalClose').addEventListener('click', closeModal);
$('dsModal').addEventListener('click', e => { if (e.target === $('dsModal')) closeModal(); });

/* ══════════════════════════════════════════════════════════════
   DASHBOARD
══════════════════════════════════════════════════════════════ */
registerTab('dashboard', async area => {
  setPageHeader('Dashboard', 'Resumen general del inventario');

  area.innerHTML = `
    <div class="stat-grid" id="statGrid">${Array(6).fill(`
      <div class="ds-card" style="padding:16px 18px">
        <div class="skel skel-h2" style="width:40%;margin-bottom:12px"></div>
        <div class="skel skel-h1" style="width:70%"></div>
      </div>`).join('')}
    </div>
    <div class="ds-grid-2" style="gap:14px">
      <div class="ds-card ds-card-flush">
        <div style="padding:14px 18px 10px" class="ds-card-title"><i class="bi bi-exclamation-triangle"></i>Alertas de Stock</div>
        <div id="alertasBody" style="padding:0 0 4px">${skelRows(3)}</div>
      </div>
      <div class="ds-card">
        <div class="ds-card-title"><i class="bi bi-activity"></i>Resumen rápido</div>
        <div id="resumenBody">${skelRows(3)}</div>
      </div>
    </div>`;

  const [res, alertas] = await Promise.all([
    api('GET', '/api/dashboard/resumen', { cliente: clienteActual() }),
    api('GET', '/api/dashboard/alertas',  { cliente: clienteActual() }),
  ]);

  if (res?.ok) {
    const d = res.data;
    const cards = [
      { color:'blue',   icon:'bi-boxes',           val: d.totalProductos,         lbl:'Total Productos',      trend: null },
      { color:'green',  icon:'bi-check-circle',     val: d.totalProductos - d.sinStock - d.stockBajo, lbl:'Stock Normal', trend: 'up' },
      { color:'yellow', icon:'bi-exclamation',      val: d.stockBajo,             lbl:'Stock Bajo',           trend: d.stockBajo > 0 ? 'down' : 'flat' },
      { color:'red',    icon:'bi-x-circle',         val: d.sinStock,              lbl:'Sin Stock',            trend: d.sinStock > 0 ? 'down' : 'flat' },
      { color:'cyan',   icon:'bi-arrow-left-right', val: d.totalMovimientos,      lbl:'Movimientos Totales',  trend: null },
      { color:'violet', icon:'bi-clock-history',    val: d.movimientosUltimoMes,  lbl:'Último Mes',           trend: d.movimientosUltimoMes > 0 ? 'up' : 'flat' },
    ];
    const sparkData = {
      blue  : [4,7,5,8,6,9,d.totalProductos],
      green : [8,6,7,9,8,10, d.totalProductos - d.sinStock - d.stockBajo],
      yellow: [1,3,2,4,2,3,d.stockBajo],
      red   : [2,1,3,1,2,1,d.sinStock],
      cyan  : [5,8,6,9,7,10,d.totalMovimientos%10],
      violet: [3,5,4,6,5,7,d.movimientosUltimoMes%10],
    };
    const sparkColors = { blue:'#3B82F6', green:'#10B981', yellow:'#F59E0B', red:'#EF4444', cyan:'#06B6D4', violet:'#7C3AED' };

    $('statGrid').innerHTML = cards.map(c => `
      <div class="stat-card" data-color="${c.color}">
        <div class="stat-card-glow"></div>
        <div class="stat-top">
          <div class="stat-icon"><i class="bi ${c.icon}"></i></div>
          ${c.trend ? `<span class="stat-trend ${c.trend}">${c.trend==='up'?'↑':c.trend==='down'?'↓':'—'}</span>` : ''}
        </div>
        <div class="stat-val">${fmtNum(c.val)}</div>
        <div class="stat-lbl">${c.lbl}</div>
        ${sparkline(sparkData[c.color], sparkColors[c.color])}
      </div>`).join('');

    $('resumenBody').innerHTML = `
      <div style="display:flex;flex-direction:column;gap:10px">
        ${[
          ['Tasa de disponibilidad', d.totalProductos > 0 ? Math.round(((d.totalProductos - d.sinStock) / d.totalProductos) * 100) + '%' : '—', 'var(--success)'],
          ['Productos con alerta',   d.stockBajo + d.sinStock, 'var(--warning)'],
          ['Mov. promedio/mes',      d.totalMovimientos > 0 ? fmtNum(Math.round(d.movimientosUltimoMes)) : '0', 'var(--cyan)'],
        ].map(([lbl, val, col]) => `
          <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-subtle)">
            <span style="font-size:12px;color:var(--text-secondary)">${lbl}</span>
            <span style="font-family:var(--font-mono);font-size:14px;font-weight:600;color:${col}">${val}</span>
          </div>`).join('')}
      </div>`;
  }

  if (alertas?.ok) {
    const rows = alertas.data;
    $('alertasBody').innerHTML = rows.length
      ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
          <table class="ds-table">
            <thead><tr><th>Cliente</th><th>Código</th><th>Producto</th><th>Stock Mín</th><th>Stock Actual</th></tr></thead>
            <tbody>${rows.map(r => `
              <tr>
                <td>${r.cliente_nombre}</td>
                <td><span class="chip">${r.codigo}</span></td>
                <td class="td-primary">${r.nombre}</td>
                <td style="font-family:var(--font-mono)">${fmtNum(r.stock_min)}</td>
                <td>${stockBar(r.stock_actual, r.stock_min)}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`
      : `<div style="padding:24px;text-align:center;color:var(--text-muted)">
          <i class="bi bi-check-circle" style="font-size:28px;color:var(--success);display:block;margin-bottom:8px"></i>
          <span style="font-size:12px">Sin alertas de stock activas</span>
        </div>`;
  }
});

/* ══════════════════════════════════════════════════════════════
   CLIENTES
══════════════════════════════════════════════════════════════ */
registerTab('clientes', async area => {
  setPageHeader('Clientes', 'Gestión de clientes del sistema',
    `<button class="btn btn-primary" id="btnNuevoCliente"><i class="bi bi-plus-lg"></i>Nuevo Cliente</button>`);

  area.innerHTML = `
    <div class="ds-grid-2" style="gap:14px">
      <div id="formNuevoCliente" class="ds-card" style="display:none">
        <div class="ds-card-title"><i class="bi bi-person-plus"></i>Registrar Cliente</div>
        <div class="ds-field">
          <label class="ds-label">Código <span class="req">*</span></label>
          <div class="ds-input-group"><i class="bi bi-hash ds-input-icon"></i>
            <input type="text" id="cliCodigo" class="ds-input" placeholder="CLI001" style="text-transform:uppercase">
          </div>
        </div>
        <div class="ds-field">
          <label class="ds-label">Nombre <span class="req">*</span></label>
          <div class="ds-input-group"><i class="bi bi-building ds-input-icon"></i>
            <input type="text" id="cliNombre" class="ds-input" placeholder="Nombre del cliente">
          </div>
        </div>
        <div class="btn-group">
          <button id="btnRegistrarCliente" class="btn btn-primary"><i class="bi bi-check-lg"></i>Registrar</button>
          <button id="btnCancelarCliente" class="btn btn-secondary"><i class="bi bi-x"></i>Cancelar</button>
        </div>
      </div>
      <div class="ds-card ds-card-flush" style="grid-column: 1 / -1">
        <div style="padding:14px 18px 10px" class="ds-card-title"><i class="bi bi-building"></i>Clientes Registrados</div>
        <div id="tablaClientes" style="padding:0 0 4px">${skelRows()}</div>
      </div>
    </div>`;

  // Botón nuevo
  $('pageActions').querySelector('#btnNuevoCliente')?.addEventListener('click', () => {
    $('formNuevoCliente').style.display = 'block';
    $('formNuevoCliente').scrollIntoView({ behavior:'smooth' });
    $('cliCodigo').focus();
  });

  $('btnCancelarCliente')?.addEventListener('click', () => {
    $('formNuevoCliente').style.display = 'none';
  });

  async function cargarClientes() {
    const r = await api('GET', '/api/clientes');
    if (!r?.ok) return;
    $('tablaClientes').innerHTML = r.data.length
      ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
          <table class="ds-table">
            <thead><tr><th>Código</th><th>Nombre</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>${r.data.map(c => `
              <tr>
                <td><span class="chip">${c.codigo}</span></td>
                <td class="td-primary">${c.nombre}</td>
                <td><span class="badge ${c.estado==='ACTIVO'?'badge-active':'badge-inactive'}">${c.estado}</span></td>
                <td>
                  <button class="btn btn-xs ${c.estado==='ACTIVO'?'btn-danger':'btn-success'} btn-cambio-estado"
                    data-codigo="${c.codigo}" data-estado="${c.estado==='ACTIVO'?'INACTIVO':'ACTIVO'}">
                    <i class="bi ${c.estado==='ACTIVO'?'bi-pause-circle':'bi-play-circle'}"></i>
                    ${c.estado==='ACTIVO'?'Inactivar':'Activar'}
                  </button>
                </td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`
      : `<div style="padding:32px;text-align:center;color:var(--text-muted)">Sin clientes registrados.</div>`;

    $$('.btn-cambio-estado').forEach(btn => {
      btn.addEventListener('click', async () => {
        const r2 = await api('POST', '/api/clientes/estado', { codigo: btn.dataset.codigo, estado: btn.dataset.estado });
        toast(r2?.mensaje || 'Error', r2?.ok ? 'success' : 'danger');
        if (r2?.ok) cargarClientes();
      });
    });
  }

  cargarClientes();

  $('btnRegistrarCliente')?.addEventListener('click', async () => {
    const r = await api('POST', '/api/clientes', {
      codigo: $('cliCodigo').value.trim().toUpperCase(),
      nombre: $('cliNombre').value.trim()
    });
    toast(r?.mensaje || 'Error', r?.ok ? 'success' : 'danger');
    if (r?.ok) {
      $('cliCodigo').value = ''; $('cliNombre').value = '';
      $('formNuevoCliente').style.display = 'none';
      cargarClientes();
      // Refrescar selector de cliente en sidebar
      if (selCli) {
        selCli.innerHTML = '<option value="">Todos los clientes</option>';
        const rc = await api('GET', '/api/clientes', { activos:'1' });
        rc?.data?.forEach(c => { selCli.insertAdjacentHTML('beforeend', `<option value="${c.codigo}">${c.nombre}</option>`); });
      }
    }
  });
});

/* ══════════════════════════════════════════════════════════════
   PRODUCTOS
══════════════════════════════════════════════════════════════ */
registerTab('productos', async area => {
  setPageHeader('Productos', 'Catálogo global y asignación por cliente');

  area.innerHTML = `
    <div class="ds-tabs">
      <button class="ds-tab active" data-ptab="catalogo"><i class="bi bi-grid"></i>Catálogo</button>
      <button class="ds-tab" data-ptab="nuevo"><i class="bi bi-plus-circle"></i>Nuevo Producto</button>
      <button class="ds-tab" data-ptab="habilitar"><i class="bi bi-link-45deg"></i>Asignar a Cliente</button>
    </div>
    <div id="prodPanel"></div>`;

  const panels = { catalogo: renderCatalogo, nuevo: renderNuevo, habilitar: renderHabilitar };

  $$('.ds-tab[data-ptab]').forEach(btn => {
    btn.addEventListener('click', () => {
      $$('.ds-tab[data-ptab]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      panels[btn.dataset.ptab]?.();
    });
  });

  async function renderCatalogo() {
    $('prodPanel').innerHTML = `
      <div class="ds-card ds-card-flush">
        <div style="padding:14px 18px 10px;display:flex;align-items:center;justify-content:space-between">
          <div class="ds-card-title" style="margin:0"><i class="bi bi-box-seam"></i>Catálogo Global</div>
          <div class="ds-input-group" style="max-width:220px">
            <i class="bi bi-search ds-input-icon"></i>
            <input type="text" id="filtroCat" class="ds-input" placeholder="Filtrar...">
          </div>
        </div>
        <div id="tablaCatalogo">${skelRows()}</div>
      </div>`;

    const r = await api('GET', '/api/productos/catalogo');
    let data = r?.data || [];

    function render(d) {
      $('tablaCatalogo').innerHTML = d.length
        ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
            <table class="ds-table">
              <thead><tr><th>Código</th><th>Nombre</th><th>Unidad</th><th>Grupo</th></tr></thead>
              <tbody>${d.map(p => `
                <tr>
                  <td><span class="chip">${p.codigo}</span></td>
                  <td class="td-primary">${p.nombre}</td>
                  <td style="color:var(--text-muted)">${p.unidad}</td>
                  <td><span class="badge badge-info">${p.grupo}</span></td>
                </tr>`).join('')}
              </tbody>
            </table>
          </div>`
        : `<div style="padding:32px;text-align:center;color:var(--text-muted)">Sin productos.</div>`;
    }

    render(data);
    $('filtroCat').addEventListener('input', function () {
      const t = this.value.toLowerCase();
      render(t ? data.filter(p => p.nombre.toLowerCase().includes(t) || p.codigo.toLowerCase().includes(t) || p.grupo.toLowerCase().includes(t)) : data);
    });
  }

  async function renderNuevo() {
    $('prodPanel').innerHTML = `<div class="ds-card" style="max-width:520px">
      <div class="ds-card-title"><i class="bi bi-plus-circle"></i>Registrar Nuevo Producto</div>
      <div id="codigoPreview" class="ds-alert ds-alert-info" style="display:none">
        <i class="bi bi-info-circle"></i>
        Código a asignar: <strong id="codigoPreviewVal" style="font-family:var(--font-mono)"></strong>
      </div>
      <div class="ds-field">
        <label class="ds-label">Grupo <span class="req">*</span></label>
        <select id="pGrupo" class="ds-select"><option value="">— Seleccione grupo —</option></select>
      </div>
      <div class="ds-field">
        <label class="ds-label">Nombre <span class="req">*</span></label>
        <input type="text" id="pNombre" class="ds-input" placeholder="Nombre del producto">
      </div>
      <div class="ds-grid-2" style="gap:12px">
        <div class="ds-field">
          <label class="ds-label">Unidad de Medida</label>
          <select id="pUnidad" class="ds-select"></select>
        </div>
        <div class="ds-field">
          <label class="ds-label">Stock Mínimo</label>
          <input type="number" id="pStockMin" class="ds-input" min="0" value="0">
        </div>
      </div>
      <div class="ds-field">
        <label class="ds-label">Habilitar para clientes (opcional)</label>
        <div id="checkClientes" class="ds-input" style="height:auto;padding:8px;max-height:120px;overflow-y:auto">
          <span style="color:var(--text-muted);font-size:11px">Cargando...</span>
        </div>
      </div>
      <div class="btn-group">
        <button id="btnCrearProd" class="btn btn-primary"><i class="bi bi-check-lg"></i>Registrar Producto</button>
        <button class="btn btn-ghost" onclick="$('pNombre').value='';$('pGrupo').value='';$('pStockMin').value=0;">Limpiar</button>
      </div>
    </div>`;

    const [listas, prefijos, clientes] = await Promise.all([
      api('GET', '/api/productos/listas'),
      api('GET', '/api/productos/prefijos'),
      api('GET', '/api/clientes', { activos:'1' }),
    ]);

    const pfMap = prefijos?.data || {};
    listas?.data?.grupos?.forEach(g => $('pGrupo').insertAdjacentHTML('beforeend', `<option value="${g}">${g}</option>`));
    listas?.data?.unidades?.forEach(u => $('pUnidad').insertAdjacentHTML('beforeend', `<option value="${u}" ${u==='Unidades'?'selected':''}>${u}</option>`));

    $('checkClientes').innerHTML = clientes?.data?.length
      ? clientes.data.map(c => `
        <label style="display:flex;align-items:center;gap:6px;padding:4px 0;font-size:12px;cursor:pointer;color:var(--text-secondary)">
          <input type="checkbox" class="ds-check cli-check" value="${c.codigo}">
          ${c.nombre}
        </label>`).join('')
      : '<span style="color:var(--text-muted);font-size:11px">Sin clientes activos.</span>';

    $('pGrupo').addEventListener('change', function () {
      const pf = pfMap[this.value];
      if (pf) { $('codigoPreview').style.display='flex'; $('codigoPreviewVal').textContent = pf + 'XXXX (autogenerado)'; }
      else     { $('codigoPreview').style.display='none'; }
    });

    $('btnCrearProd').addEventListener('click', async () => {
      if (!$('pGrupo').value || !$('pNombre').value.trim()) {
        toast('Grupo y nombre son obligatorios.', 'warning'); return;
      }
      const clisSelec = [...$$('.cli-check:checked')].map(c => c.value);
      const r = await api('POST', '/api/productos', {
        grupo: $('pGrupo').value, nombre: $('pNombre').value.trim(),
        unidad: $('pUnidad').value, stock_min: parseFloat($('pStockMin').value) || 0,
        clientes: clisSelec,
      });
      toast(r?.mensaje || 'Error', r?.ok ? 'success' : 'danger');
      if (r?.ok) renderCatalogo();
    });
  }

  async function renderHabilitar() {
    const clientes = await api('GET', '/api/clientes', { activos:'1' });
    const optsCli  = (clientes?.data || []).map(c => `<option value="${c.codigo}">${c.nombre}</option>`).join('');

    $('prodPanel').innerHTML = `
      <div class="ds-grid-2" style="gap:14px">
        <div class="ds-card">
          <div class="ds-card-title"><i class="bi bi-link-45deg"></i>Habilitar Producto</div>
          <div class="ds-field">
            <label class="ds-label">Cliente <span class="req">*</span></label>
            <select id="habCliente" class="ds-select"><option value="">— Seleccione —</option>${optsCli}</select>
          </div>
          <div class="ds-field">
            <label class="ds-label">Producto <span class="req">*</span></label>
            <div style="position:relative">
              <input type="text" id="habBusca" class="ds-input" placeholder="Buscar por nombre...">
              <div id="habDrop" class="ds-autocomplete" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:200"></div>
            </div>
            <input type="hidden" id="habCodigo">
          </div>
          <div class="ds-field">
            <label class="ds-label">Stock Mínimo</label>
            <input type="number" id="habMin" class="ds-input" min="0" value="0">
          </div>
          <button id="btnHabilitar" class="btn btn-success w-100"><i class="bi bi-link-45deg"></i>Habilitar</button>
        </div>
        <div class="ds-card">
          <div class="ds-card-title"><i class="bi bi-list-check"></i>Productos del Cliente</div>
          <div class="ds-field">
            <select id="verCliProd" class="ds-select"><option value="">— Ver productos de... —</option>${optsCli}</select>
          </div>
          <div id="tablaHab">${skelRows(3)}</div>
        </div>
      </div>`;

    // autocomplete
    let habTimer;
    $('habBusca').addEventListener('input', function () {
      clearTimeout(habTimer);
      habTimer = setTimeout(async () => {
        const q = this.value.trim();
        if (!q) { $('habDrop').style.display='none'; return; }
        const r = await api('GET', '/api/productos/buscar', { q });
        const items = r?.data || [];
        $('habDrop').innerHTML = items.map(p => `
          <div class="ds-autocomplete-item hab-sug" data-codigo="${p.codigo}" data-nombre="${p.nombre}">
            <span class="item-code">${p.codigo}</span>
            <span>${p.nombre}</span>
          </div>`).join('') || '<div class="ds-autocomplete-item" style="color:var(--text-muted)">Sin resultados</div>';
        $('habDrop').style.display = 'block';
        $$('.hab-sug').forEach(el => {
          el.addEventListener('click', () => {
            $('habBusca').value = el.dataset.nombre;
            $('habCodigo').value = el.dataset.codigo;
            $('habDrop').style.display = 'none';
          });
        });
      }, 260);
    });
    document.addEventListener('click', e => {
      if (!$('habBusca')?.contains(e.target)) $('habDrop').style.display = 'none';
    });

    $('btnHabilitar').addEventListener('click', async () => {
      if (!$('habCliente').value || !$('habCodigo').value) { toast('Seleccione cliente y producto.','warning'); return; }
      const r = await api('POST', '/api/productos/habilitar', {
        cliente: $('habCliente').value, producto: $('habCodigo').value,
        stock_min: parseFloat($('habMin').value) || 0,
      });
      toast(r?.mensaje || 'Error', r?.ok ? 'success' : 'danger');
      if (r?.ok) $('verCliProd').dispatchEvent(new Event('change'));
    });

    $('verCliProd').addEventListener('change', async function () {
      if (!this.value) { $('tablaHab').innerHTML = skelRows(2); return; }
      const r = await api('GET', '/api/inventario', { cliente: this.value });
      const rows = r?.data || [];
      $('tablaHab').innerHTML = rows.length
        ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
            <table class="ds-table">
              <thead><tr><th>Código</th><th>Nombre</th><th>Mín</th><th>Stock</th></tr></thead>
              <tbody>${rows.map(r2=>`
                <tr>
                  <td><span class="chip">${r2.codigo}</span></td>
                  <td class="td-primary">${r2.nombre}</td>
                  <td style="font-family:var(--font-mono)">${fmtNum(r2.stock_min)}</td>
                  <td>${stockBar(r2.stock_actual, r2.stock_min)}</td>
                </tr>`).join('')}
              </tbody>
            </table>
          </div>`
        : `<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:12px">Sin productos asignados.</div>`;
    });
  }

  renderCatalogo();
});

/* ══════════════════════════════════════════════════════════════
   MOVIMIENTOS
══════════════════════════════════════════════════════════════ */
registerTab('movimientos', async area => {
  setPageHeader('Movimientos', 'Registro de ingresos, salidas y ajustes de stock');

  area.innerHTML = `
    <div class="ds-card" style="max-width:580px">
      <div class="ds-card-title"><i class="bi bi-arrow-left-right"></i>Nuevo Movimiento</div>

      <div class="ds-field">
        <label class="ds-label">Producto <span class="req">*</span></label>
        <div style="position:relative">
          <div class="ds-input-group">
            <i class="bi bi-search ds-input-icon"></i>
            <input type="text" id="movBusca" class="ds-input" placeholder="Buscar producto por nombre...">
          </div>
          <div id="movDrop" class="ds-autocomplete" style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;z-index:200"></div>
        </div>
        <input type="hidden" id="movCodigo">
      </div>

      <div class="ds-grid-2" style="gap:12px">
        <div class="ds-field">
          <label class="ds-label">Fecha <span class="req">*</span></label>
          <input type="date" id="movFecha" class="ds-input" value="${new Date().toISOString().slice(0,10)}">
        </div>
        <div class="ds-field">
          <label class="ds-label">Tipo <span class="req">*</span></label>
          <select id="movTipo" class="ds-select">
            <option value="INGRESO">↑ Ingreso</option>
            <option value="SALIDA">↓ Salida</option>
            <option value="AJUSTE_POSITIVO">+ Ajuste Positivo</option>
            <option value="AJUSTE_NEGATIVO">− Ajuste Negativo</option>
          </select>
        </div>
      </div>

      <div class="ds-field">
        <label class="ds-label">Cantidad <span class="req">*</span></label>
        <input type="number" id="movCantidad" class="ds-input" min="0.01" step="0.01" placeholder="0">
      </div>

      <div class="ds-field">
        <label class="ds-label">Observaciones</label>
        <textarea id="movObs" class="ds-textarea" placeholder="Opcional — referencia, motivo, etc."></textarea>
      </div>

      <div class="btn-group">
        <button id="btnGuardarMov" class="btn btn-primary"><i class="bi bi-save"></i>Guardar Movimiento</button>
        <button id="btnLimpiarMov" class="btn btn-ghost"><i class="bi bi-arrow-counterclockwise"></i>Limpiar</button>
      </div>
    </div>`;

  let movTimer;
  $('movBusca').addEventListener('input', function () {
    clearTimeout(movTimer);
    movTimer = setTimeout(async () => {
      const q = this.value.trim();
      if (!q) { $('movDrop').style.display='none'; return; }
      const r = await api('GET', '/api/movimientos/buscar-producto', { q, cliente: clienteActual() });
      const items = r?.data || [];
      $('movDrop').innerHTML = items.length
        ? items.map(p => `
          <div class="ds-autocomplete-item mov-sug" data-codigo="${p.codigo}" data-nombre="${p.nombre}">
            <span class="item-code">${p.codigo}</span>
            <span style="flex:1">${p.nombre}</span>
            <span style="font-size:10px;color:var(--text-muted)">${p.unidad}</span>
          </div>`).join('')
        : '<div class="ds-autocomplete-item" style="color:var(--text-muted);font-size:12px">Sin resultados</div>';
      $('movDrop').style.display = 'block';
      $$('.mov-sug').forEach(el => {
        el.addEventListener('click', () => {
          $('movBusca').value  = el.dataset.nombre;
          $('movCodigo').value = el.dataset.codigo;
          $('movDrop').style.display = 'none';
          $('movCantidad').focus();
        });
      });
    }, 260);
  });
  document.addEventListener('click', e => {
    if (!$('movBusca')?.contains(e.target)) $('movDrop').style.display = 'none';
  });

  $('btnLimpiarMov').addEventListener('click', () => {
    $('movBusca').value = ''; $('movCodigo').value = '';
    $('movCantidad').value = ''; $('movObs').value = '';
    $('movFecha').value = new Date().toISOString().slice(0,10);
    $('movTipo').value = 'INGRESO';
  });

  $('btnGuardarMov').addEventListener('click', async () => {
    if (!$('movCodigo').value)   { toast('Seleccione un producto.','warning'); return; }
    if (!$('movCantidad').value || parseFloat($('movCantidad').value) <= 0) {
      toast('Ingrese una cantidad válida.','warning'); return;
    }
    const r = await api('POST', '/api/movimientos', {
      cliente     : clienteActual(),
      codigo      : $('movCodigo').value,
      fecha       : $('movFecha').value,
      tipo        : $('movTipo').value,
      cantidad    : parseFloat($('movCantidad').value),
      observaciones: $('movObs').value,
    });
    toast(r?.mensaje || 'Error', r?.ok ? 'success' : 'danger');
    if (r?.ok) $('btnLimpiarMov').click();
  });
});

/* ══════════════════════════════════════════════════════════════
   INVENTARIO
══════════════════════════════════════════════════════════════ */
registerTab('inventario', async area => {
  setPageHeader('Inventario', 'Stock actual en tiempo real',
    `<a class="btn btn-secondary" href="${API}/api/inventario/exportar?cliente=${clienteActual()}" target="_blank">
      <i class="bi bi-download"></i>Exportar CSV
    </a>
    <button class="btn btn-primary" id="btnRefreshInv"><i class="bi bi-arrow-clockwise"></i>Actualizar</button>`);

  area.innerHTML = `
    <div class="filter-row" style="margin-bottom:14px">
      <div class="ds-field">
        <label class="ds-label">Filtrar</label>
        <div class="ds-input-group">
          <i class="bi bi-search ds-input-icon"></i>
          <input type="text" id="filtroInv" class="ds-input" placeholder="Nombre, código o grupo...">
        </div>
      </div>
      <div class="ds-field">
        <label class="ds-label">Estado</label>
        <select id="filtroEstado" class="ds-select">
          <option value="">Todos</option>
          <option value="ok">Normal</option>
          <option value="low">Stock bajo</option>
          <option value="empty">Sin stock</option>
        </select>
      </div>
    </div>
    <div class="ds-card ds-card-flush">
      <div id="tablaInv" style="padding-bottom:4px">${skelRows()}</div>
    </div>`;

  let stockData = [];

  function renderStock(d) {
    $('tablaInv').innerHTML = d.length
      ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
          <table class="ds-table">
            <thead><tr><th>Cliente</th><th>Código</th><th>Producto</th><th>Unidad</th><th>Grupo</th><th>Mín</th><th>Stock Actual</th></tr></thead>
            <tbody>${d.map(r => `
              <tr>
                <td style="color:var(--text-muted)">${r.cliente_nombre}</td>
                <td><span class="chip">${r.codigo}</span></td>
                <td class="td-primary">${r.nombre}</td>
                <td style="color:var(--text-muted)">${r.unidad}</td>
                <td><span class="badge badge-info" style="font-size:10px">${r.grupo}</span></td>
                <td style="font-family:var(--font-mono)">${fmtNum(r.stock_min)}</td>
                <td>${stockBar(r.stock_actual, r.stock_min)}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`
      : `<div style="padding:40px;text-align:center;color:var(--text-muted)">Sin datos de inventario.</div>`;
  }

  function applyFilters() {
    const txt  = ($('filtroInv')?.value || '').toLowerCase();
    const est  = $('filtroEstado')?.value || '';
    let d = stockData;
    if (txt) d = d.filter(r => r.nombre.toLowerCase().includes(txt) || r.codigo.toLowerCase().includes(txt) || r.grupo.toLowerCase().includes(txt));
    if (est === 'ok')    d = d.filter(r => r.stock_actual > 0 && (r.stock_min <= 0 || r.stock_actual > r.stock_min));
    if (est === 'low')   d = d.filter(r => r.stock_actual > 0 && r.stock_min > 0 && r.stock_actual <= r.stock_min);
    if (est === 'empty') d = d.filter(r => r.stock_actual <= 0);
    renderStock(d);
  }

  async function cargar() {
    $('tablaInv').innerHTML = `<div style="padding:16px">${skelRows()}</div>`;
    const r = await api('GET', '/api/inventario', { cliente: clienteActual() });
    stockData = r?.data || [];
    applyFilters();
  }

  cargar();
  $('pageActions').querySelector('#btnRefreshInv')?.addEventListener('click', cargar);
  $('filtroInv').addEventListener('input', applyFilters);
  $('filtroEstado').addEventListener('change', applyFilters);
});

/* ══════════════════════════════════════════════════════════════
   REPORTES
══════════════════════════════════════════════════════════════ */
registerTab('reportes', async area => {
  const hoy   = new Date().toISOString().slice(0,10);
  const inicio = new Date(new Date().setDate(1)).toISOString().slice(0,10);

  setPageHeader('Reportes', 'Historial de movimientos filtrable');

  area.innerHTML = `
    <div class="ds-card">
      <div class="ds-card-title"><i class="bi bi-funnel"></i>Filtros</div>
      <div class="filter-row">
        <div class="ds-field"><label class="ds-label">Desde</label><input type="date" id="repDesde" class="ds-input" value="${inicio}"></div>
        <div class="ds-field"><label class="ds-label">Hasta</label><input type="date" id="repHasta" class="ds-input" value="${hoy}"></div>
        <div class="ds-field" style="min-width:160px">
          <label class="ds-label">Tipo</label>
          <select id="repTipo" class="ds-select">
            <option value="">Todos los tipos</option>
            <option value="INGRESO">Ingreso</option>
            <option value="SALIDA">Salida</option>
            <option value="AJUSTE_POSITIVO">Ajuste Positivo</option>
            <option value="AJUSTE_NEGATIVO">Ajuste Negativo</option>
          </select>
        </div>
        <div class="ds-field" style="align-self:flex-end">
          <button id="btnGenRep" class="btn btn-primary"><i class="bi bi-search"></i>Generar</button>
        </div>
      </div>
    </div>
    <div class="ds-card ds-card-flush">
      <div id="tablaRep" style="padding:20px;color:var(--text-muted);font-size:12.5px">
        Configure los filtros y presione Generar.
      </div>
    </div>`;

  $('btnGenRep').addEventListener('click', async () => {
    $('tablaRep').innerHTML = `<div style="padding:16px">${skelRows()}</div>`;
    const r = await api('GET', '/api/reportes/historial', {
      cliente: clienteActual(),
      desde  : $('repDesde').value,
      hasta  : $('repHasta').value,
      tipo   : $('repTipo').value,
    });
    const rows = r?.data || [];
    $('tablaRep').innerHTML = rows.length
      ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
          <table class="ds-table">
            <thead><tr><th>Fecha</th><th>Cliente</th><th>Código</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Stock Res.</th><th>Usuario</th></tr></thead>
            <tbody>${rows.map(r2 => `
              <tr>
                <td style="font-family:var(--font-mono);font-size:11px">${fmtDate(r2.fecha_movimiento)}</td>
                <td style="color:var(--text-muted)">${r2.cliente_nombre}</td>
                <td><span class="chip">${r2.codigo}</span></td>
                <td class="td-primary">${r2.producto}</td>
                <td><span class="badge badge-${r2.tipo.toLowerCase()}">${r2.tipo.replace('_',' ')}</span></td>
                <td style="font-family:var(--font-mono);font-weight:600">${fmtNum(r2.cantidad)}</td>
                <td style="font-family:var(--font-mono)">${fmtNum(r2.stock_resultante)}</td>
                <td style="color:var(--text-muted);font-size:11px">${r2.registrado_por}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`
      : `<div style="padding:32px;text-align:center;color:var(--text-muted)">Sin resultados para el periodo.</div>`;
  });
});

/* ══════════════════════════════════════════════════════════════
   BUSCAR
══════════════════════════════════════════════════════════════ */
registerTab('buscar', async area => {
  setPageHeader('Buscar', 'Búsqueda global de productos en inventario');

  area.innerHTML = `
    <div class="ds-card">
      <div class="ds-input-group" style="margin-bottom:0">
        <i class="bi bi-search ds-input-icon"></i>
        <input type="text" id="buscaQ" class="ds-input" placeholder="Buscar por código, nombre, grupo o cliente..." style="padding:10px 11px 10px 34px;font-size:13px">
      </div>
    </div>
    <div class="ds-card ds-card-flush">
      <div id="resBusca" style="padding:28px;text-align:center;color:var(--text-muted);font-size:12.5px">
        <i class="bi bi-search" style="font-size:28px;display:block;margin-bottom:8px;opacity:.3"></i>
        Ingresa al menos un carácter para buscar.
      </div>
    </div>`;

  let bTimer;
  $('buscaQ').addEventListener('input', function () {
    clearTimeout(bTimer);
    bTimer = setTimeout(async () => {
      const q = this.value.trim();
      if (!q) return;
      $('resBusca').innerHTML = `<div style="padding:16px">${skelRows()}</div>`;
      const r = await api('GET', '/api/buscar', { q, cliente: clienteActual() });
      const rows = r?.data || [];
      $('resBusca').innerHTML = rows.length
        ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
            <table class="ds-table">
              <thead><tr><th>Cliente</th><th>Código</th><th>Producto</th><th>Unidad</th><th>Grupo</th><th>Mín</th><th>Stock</th></tr></thead>
              <tbody>${rows.map(r2 => `
                <tr>
                  <td style="color:var(--text-muted)">${r2.cliente_nombre}</td>
                  <td><span class="chip">${r2.codigo}</span></td>
                  <td class="td-primary">${r2.nombre}</td>
                  <td style="color:var(--text-muted)">${r2.unidad}</td>
                  <td><span class="badge badge-info" style="font-size:10px">${r2.grupo}</span></td>
                  <td style="font-family:var(--font-mono)">${fmtNum(r2.stock_min)}</td>
                  <td>${stockBar(r2.stock_actual, r2.stock_min)}</td>
                </tr>`).join('')}
              </tbody>
            </table>
          </div>`
        : `<div style="padding:32px;text-align:center;color:var(--text-muted)">Sin resultados para "<strong>${q}</strong>".</div>`;
    }, 300);
  });
  $('buscaQ').focus();
});

/* ══════════════════════════════════════════════════════════════
   CONFIGURACIÓN
══════════════════════════════════════════════════════════════ */
registerTab('configuracion', area => {
  setPageHeader('Configuración', 'Herramientas de administración del sistema');

  area.innerHTML = `
    <div class="ds-grid-2" style="gap:14px;max-width:720px">
      <div class="ds-card">
        <div class="ds-card-title"><i class="bi bi-shield-check"></i>Validar Integridad</div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;line-height:1.6">
          Verifica coherencia entre tablas: caché de stock, clientes inactivos con stock, referencias huérfanas.
        </p>
        <button id="btnValidar" class="btn btn-secondary" style="width:100%">
          <i class="bi bi-search"></i>Ejecutar Validación
        </button>
        <div id="resValidar" style="margin-top:12px"></div>
      </div>
      <div class="ds-card">
        <div class="ds-card-title"><i class="bi bi-arrow-repeat"></i>Recalcular Stock</div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:14px;line-height:1.6">
          Reconstruye el caché de stock_actual desde el historial completo de movimientos.
        </p>
        <button id="btnRecalcular" class="btn btn-secondary" style="width:100%">
          <i class="bi bi-arrow-clockwise"></i>Recalcular Ahora
        </button>
      </div>
    </div>`;

  $('btnValidar').addEventListener('click', async () => {
    $('btnValidar').disabled = true;
    $('btnValidar').innerHTML = '<i class="bi bi-hourglass-split"></i>Validando...';
    const r = await api('GET', '/api/configuracion/validar');
    $('btnValidar').disabled = false;
    $('btnValidar').innerHTML = '<i class="bi bi-search"></i>Ejecutar Validación';
    if (!r) return;
    const errs = r.data?.errores || [];
    $('resValidar').innerHTML = errs.length
      ? `<div class="ds-alert ds-alert-warning">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <div>
            <strong>${errs.length} problema(s) encontrado(s):</strong>
            <ul style="margin:6px 0 0 14px;font-size:11.5px">
              ${errs.map(e => `<li>${e}</li>`).join('')}
            </ul>
          </div>
        </div>`
      : `<div class="ds-alert ds-alert-success"><i class="bi bi-check-circle-fill"></i>Sistema íntegro — sin errores.</div>`;
  });

  $('btnRecalcular').addEventListener('click', async () => {
    if (!confirm('¿Recalcular stock desde el historial completo?')) return;
    $('btnRecalcular').disabled = true;
    $('btnRecalcular').innerHTML = '<i class="bi bi-hourglass-split"></i>Procesando...';
    const r = await api('POST', '/api/configuracion/recalcular');
    $('btnRecalcular').disabled = false;
    $('btnRecalcular').innerHTML = '<i class="bi bi-arrow-clockwise"></i>Recalcular Ahora';
    toast(r?.mensaje || 'Error', r?.ok ? 'success' : 'danger');
  });
});

/* ══════════════════════════════════════════════════════════════
   USUARIOS
══════════════════════════════════════════════════════════════ */
registerTab('usuarios', async area => {
  setPageHeader('Usuarios y Permisos', 'Gestión de accesos al sistema',
    `<button class="btn btn-primary" id="btnNuevoUsr"><i class="bi bi-person-plus"></i>Nuevo Usuario</button>`);

  area.innerHTML = `
    <div id="formNuevoUsr" class="ds-card" style="display:none;max-width:480px;margin-bottom:14px">
      <div class="ds-card-title"><i class="bi bi-person-plus"></i>Crear Usuario</div>
      <div class="ds-grid-2" style="gap:12px">
        <div class="ds-field">
          <label class="ds-label">Usuario <span class="req">*</span></label>
          <input type="text" id="usrNombre" class="ds-input" placeholder="nombre_usuario">
        </div>
        <div class="ds-field">
          <label class="ds-label">Contraseña <span class="req">*</span></label>
          <input type="password" id="usrPass" class="ds-input" placeholder="••••••">
        </div>
      </div>
      <div class="ds-grid-2" style="gap:12px">
        <div class="ds-field">
          <label class="ds-label">Rol <span class="req">*</span></label>
          <select id="usrRol" class="ds-select">
            <option value="ADMINISTRADOR">Administrador</option>
            <option value="ALMACENERO">Almacenero</option>
            <option value="CLIENTE">Cliente</option>
          </select>
        </div>
        <div class="ds-field" id="usrClienteWrap" style="display:none">
          <label class="ds-label">Cliente Asociado <span class="req">*</span></label>
          <select id="usrCliente" class="ds-select"><option value="">— Seleccione —</option></select>
        </div>
      </div>
      <div class="btn-group">
        <button id="btnCrearUsr" class="btn btn-primary"><i class="bi bi-check-lg"></i>Crear</button>
        <button id="btnCancelarUsr" class="btn btn-ghost"><i class="bi bi-x"></i>Cancelar</button>
      </div>
    </div>

    <div class="ds-card ds-card-flush" style="margin-bottom:14px">
      <div style="padding:14px 18px 10px" class="ds-card-title"><i class="bi bi-people"></i>Usuarios Registrados</div>
      <div id="tablaUsuarios">${skelRows()}</div>
    </div>

    <div class="ds-card">
      <div class="ds-card-title"><i class="bi bi-shield-lock"></i>Permisos por Rol</div>
      <div id="matrizRoles">${skelRows(3)}</div>
      <div class="ds-card-title" style="margin-top:18px"><i class="bi bi-person-badge"></i>Overrides Individuales</div>
      <div id="matrizUsuarios">${skelRows(2)}</div>
    </div>`;

  // Botón Nuevo Usuario
  $('pageActions').querySelector('#btnNuevoUsr')?.addEventListener('click', () => {
    $('formNuevoUsr').style.display = 'block';
    $('usrNombre').focus();
  });
  $('btnCancelarUsr')?.addEventListener('click', () => { $('formNuevoUsr').style.display = 'none'; });

 api('GET', '/api/clientes', { activos:'1' }).then(r => {
    if (!r?.data?.length) {
      $('usrCliente').insertAdjacentHTML('beforeend',
        `<option value="" disabled>Sin clientes activos</option>`);
      return;
    }
    r.data.forEach(c => {
      $('usrCliente').insertAdjacentHTML('beforeend',
        `<option value="${c.codigo}">${c.nombre}</option>`);
    });
  });

  $('usrRol').addEventListener('change', function () {
    const wrap = $('usrClienteWrap');
    wrap.style.display = this.value === 'CLIENTE' ? 'block' : 'none';
    if (this.value !== 'CLIENTE') $('usrCliente').value = '';
  });

async function cargarUsuarios() {
    const [r, clientes] = await Promise.all([
      api('GET', '/api/usuarios'),
      api('GET', '/api/clientes', { activos: '1' }),
    ]);
    const optsCli = (clientes?.data || []).map(c =>
      `<option value="${c.codigo}">${c.nombre}</option>`).join('');

    $('tablaUsuarios').innerHTML = r?.data?.length
      ? `<div class="ds-table-wrapper" style="border:none;border-radius:0">
          <table class="ds-table">
            <thead><tr><th>Usuario</th><th>Rol</th><th>Cliente</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>${r.data.map(u => `
              <tr>
                <td class="td-primary">${u.usuario}</td>
                <td><span class="badge badge-info">${u.rol}</span></td>
                <td style="color:var(--text-muted)">${u.cliente_nombre||'—'}</td>
                <td><span class="badge ${u.estado==='ACTIVO'?'badge-active':'badge-inactive'}">${u.estado}</span></td>
                <td>
                  <div class="btn-group">
         <button class="btn btn-xs btn-secondary btn-editar-usr"
               data-usuario="${u.usuario}"
               data-rol="${u.rol}"
               data-cliente="${u.cliente_codigo||''}"
   data-estado="${u.estado}">
  <i class="bi bi-pencil"></i>Editar
</button>
<button class="btn btn-xs ${u.estado==='ACTIVO'?'btn-danger':'btn-success'} btn-toggle-usr"
  data-usuario="${u.usuario}" data-estado="${u.estado==='ACTIVO'?'INACTIVO':'ACTIVO'}">
  <i class="bi ${u.estado==='ACTIVO'?'bi-pause-circle':'bi-play-circle'}"></i>
  ${u.estado==='ACTIVO'?'Inactivar':'Activar'}
</button>
${u.usuario !== 'admin' ? `
<button class="btn btn-xs btn-danger btn-eliminar-usr"
  data-usuario="${u.usuario}">
  <i class="bi bi-trash"></i>Eliminar
</button>` : ''}
</div>
                </td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`
      : `<div style="padding:24px;text-align:center;color:var(--text-muted)">Sin usuarios.</div>`;

    $$('.btn-eliminar-usr').forEach(btn => {
      btn.addEventListener('click', async () => {
        if (!confirm(`¿Eliminar el usuario "${btn.dataset.usuario}"? Esta acción no se puede deshacer.`)) return;
        const r2 = await api('POST', '/api/usuarios/eliminar', { usuario: btn.dataset.usuario });
        toast(r2?.mensaje || 'Error', r2?.ok ? 'success' : 'danger');
        if (r2?.ok) cargarUsuarios();
      });
    });

    $$('.btn-editar-usr').forEach(btn => {
      btn.addEventListener('click', () => {
        const usuario = btn.dataset.usuario;
        const rolActual = btn.dataset.rol;
        const clienteActual = btn.dataset.cliente;
        const estadoActual = btn.dataset.estado;

        openModal(
          `<i class="bi bi-pencil"></i> Editar Usuario: ${usuario}`,
          `<div class="ds-field">
            <label class="ds-label">Nueva Contraseña <span style="color:var(--text-muted);font-weight:400">(dejar vacío para no cambiar)</span></label>
            <input type="password" id="editPass" class="ds-input" placeholder="••••••">
          </div>
          <div class="ds-field">
            <label class="ds-label">Rol</label>
            <select id="editRol" class="ds-select">
              <option value="ADMINISTRADOR" ${rolActual==='ADMINISTRADOR'?'selected':''}>Administrador</option>
              <option value="ALMACENERO"    ${rolActual==='ALMACENERO'?'selected':''}>Almacenero</option>
              <option value="CLIENTE"       ${rolActual==='CLIENTE'?'selected':''}>Cliente</option>
            </select>
          </div>
          <div class="ds-field" id="editClienteWrap" style="display:${rolActual==='CLIENTE'?'block':'none'}">
            <label class="ds-label">Cliente Asociado</label>
            <select id="editCliente" class="ds-select">
              <option value="">— Seleccione —</option>
              ${optsCli}
            </select>
          </div>
          <div class="ds-field">
            <label class="ds-label">Estado</label>
            <select id="editEstado" class="ds-select">
              <option value="ACTIVO"   ${estadoActual==='ACTIVO'?'selected':''}>Activo</option>
              <option value="INACTIVO" ${estadoActual==='INACTIVO'?'selected':''}>Inactivo</option>
            </select>
          </div>`,
          `<button class="btn btn-ghost" onclick="closeModal()">Cancelar</button>
           <button class="btn btn-primary" id="btnGuardarEdit"><i class="bi bi-check-lg"></i>Guardar cambios</button>`
        );

        // Setear cliente actual en el select
        if (clienteActual && document.getElementById('editCliente')) {
          document.getElementById('editCliente').value = clienteActual;
        }

        // Mostrar/ocultar selector de cliente según rol
        document.getElementById('editRol').addEventListener('change', function() {
          document.getElementById('editClienteWrap').style.display =
            this.value === 'CLIENTE' ? 'block' : 'none';
        });

        document.getElementById('btnGuardarEdit').addEventListener('click', async () => {
          const payload = {
            usuario : usuario,
            rol     : document.getElementById('editRol').value,
            estado  : document.getElementById('editEstado').value,
            cliente : document.getElementById('editCliente')?.value || '',
          };
          const pass = document.getElementById('editPass').value;
          if (pass) payload.password = pass;

          const r2 = await api('POST', '/api/usuarios/actualizar', payload);
          toast(r2?.mensaje || 'Error', r2?.ok ? 'success' : 'danger');
          if (r2?.ok) { closeModal(); cargarUsuarios(); }
        });
      });
    });
  }

  $('btnCrearUsr')?.addEventListener('click', async () => {
    const r = await api('POST', '/api/usuarios', {
      usuario: $('usrNombre').value.trim(), password: $('usrPass').value,
      rol: $('usrRol').value, cliente: $('usrCliente').value,
    });
    toast(r?.mensaje || 'Error', r?.ok ? 'success' : 'danger');
    if (r?.ok) { $('formNuevoUsr').style.display='none'; $('usrNombre').value=''; $('usrPass').value=''; cargarUsuarios(); }
  });

  async function cargarPermisos() {
    const r = await api('GET', '/api/usuarios/permisos');
    if (!r?.ok) return;
    const { secciones, porRol, porUsuario } = r.data;
    const roles  = Object.keys(porRol);
    const slugs  = secciones.map(s => s.slug);
    const labels = secciones.map(s => s.etiqueta);

    $('matrizRoles').innerHTML = `
      <div class="ds-table-wrapper">
        <table class="ds-table">
          <thead><tr>
            <th>Sección</th>
            ${roles.map(rol => `<th style="text-align:center">${rol}</th>`).join('')}
          </tr></thead>
          <tbody>${slugs.map((slug, i) => `
            <tr>
              <td class="td-primary" style="font-size:12px">${labels[i]}</td>
              ${roles.map(rol => {
                const on = porRol[rol]?.[slug] ? true : false;
                return `<td style="text-align:center">
                  <input type="checkbox" class="perm-toggle perm-rol-toggle"
                    data-rol="${rol}" data-slug="${slug}" ${on?'checked':''}>
                </td>`;
              }).join('')}
            </tr>`).join('')}
          </tbody>
        </table>
      </div>`;

    $$('.perm-rol-toggle').forEach(chk => {
      chk.addEventListener('change', async function () {
        const r2 = await api('POST', '/api/usuarios/permisos', {
          tipo:'ROL', clave:this.dataset.rol, seccion:this.dataset.slug, permitido:this.checked
        });
        toast(r2?.mensaje || 'Error', r2?.ok ? 'success' : 'danger');
        if (!r2?.ok) this.checked = !this.checked;
      });
    });

    const usrList = Object.keys(porUsuario);
    $('matrizUsuarios').innerHTML = usrList.length
      ? `<p style="font-size:11.5px;color:var(--text-muted);margin-bottom:8px">
          Haz clic en un badge coloreado para eliminar el override — el usuario volverá a heredar el permiso de su rol.
        </p>
        <div class="ds-table-wrapper">
          <table class="ds-table">
            <thead><tr><th>Usuario</th>${slugs.map((_,i)=>`<th style="font-size:10px;text-align:center">${labels[i]}</th>`).join('')}</tr></thead>
            <tbody>${usrList.map(usr => `
              <tr>
                <td class="td-primary">${usr}</td>
                ${slugs.map(slug => {
                  const tiene = Object.prototype.hasOwnProperty.call(porUsuario[usr], slug);
                  const val   = porUsuario[usr]?.[slug];
                  return `<td style="text-align:center">
                    ${tiene
                      ? `<span class="badge ${val?'badge-ok':'badge-empty'} ovr-badge"
                           data-usr="${usr}" data-slug="${slug}" style="cursor:pointer"
                           title="Click para quitar override">${val?'✓':'✗'}</span>`
                      : `<span style="color:var(--text-muted);font-size:11px">—</span>`}
                  </td>`;
                }).join('')}
              </tr>`).join('')}
            </tbody>
          </table>
        </div>`
      : `<p style="font-size:12px;color:var(--text-muted)">Sin overrides individuales configurados.</p>`;

    $$('.ovr-badge').forEach(badge => {
      badge.addEventListener('click', async function () {
        if (!confirm(`¿Quitar override de "${this.dataset.slug}" para "${this.dataset.usr}"?`)) return;
        const r2 = await api('POST', '/api/usuarios/permisos/quitar', { usuario:this.dataset.usr, seccion:this.dataset.slug });
        toast(r2?.mensaje || 'Error', r2?.ok ? 'success' : 'danger');
        if (r2?.ok) cargarPermisos();
      });
    });
  }

  cargarUsuarios();
  cargarPermisos();
});

/* ══════════════════════════════════════════════════════════════
   ARRANQUE
══════════════════════════════════════════════════════════════ */
(function init() {
  const primera = Object.keys(PERM).find(k => PERM[k]);
  if (primera) showTab(primera);
})();
