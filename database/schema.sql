-- ============================================================
-- DEYBIS SYSTEM — Esquema de Base de Datos v2.1
-- Motor: MySQL 8.0+ / MariaDB 10.4+   Charset: utf8mb4
--
-- ORDEN CORRECTO (sin errores de FK):
--   1. clientes          (sin FK)
--   2. grupos            (sin FK)
--   3. unidades          (sin FK)
--   4. correlativos      (sin FK)
--   5. roles             (sin FK)
--   6. secciones         (sin FK)
--   7. productos         → grupos, unidades
--   8. producto_cliente  → clientes, productos
--   9. permisos_rol      → roles, secciones
--  10. usuarios          → roles, clientes
--  11. movimientos       → producto_cliente, usuarios   ← FK que fallaba
--  12. permisos_usuario  → usuarios, secciones
--  13. sesiones          → usuarios
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS deybis_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE deybis_system;

-- ============================================================
-- 1. CLIENTES
-- ============================================================
CREATE TABLE IF NOT EXISTS clientes (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo     VARCHAR(20)  NOT NULL UNIQUE,
  nombre     VARCHAR(150) NOT NULL,
  estado     ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_cli_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. GRUPOS DE PRODUCTO
-- ============================================================
CREATE TABLE IF NOT EXISTS grupos (
  id      SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre  VARCHAR(60) NOT NULL UNIQUE,
  prefijo VARCHAR(5)  NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. UNIDADES DE MEDIDA
-- ============================================================
CREATE TABLE IF NOT EXISTS unidades (
  id     SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. CORRELATIVOS
-- ============================================================
CREATE TABLE IF NOT EXISTS correlativos (
  prefijo    VARCHAR(5)    NOT NULL PRIMARY KEY,
  ultimo_num INT UNSIGNED  NOT NULL DEFAULT 0,
  updated_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. ROLES
-- ============================================================
CREATE TABLE IF NOT EXISTS roles (
  id     TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. SECCIONES (tabs / módulos del sistema)
-- ============================================================
CREATE TABLE IF NOT EXISTS secciones (
  id       SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug     VARCHAR(30) NOT NULL UNIQUE,
  etiqueta VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. PRODUCTOS (catálogo global)
--    FK → grupos, unidades
-- ============================================================
CREATE TABLE IF NOT EXISTS productos (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo    VARCHAR(10)       NOT NULL UNIQUE,
  nombre    VARCHAR(200)      NOT NULL,
  id_unidad SMALLINT UNSIGNED NOT NULL,
  id_grupo  SMALLINT UNSIGNED NOT NULL,
  created_at DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_prod_unidad FOREIGN KEY (id_unidad) REFERENCES unidades (id),
  CONSTRAINT fk_prod_grupo  FOREIGN KEY (id_grupo)  REFERENCES grupos   (id),
  INDEX idx_prod_nombre (nombre),
  INDEX idx_prod_grupo  (id_grupo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. PRODUCTO_CLIENTE (relación N:M + caché de stock)
--    FK → clientes, productos
-- ============================================================
CREATE TABLE IF NOT EXISTS producto_cliente (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_cliente   INT UNSIGNED   NOT NULL,
  id_producto  INT UNSIGNED   NOT NULL,
  stock_min    DECIMAL(12,2)  NOT NULL DEFAULT 0,
  stock_actual DECIMAL(12,2)  NOT NULL DEFAULT 0,
  habilitado_at DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pc_cliente  FOREIGN KEY (id_cliente)  REFERENCES clientes  (id),
  CONSTRAINT fk_pc_producto FOREIGN KEY (id_producto) REFERENCES productos (id),
  UNIQUE KEY uq_pc (id_cliente, id_producto),
  INDEX idx_pc_cliente  (id_cliente),
  INDEX idx_pc_producto (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. PERMISOS POR ROL
--    FK → roles, secciones
-- ============================================================
CREATE TABLE IF NOT EXISTS permisos_rol (
  id_rol     TINYINT UNSIGNED  NOT NULL,
  id_seccion SMALLINT UNSIGNED NOT NULL,
  permitido  TINYINT(1)        NOT NULL DEFAULT 0,
  PRIMARY KEY (id_rol, id_seccion),
  CONSTRAINT fk_pr_rol     FOREIGN KEY (id_rol)     REFERENCES roles    (id),
  CONSTRAINT fk_pr_seccion FOREIGN KEY (id_seccion) REFERENCES secciones (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. USUARIOS
--     FK → roles, clientes
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario       VARCHAR(60)      NOT NULL UNIQUE,
  password_hash VARCHAR(255)     NOT NULL,
  id_rol        TINYINT UNSIGNED NOT NULL,
  id_cliente    INT UNSIGNED     NULL DEFAULT NULL,
  estado        ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  created_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_usr_rol     FOREIGN KEY (id_rol)     REFERENCES roles    (id),
  CONSTRAINT fk_usr_cliente FOREIGN KEY (id_cliente) REFERENCES clientes (id),
  INDEX idx_usr_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. MOVIMIENTOS
--     FK → producto_cliente, usuarios
--     (ambas tablas ya existen en este punto)
-- ============================================================
CREATE TABLE IF NOT EXISTS movimientos (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pc            INT UNSIGNED  NOT NULL,
  fecha_movimiento DATE          NOT NULL,
  tipo             ENUM('INGRESO','SALIDA','AJUSTE_POSITIVO','AJUSTE_NEGATIVO','AJUSTE') NOT NULL,
  cantidad         DECIMAL(12,2) NOT NULL,
  stock_resultante DECIMAL(12,2) NOT NULL,
  observaciones    VARCHAR(500)  NULL,
  id_usuario       INT UNSIGNED  NOT NULL,
  created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mov_pc      FOREIGN KEY (id_pc)      REFERENCES producto_cliente (id),
  CONSTRAINT fk_mov_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios         (id),
  INDEX idx_mov_pc    (id_pc),
  INDEX idx_mov_fecha (fecha_movimiento),
  INDEX idx_mov_tipo  (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. PERMISOS INDIVIDUALES (overrides por usuario)
--     FK → usuarios, secciones
-- ============================================================
CREATE TABLE IF NOT EXISTS permisos_usuario (
  id_usuario INT UNSIGNED      NOT NULL,
  id_seccion SMALLINT UNSIGNED NOT NULL,
  permitido  TINYINT(1)        NOT NULL DEFAULT 0,
  PRIMARY KEY (id_usuario, id_seccion),
  CONSTRAINT fk_pu_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios  (id) ON DELETE CASCADE,
  CONSTRAINT fk_pu_seccion FOREIGN KEY (id_seccion) REFERENCES secciones (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. SESIONES (tokens de login)
--     FK → usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS sesiones (
  token      VARCHAR(128) NOT NULL PRIMARY KEY,
  id_usuario INT UNSIGNED NOT NULL,
  expires_at DATETIME     NOT NULL,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ses_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios (id) ON DELETE CASCADE,
  INDEX idx_ses_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATOS INICIALES (semillas)
-- ============================================================

-- Roles del sistema
INSERT IGNORE INTO roles (nombre) VALUES
  ('ADMINISTRADOR'),
  ('ALMACENERO'),
  ('CLIENTE');

-- Grupos de producto con sus prefijos de código
INSERT IGNORE INTO grupos (nombre, prefijo) VALUES
  ('Agroquímico',          'AGR'),
  ('Combustible',          'GAS'),
  ('Computo',              'COM'),
  ('Economato',            'ECO'),
  ('EPP',                  'EPP'),
  ('Ferretería',           'FER'),
  ('Limpieza',             'UTL'),
  ('Material de embalaje', 'EMB'),
  ('Material de empaque',  'EMP'),
  ('Repuestos',            'REP'),
  ('Útiles de oficina',    'UTO');

-- Correlativos (contador de código por prefijo)
INSERT IGNORE INTO correlativos (prefijo, ultimo_num)
  SELECT prefijo, 0 FROM grupos;

-- Unidades de medida
INSERT IGNORE INTO unidades (nombre) VALUES
  ('Bolsa'),
  ('Caja'),
  ('Galon'),
  ('Kilogramo'),
  ('Litro'),
  ('Millar'),
  ('Paquete'),
  ('Par'),
  ('Rollo'),
  ('Unidades');

-- Secciones / módulos del sistema
INSERT IGNORE INTO secciones (slug, etiqueta) VALUES
  ('dashboard',      'Dashboard'),
  ('clientes',       'Clientes'),
  ('productos',      'Productos'),
  ('movimientos',    'Movimientos'),
  ('inventario',     'Inventario'),
  ('reportes',       'Reportes'),
  ('buscar',         'Buscar'),
  ('configuracion',  'Configuración'),
  ('usuarios',       'Usuarios');

-- Permisos ADMINISTRADOR: acceso total
INSERT IGNORE INTO permisos_rol (id_rol, id_seccion, permitido)
  SELECT 1, id, 1 FROM secciones;

-- Permisos ALMACENERO: sin clientes, configuración ni usuarios
INSERT IGNORE INTO permisos_rol (id_rol, id_seccion, permitido)
  SELECT 2, id,
    CASE WHEN slug IN ('clientes','configuracion','usuarios') THEN 0 ELSE 1 END
  FROM secciones;

-- Permisos CLIENTE: solo lectura de su inventario
INSERT IGNORE INTO permisos_rol (id_rol, id_seccion, permitido)
  SELECT 3, id,
    CASE WHEN slug IN ('dashboard','inventario','reportes','buscar') THEN 1 ELSE 0 END
  FROM secciones;

-- Usuario administrador inicial
-- Contraseña: admin123  (hash bcrypt cost 10)
-- Ejecuta database/seed_admin.php para regenerar el hash de forma segura
INSERT IGNORE INTO usuarios (usuario, password_hash, id_rol, estado)
  VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1,
    'ACTIVO'
  );
