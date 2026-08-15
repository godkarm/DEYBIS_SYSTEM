<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>DEYBIS SYSTEM — Iniciar Sesión</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
/* ── Variables ─────────────────────────────────────────────── */
:root {
  --bg      : #080E1C;
  --surface : #0D1526;
  --elevated: #111E35;
  --border  : rgba(255,255,255,.10);
  --accent  : #2563EB;
  --cyan    : #06B6D4;
  --text    : #F1F5F9;
  --muted   : #64748B;
  --danger  : #EF4444;
  --glow    : rgba(37,99,235,.35);
}
[data-theme="light"] {
  --bg      : #F0F4FA;
  --surface : #FFFFFF;
  --elevated: #F8FAFF;
  --border  : rgba(0,0,0,.10);
  --text    : #0F172A;
  --muted   : #94A3B8;
}

/* ── Reset ────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family    : 'Inter', system-ui, sans-serif;
  background     : var(--bg);
  min-height     : 100vh;
  display        : flex;
  align-items    : center;
  justify-content: center;
  padding        : 20px;
  transition     : background .3s;
  -webkit-font-smoothing: antialiased;
}

/* ── Card ─────────────────────────────────────────────────── */
.card {
  background   : var(--surface);
  border       : 1px solid var(--border);
  border-radius: 20px;
  padding      : 36px 32px 30px;
  width        : 100%;
  max-width    : 400px;
  position     : relative;
  overflow     : hidden;
  box-shadow   : 0 24px 60px rgba(0,0,0,.45);
}

/* Glow de fondo */
.card::before {
  content   : '';
  position  : absolute;
  top       : -60px; right: -60px;
  width     : 220px; height: 220px;
  background: radial-gradient(circle, rgba(37,99,235,.18), transparent 70%);
  pointer-events: none;
}
.card::after {
  content   : '';
  position  : absolute;
  bottom    : -40px; left: -40px;
  width     : 160px; height: 160px;
  background: radial-gradient(circle, rgba(6,182,212,.12), transparent 70%);
  pointer-events: none;
}

/* ── Brand ────────────────────────────────────────────────── */
.brand {
  display        : flex;
  align-items    : center;
  gap            : 12px;
  margin-bottom  : 28px;
}
.brand-logo {
  width         : 44px;
  height        : 44px;
  border-radius : 12px;
  background    : linear-gradient(135deg, var(--accent), var(--cyan));
  display       : flex;
  align-items   : center;
  justify-content: center;
  font-size     : 22px;
  box-shadow    : 0 0 24px var(--glow);
  flex-shrink   : 0;
}
.brand-text-title {
  font-size    : 17px;
  font-weight  : 700;
  color        : var(--text);
  letter-spacing: .06em;
  line-height  : 1.1;
}
.brand-text-sub {
  font-size    : 11px;
  color        : var(--muted);
  margin-top   : 2px;
}

/* ── Form ─────────────────────────────────────────────────── */
.field { margin-bottom: 14px; }
.label {
  display      : block;
  font-size    : 11.5px;
  font-weight  : 500;
  color        : var(--muted);
  margin-bottom: 5px;
  letter-spacing: .02em;
}

.input-wrap { position: relative; }
.input-wrap .ico {
  position  : absolute;
  left      : 11px;
  top       : 50%;
  transform : translateY(-50%);
  color     : var(--muted);
  font-size : 15px;
  pointer-events: none;
}

input[type="text"],
input[type="password"] {
  width         : 100%;
  background    : var(--elevated);
  border        : 1px solid var(--border);
  border-radius : 8px;
  color         : var(--text);
  font-family   : 'Inter', system-ui, sans-serif;
  font-size     : 13px;
  padding       : 9px 12px 9px 36px;
  outline       : none;
  transition    : border-color .15s, box-shadow .15s;
  appearance    : none;
}
input:focus {
  border-color : var(--accent);
  box-shadow   : 0 0 0 3px var(--glow);
}
input::placeholder { color: var(--muted); }

/* Toggle contraseña */
.eye-btn {
  position  : absolute;
  right     : 10px;
  top       : 50%;
  transform : translateY(-50%);
  background: none;
  border    : none;
  color     : var(--muted);
  cursor    : pointer;
  font-size : 15px;
  padding   : 2px;
  line-height: 1;
}
.eye-btn:hover { color: var(--text); }

/* ── Error alert ─────────────────────────────────────────── */
.alert {
  display      : none;
  align-items  : center;
  gap          : 7px;
  background   : rgba(239,68,68,.12);
  border       : 1px solid rgba(239,68,68,.25);
  border-radius: 8px;
  color        : #FCA5A5;
  font-size    : 12px;
  padding      : 9px 12px;
  margin-bottom: 14px;
  line-height  : 1.5;
}
.alert.show { display: flex; }
.alert i { font-size: 15px; flex-shrink: 0; }

