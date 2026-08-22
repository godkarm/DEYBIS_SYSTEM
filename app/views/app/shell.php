<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($appName) ?> — Inventario</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= $baseUrl ?>/css/app.css">
</head>
<body>

<!-- ══ SIDEBAR ════════════════════════════════════════════════ -->
<aside id="sidebar">

  <!-- Brand -->
  <div class="sb-brand">
    <div class="sb-logo">
      <i class="bi bi-boxes"></i>
    </div>
    <div class="sb-brand-text">
      <span class="sb-title">DEYBIS</span>
      <span class="sb-sub">Sistema de Inventario</span>
    </div>
    <button id="btnCollapseNav" class="sb-collapse-btn" title="Colapsar menú">
      <i class="bi bi-layout-sidebar"></i>
    </button>
  </div>

  <!-- User card -->
  <div class="sb-user">
    <div class="sb-avatar">
      <?= strtoupper(substr($usuario['usuario'], 0, 1)) ?>
    </div>
    <div class="sb-user-info">
      <span class="sb-user-name"><?= htmlspecialchars($usuario['usuario']) ?></span>
      <span class="sb-role-badge sb-role-<?= strtolower($usuario['rol']) ?>">
        <?= htmlspecialchars($usuario['rol']) ?>
      </span>
    </div>
  </div>

  <!-- Selector de cliente -->
  <?php if ($usuario['rol'] !== 'CLIENTE'): ?>
  <div class="sb-client-selector">
    <div class="sb-client-label">
      <i class="bi bi-building"></i>
      <span>Cliente activo</span>
    </div>
    <select id="selectCliente" class="sb-select">
      <option value="">Todos los clientes</option>
    </select>
  </div>
  <?php else: ?>
  <div class="sb-client-active">
    <i class="bi bi-building-check"></i>
    <span><?= htmlspecialchars($usuario['cliente_nombre'] ?? $usuario['cliente_codigo'] ?? '') ?></span>
  </div>
  <?php endif; ?>

  <!-- Divider -->
  <div class="sb-divider"></div>

  <!-- Nav -->
  <nav class="sb-nav">
    <span class="sb-nav-label">Módulos</span>
    <?php
    $menu = [
      'dashboard'      => ['icon'=>'bi-speedometer2',      'label'=>'Dashboard'],
      'clientes'       => ['icon'=>'bi-building',           'label'=>'Clientes'],
      'productos'      => ['icon'=>'bi-box-seam',           'label'=>'Productos'],
      'requerimientos' => ['icon'=>'bi-clipboard-check',    'label'=>'Requerimientos'],
      'movimientos'    => ['icon'=>'bi-arrow-left-right',   'label'=>'Movimientos'],
      'inventario'     => ['icon'=>'bi-clipboard-data',     'label'=>'Inventario'],
      'kardex'         => ['icon'=>'bi-table',              'label'=>'Kardex'],
      'reportes'       => ['icon'=>'bi-graph-up-arrow',     'label'=>'Reportes'],
      'buscar'         => ['icon'=>'bi-search',             'label'=>'Buscar'],
    ];
    foreach ($menu as $slug => $item):
      if (empty($permisos[$slug])) continue;
    ?>
    <a href="#" class="sb-link" data-tab="<?= $slug ?>">
      <span class="sb-link-icon"><i class="bi <?= $item['icon'] ?>"></i></span>
      <span class="sb-link-text"><?= $item['label'] ?></span>
      <span class="sb-link-indicator"></span>
    </a>
    <?php endforeach; ?>

    <?php if (!empty($permisos['configuracion']) || !empty($permisos['usuarios'])): ?>
    <span class="sb-nav-label" style="margin-top:8px">Administración</span>
    <?php endif; ?>

    <?php if (!empty($permisos['configuracion'])): ?>
    <a href="#" class="sb-link" data-tab="configuracion">
      <span class="sb-link-icon"><i class="bi bi-gear-wide-connected"></i></span>
      <span class="sb-link-text">Configuración</span>
      <span class="sb-link-indicator"></span>
    </a>
    <?php endif; ?>
    <?php if (!empty($permisos['usuarios'])): ?>
    <a href="#" class="sb-link" data-tab="usuarios">
      <span class="sb-link-icon"><i class="bi bi-people"></i></span>
      <span class="sb-link-text">Usuarios</span>
      <span class="sb-link-indicator"></span>
    </a>
    <?php endif; ?>
  </nav>

  <!-- Footer -->
  <div class="sb-footer">
    <button id="btnTheme" class="sb-theme-btn" title="Cambiar tema">
      <i class="bi bi-moon-stars"></i>
    </button>
    <span class="sb-ver">v2.0</span>
    <button id="btnLogout" class="sb-logout-btn" title="Cerrar sesión">
      <i class="bi bi-box-arrow-right"></i>
      <span>Salir</span>
    </button>
  </div>
</aside>

<!-- ══ TOPBAR (mobile) ═══════════════════════════════════════ -->
<header id="topbar">
  <button id="btnMobileMenu" class="topbar-menu-btn">
    <i class="bi bi-list"></i>
  </button>
  <span class="topbar-title">DEYBIS SYSTEM</span>
  <div id="topbar-breadcrumb" class="topbar-breadcrumb"></div>
</header>

<!-- ══ MAIN ═══════════════════════════════════════════════════ -->
<main id="mainContent">
  <!-- Page header (se rellena por JS) -->
  <div id="pageHeader" class="page-header" style="display:none">
    <div class="page-header-left">
      <h1 class="page-title" id="pageTitle"></h1>
      <p  class="page-sub"   id="pageSub"></p>
    </div>
    <div class="page-header-right" id="pageActions"></div>
  </div>

  <!-- Content area -->
  <div id="tabContent" class="tab-content-area">
    <div class="empty-state">
      <div class="empty-icon"><i class="bi bi-boxes"></i></div>
      <h3>Bienvenido a DEYBIS SYSTEM</h3>
      <p>Selecciona una sección del menú para comenzar.</p>
    </div>
  </div>
</main>

<!-- ══ TOAST ══════════════════════════════════════════════════ -->
<div id="toastStack" class="toast-stack"></div>

<!-- ══ MODAL genérico ════════════════════════════════════════ -->
<div id="dsModal" class="ds-modal-overlay" style="display:none">
  <div class="ds-modal">
    <div class="ds-modal-header">
      <h3 class="ds-modal-title" id="dsModalTitle"></h3>
      <button class="ds-modal-close" id="dsModalClose"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="ds-modal-body" id="dsModalBody"></div>
    <div class="ds-modal-footer" id="dsModalFooter"></div>
  </div>
</div>

<!-- ══ OVERLAY sidebar mobile ════════════════════════════════ -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<script>
window.DS = {
  base    : '<?= $baseUrl ?>',
  usuario : <?= json_encode($usuario, JSON_UNESCAPED_UNICODE) ?>,
  permisos: <?= json_encode($permisos, JSON_UNESCAPED_UNICODE) ?>
};
</script>
<script src="<?= $baseUrl ?>/js/app.js"></script>
</body>
</html>