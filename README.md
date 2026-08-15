# DEYBIS SYSTEM v2.0
**Sistema de Control de Inventario — PHP 8.1+ · MySQL 8 · Bootstrap Icons**

---

## Instalación en XAMPP (Windows/Linux/Mac)

### Paso 1 — Copiar el proyecto

Copia la carpeta `deybis_system/` completa dentro de tu htdocs:

```
C:\xampp\htdocs\deybis_system\
```

### Paso 2 — Crear la base de datos

1. Abre **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Haz clic en **"Nueva"** (panel izquierdo)
3. Nombre: `deybis_system` — Cotejamiento: `utf8mb4_unicode_ci` → **Crear**
4. Selecciona la base `deybis_system` → pestaña **SQL**
5. Pega el contenido de `database/schema.sql` y ejecuta

### Paso 3 — Sembrar la contraseña del admin

Abre en el navegador:
```
http://localhost/deybis_system/database/seed_admin.php
```
Verás: *"Hash actualizado correctamente"*

> ⚠️ Borra o mueve `seed_admin.php` después de ejecutarlo.

### Paso 4 — Verificar configuración

Abre `config/app.php` y confirma:
```php
define('BASE_URL', '/deybis_system/public');
```
Si pusiste la carpeta en otro lugar, ajusta este valor.

Abre `config/database.php` y confirma credenciales:
```php
private string $host     = 'localhost';
private string $db       = 'deybis_system';
private string $user     = 'root';
private string $password = '';   // XAMPP por defecto tiene contraseña vacía
```

### Paso 5 — Acceder al sistema

```
http://localhost/deybis_system/public/
```

**Credenciales iniciales:**
- Usuario: `admin`
- Contraseña: `admin123`

---

## Requisitos mínimos

| Componente | Versión |
|---|---|
| PHP | 8.1+ |
| MySQL / MariaDB | 8.0+ / 10.4+ |
| Apache | 2.4+ con `mod_rewrite` activo |
| XAMPP | 8.1+ |

**Verificar que mod_rewrite está activo en XAMPP:**
- Windows: `C:\xampp\apache\conf\httpd.conf` → buscar `LoadModule rewrite_module` → quitar el `#`
- Reiniciar Apache desde el panel de XAMPP

---

## Estructura del proyecto

```
deybis_system/
├── config/
│   ├── app.php          ← BASE_URL y constantes (EDITAR AQUÍ)
│   └── database.php     ← Credenciales MySQL (EDITAR AQUÍ)
├── database/
│   ├── schema.sql       ← Ejecutar en phpMyAdmin
│   └── seed_admin.php   ← Ejecutar una sola vez en el navegador
├── public/              ← Document root (URL de acceso)
│   ├── index.php        ← Front controller
│   ├── .htaccess        ← URL rewriting
│   ├── css/app.css      ← Estilos (Design System v2)
│   └── js/app.js        ← SPA controller
├── app/
│   ├── controllers/     ← 11 controladores
│   ├── models/          ← 6 modelos PDO
│   ├── views/           ← Login + SPA shell
│   └── helpers/         ← Auth, Session, Response
└── storage/
    ├── logs/            ← Logs de errores PHP
    └── uploads/         ← Subidas (futuro)
```

---

## Módulos del sistema

| Módulo | Rol que lo ve |
|---|---|
| Dashboard | Todos |
| Clientes | Admin, Almacenero |
| Productos | Admin, Almacenero |
| Movimientos | Admin, Almacenero |
| Inventario | Todos |
| Reportes | Todos |
| Buscar | Todos |
| Configuración | Admin |
| Usuarios y Permisos | Admin |

---

## Solución de problemas

**ERR_TOO_MANY_REDIRECTS**
- Verifica que `BASE_URL` en `config/app.php` coincida con la URL real
- Verifica `RewriteBase` en `public/.htaccess`
- Borra las cookies del navegador y vuelve a intentar

**Error de conexión a la BD**
- Verifica que MySQL esté iniciado en XAMPP
- Revisa usuario/contraseña en `config/database.php`
- Confirma que la base de datos `deybis_system` existe en phpMyAdmin

**Página en blanco / Error 500**
- Activa la visualización de errores en XAMPP: `php.ini` → `display_errors = On`
- Revisa `storage/logs/` o el log de Apache en XAMPP

**mod_rewrite no funciona**
- Abre `C:\xampp\apache\conf\httpd.conf`
- Busca `#LoadModule rewrite_module` y quita el `#`
- Reinicia Apache