/* ── Submit button ────────────────────────────────────────── */
.btn-submit {
  width         : 100%;
  background    : var(--accent);
  border        : none;
  border-radius : 8px;
  color         : #fff;
  font-family   : 'Inter', system-ui, sans-serif;
  font-size     : 13.5px;
  font-weight   : 600;
  padding       : 10px 16px;
  cursor        : pointer;
  display       : flex;
  align-items   : center;
  justify-content: center;
  gap           : 7px;
  transition    : background .15s, box-shadow .15s, transform .1s;
  margin-top    : 18px;
  letter-spacing: .02em;
}
.btn-submit:hover:not(:disabled) {
  background : #1D4ED8;
  box-shadow : 0 0 0 3px var(--glow);
}
.btn-submit:active:not(:disabled) { transform: scale(.98); }
.btn-submit:disabled { opacity: .5; cursor: not-allowed; }

/* ── Spinner ─────────────────────────────────────────────── */
.spinner {
  width         : 14px;
  height        : 14px;
  border        : 2px solid rgba(255,255,255,.3);
  border-top-color: #fff;
  border-radius : 50%;
  animation     : spin .7s linear infinite;
  display       : none;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn-submit.loading .spinner { display: block; }
.btn-submit.loading .btn-txt { display: none; }

/* ── Footer ──────────────────────────────────────────────── */
.footer {
  text-align : center;
  margin-top : 20px;
  font-size  : 11px;
  color      : var(--muted);
}
</style>
</head>
<body>

<div class="card">
  <div class="brand">
    <div class="brand-logo">📦</div>
    <div>
      <div class="brand-text-title">DEYBIS SYSTEM</div>
      <div class="brand-text-sub">Sistema de Control de Inventario</div>
    </div>
  </div>

  <div class="alert" id="alertEl">
    <i class="bi bi-exclamation-circle"></i>
    <span id="alertMsg"></span>
  </div>

  <form id="loginForm" novalidate>
    <div class="field">
      <label class="label" for="usuario">Usuario</label>
      <div class="input-wrap">
        <i class="bi bi-person ico"></i>
        <input type="text" id="usuario" name="usuario"
          autocomplete="username" placeholder="nombre de usuario" required autofocus>
      </div>
    </div>

    <div class="field">
      <label class="label" for="password">Contraseña</label>
      <div class="input-wrap">
        <i class="bi bi-lock ico"></i>
        <input type="password" id="password" name="password"
          autocomplete="current-password" placeholder="••••••••" required>
        <button type="button" class="eye-btn" id="eyeBtn" tabindex="-1">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn-submit" id="btnLogin">
      <div class="spinner"></div>
      <span class="btn-txt">
        <i class="bi bi-box-arrow-in-right"></i>
        Iniciar Sesión
      </span>
    </button>
  </form>

  <div class="footer">
    <?= htmlspecialchars(APP_NAME) ?> v<?= htmlspecialchars(APP_VERSION) ?>
  </div>
</div>

<script>
const BASE = '<?= rtrim(dirname($_SERVER["SCRIPT_NAME"]), "/") ?>';

/* ── Toggle contraseña ────────────────────────────────────── */
const eyeBtn  = document.getElementById('eyeBtn');
const passInp = document.getElementById('password');
const eyeIcon = document.getElementById('eyeIcon');
eyeBtn.addEventListener('click', () => {
  const isPass = passInp.type === 'password';
  passInp.type    = isPass ? 'text' : 'password';
  eyeIcon.className = isPass ? 'bi bi-eye-slash' : 'bi bi-eye';
});

/* ── Alert helper ─────────────────────────────────────────── */
function showAlert(msg) {
  const el = document.getElementById('alertEl');
  document.getElementById('alertMsg').textContent = msg;
  el.classList.add('show');
}
function hideAlert() {
  document.getElementById('alertEl').classList.remove('show');
}

/* ── Login ────────────────────────────────────────────────── */
document.getElementById('loginForm').addEventListener('submit', async e => {
  e.preventDefault();
  hideAlert();

  const usuario  = document.getElementById('usuario').value.trim();
  const password = document.getElementById('password').value;

  if (!usuario || !password) {
    showAlert('Ingresa usuario y contraseña.');
    return;
  }

  const btn = document.getElementById('btnLogin');
  btn.disabled = true;
  btn.classList.add('loading');

  try {
    const res  = await fetch(BASE + '/api/login', {
      method : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body   : JSON.stringify({ usuario, password }),
    });
    const data = await res.json();

    if (data.ok) {
      // Redirigir a la SPA
      window.location.href = data.data.redirect;
    } else {
      showAlert(data.mensaje || 'Error al iniciar sesión.');
      btn.disabled = false;
      btn.classList.remove('loading');
    }
  } catch {
    showAlert('Error de conexión. Verifica que el servidor esté activo.');
    btn.disabled = false;
    btn.classList.remove('loading');
  }
});

/* ── Enter en campo usuario → foco al password ────────────── */
document.getElementById('usuario').addEventListener('keydown', e => {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('password').focus();
  }
});
</script>
</body>
</html>
