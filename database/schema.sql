-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-08-2026 a las 15:26:01
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `deybis_system`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(20) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientes`
--

INSERT INTO `clientes` (`id`, `codigo`, `nombre`, `estado`, `created_at`, `updated_at`) VALUES
(13, 'ASSWQS', 'qori', 'ACTIVO', '2026-08-22 15:19:51', '2026-08-22 15:19:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `correlativos`
--

CREATE TABLE `correlativos` (
  `prefijo` varchar(5) NOT NULL,
  `ultimo_num` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `correlativos`
--

INSERT INTO `correlativos` (`prefijo`, `ultimo_num`, `updated_at`) VALUES
('AGR', 1, '2026-08-22 15:18:06'),
('COM', 0, '2026-08-20 17:18:34'),
('ECO', 0, '2026-08-20 17:18:34'),
('EMB', 0, '2026-08-20 17:18:34'),
('EMP', 0, '2026-08-20 17:18:34'),
('EPP', 0, '2026-08-20 17:18:34'),
('FER', 0, '2026-08-20 17:18:34'),
('GAS', 0, '2026-08-20 17:18:34'),
('REP', 0, '2026-08-20 17:18:34'),
('REQ', 0, '2026-08-22 14:57:14'),
('UTL', 0, '2026-08-20 17:18:34'),
('UTO', 0, '2026-08-20 17:18:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `grupos`
--

CREATE TABLE `grupos` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `prefijo` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `grupos`
--

INSERT INTO `grupos` (`id`, `nombre`, `prefijo`) VALUES
(1, 'Agroquímico', 'AGR'),
(2, 'Combustible', 'GAS'),
(3, 'Computo', 'COM'),
(4, 'Economato', 'ECO'),
(5, 'EPP', 'EPP'),
(6, 'Ferretería', 'FER'),
(7, 'Limpieza', 'UTL'),
(8, 'Material de embalaje', 'EMB'),
(9, 'Material de empaque', 'EMP'),
(10, 'Repuestos', 'REP'),
(11, 'Útiles de oficina', 'UTO');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimientos`
--

CREATE TABLE `movimientos` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_pc` int(10) UNSIGNED NOT NULL,
  `fecha_movimiento` date NOT NULL,
  `tipo` enum('INGRESO','SALIDA','AJUSTE_POSITIVO','AJUSTE_NEGATIVO','AJUSTE') NOT NULL,
  `cantidad` decimal(12,2) NOT NULL,
  `stock_resultante` decimal(12,2) NOT NULL,
  `observaciones` varchar(500) DEFAULT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_rol`
--

CREATE TABLE `permisos_rol` (
  `id_rol` tinyint(3) UNSIGNED NOT NULL,
  `id_seccion` smallint(5) UNSIGNED NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permisos_rol`
--

INSERT INTO `permisos_rol` (`id_rol`, `id_seccion`, `permitido`) VALUES
(1, 1, 1),
(1, 2, 1),
(1, 3, 1),
(1, 4, 1),
(1, 5, 1),
(1, 6, 1),
(1, 7, 1),
(1, 8, 1),
(1, 9, 1),
(1, 10, 1),
(1, 11, 1),
(2, 1, 1),
(2, 2, 0),
(2, 3, 1),
(2, 4, 1),
(2, 5, 1),
(2, 6, 1),
(2, 7, 1),
(2, 8, 0),
(2, 9, 0),
(2, 10, 1),
(2, 11, 1),
(3, 1, 1),
(3, 2, 0),
(3, 3, 0),
(3, 4, 0),
(3, 5, 1),
(3, 6, 1),
(3, 7, 1),
(3, 8, 0),
(3, 9, 0),
(3, 10, 1),
(3, 11, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permisos_usuario`
--

CREATE TABLE `permisos_usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `id_seccion` smallint(5) UNSIGNED NOT NULL,
  `permitido` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `id_unidad` smallint(5) UNSIGNED NOT NULL,
  `id_grupo` smallint(5) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `codigo`, `nombre`, `id_unidad`, `id_grupo`, `created_at`) VALUES
(12, 'AGR0001', 'sdsadsdsadsad', 10, 1, '2026-08-22 15:18:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_cliente`
--

CREATE TABLE `producto_cliente` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_cliente` int(10) UNSIGNED NOT NULL,
  `id_producto` int(10) UNSIGNED NOT NULL,
  `stock_min` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock_actual` decimal(12,2) NOT NULL DEFAULT 0.00,
  `habilitado_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_cliente`
--

INSERT INTO `producto_cliente` (`id`, `id_cliente`, `id_producto`, `stock_min`, `stock_actual`, `habilitado_at`) VALUES
(2, 13, 12, 5.00, 0.00, '2026-08-22 15:22:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requerimientos`
--

CREATE TABLE `requerimientos` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(12) NOT NULL,
  `id_cliente` int(10) UNSIGNED NOT NULL,
  `id_usuario_solicitante` int(10) UNSIGNED NOT NULL,
  `estado` enum('PENDIENTE','APROBADO','DESPACHADO','RECHAZADO','CERRADO') NOT NULL DEFAULT 'PENDIENTE',
  `observaciones` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `requerimiento_items`
--

CREATE TABLE `requerimiento_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_requerimiento` int(10) UNSIGNED NOT NULL,
  `id_pc` int(10) UNSIGNED NOT NULL,
  `cantidad_solicitada` decimal(12,2) NOT NULL,
  `cantidad_despachada` decimal(12,2) DEFAULT NULL,
  `observaciones` varchar(300) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'ADMINISTRADOR'),
(2, 'ALMACENERO'),
(3, 'CLIENTE');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `secciones`
--

CREATE TABLE `secciones` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `slug` varchar(30) NOT NULL,
  `etiqueta` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `secciones`
--

INSERT INTO `secciones` (`id`, `slug`, `etiqueta`) VALUES
(1, 'dashboard', 'Dashboard'),
(2, 'clientes', 'Clientes'),
(3, 'productos', 'Productos'),
(4, 'movimientos', 'Movimientos'),
(5, 'inventario', 'Inventario'),
(6, 'reportes', 'Reportes'),
(7, 'buscar', 'Buscar'),
(8, 'configuracion', 'Configuración'),
(9, 'usuarios', 'Usuarios'),
(10, 'requerimientos', 'Requerimientos'),
(11, 'kardex', 'Kardex');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones`
--

CREATE TABLE `sesiones` (
  `token` varchar(128) NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `unidades`
--

CREATE TABLE `unidades` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `nombre` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `unidades`
--

INSERT INTO `unidades` (`id`, `nombre`) VALUES
(1, 'Bolsa'),
(2, 'Caja'),
(3, 'Galon'),
(4, 'Kilogramo'),
(5, 'Litro'),
(6, 'Millar'),
(7, 'Paquete'),
(8, 'Par'),
(9, 'Rollo'),
(10, 'Unidades');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario` varchar(60) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `id_rol` tinyint(3) UNSIGNED NOT NULL,
  `id_cliente` int(10) UNSIGNED DEFAULT NULL,
  `estado` enum('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `usuario`, `password_hash`, `id_rol`, `id_cliente`, `estado`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$12$iNSujeM5937k9.l.HHfW2ez1baorQwyDPthndXkIKa5LmKrhP5H5y', 1, NULL, 'ACTIVO', '2026-08-15 12:05:54', '2026-08-15 18:20:43'),
(2, 'deybis', '$2y$12$6T0X7O9dcounAJPSN/cKCewD7ZsjIlkMQ2dAHAXnO1ynRxDtjZJeq', 1, NULL, 'ACTIVO', '2026-08-15 14:16:43', '2026-08-15 14:16:43'),
(4, 'qori', '$2y$12$BA4tOZ8M4.mP1Az6Z5aVqOx47MUeBw/CjXcCyDP4e08Z7SsfH1y5O', 3, 13, 'ACTIVO', '2026-08-22 15:20:07', '2026-08-22 15:20:07');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_cli_estado` (`estado`);

--
-- Indices de la tabla `correlativos`
--
ALTER TABLE `correlativos`
  ADD PRIMARY KEY (`prefijo`);

--
-- Indices de la tabla `grupos`
--
ALTER TABLE `grupos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD UNIQUE KEY `prefijo` (`prefijo`);

--
-- Indices de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mov_usuario` (`id_usuario`),
  ADD KEY `idx_mov_pc` (`id_pc`),
  ADD KEY `idx_mov_fecha` (`fecha_movimiento`),
  ADD KEY `idx_mov_tipo` (`tipo`);

--
-- Indices de la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD PRIMARY KEY (`id_rol`,`id_seccion`),
  ADD KEY `fk_pr_seccion` (`id_seccion`);

--
-- Indices de la tabla `permisos_usuario`
--
ALTER TABLE `permisos_usuario`
  ADD PRIMARY KEY (`id_usuario`,`id_seccion`),
  ADD KEY `fk_pu_seccion` (`id_seccion`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_prod_unidad` (`id_unidad`),
  ADD KEY `idx_prod_nombre` (`nombre`),
  ADD KEY `idx_prod_grupo` (`id_grupo`);

--
-- Indices de la tabla `producto_cliente`
--
ALTER TABLE `producto_cliente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pc` (`id_cliente`,`id_producto`),
  ADD KEY `idx_pc_cliente` (`id_cliente`),
  ADD KEY `idx_pc_producto` (`id_producto`);

--
-- Indices de la tabla `requerimientos`
--
ALTER TABLE `requerimientos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `fk_req_usuario` (`id_usuario_solicitante`),
  ADD KEY `idx_req_cliente` (`id_cliente`),
  ADD KEY `idx_req_estado` (`estado`);

--
-- Indices de la tabla `requerimiento_items`
--
ALTER TABLE `requerimiento_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ri_req` (`id_requerimiento`),
  ADD KEY `idx_ri_pc` (`id_pc`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `secciones`
--
ALTER TABLE `secciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indices de la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD PRIMARY KEY (`token`),
  ADD KEY `fk_ses_usuario` (`id_usuario`),
  ADD KEY `idx_ses_expires` (`expires_at`);

--
-- Indices de la tabla `unidades`
--
ALTER TABLE `unidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_usr_rol` (`id_rol`),
  ADD KEY `fk_usr_cliente` (`id_cliente`),
  ADD KEY `idx_usr_estado` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `grupos`
--
ALTER TABLE `grupos`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `movimientos`
--
ALTER TABLE `movimientos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `producto_cliente`
--
ALTER TABLE `producto_cliente`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `requerimientos`
--
ALTER TABLE `requerimientos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `requerimiento_items`
--
ALTER TABLE `requerimiento_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `secciones`
--
ALTER TABLE `secciones`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `unidades`
--
ALTER TABLE `unidades`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `movimientos`
--
ALTER TABLE `movimientos`
  ADD CONSTRAINT `fk_mov_pc` FOREIGN KEY (`id_pc`) REFERENCES `producto_cliente` (`id`),
  ADD CONSTRAINT `fk_mov_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `permisos_rol`
--
ALTER TABLE `permisos_rol`
  ADD CONSTRAINT `fk_pr_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_pr_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `secciones` (`id`);

--
-- Filtros para la tabla `permisos_usuario`
--
ALTER TABLE `permisos_usuario`
  ADD CONSTRAINT `fk_pu_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `secciones` (`id`),
  ADD CONSTRAINT `fk_pu_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_prod_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupos` (`id`),
  ADD CONSTRAINT `fk_prod_unidad` FOREIGN KEY (`id_unidad`) REFERENCES `unidades` (`id`);

--
-- Filtros para la tabla `producto_cliente`
--
ALTER TABLE `producto_cliente`
  ADD CONSTRAINT `fk_pc_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_pc_producto` FOREIGN KEY (`id_producto`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `requerimientos`
--
ALTER TABLE `requerimientos`
  ADD CONSTRAINT `fk_req_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_req_usuario` FOREIGN KEY (`id_usuario_solicitante`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `requerimiento_items`
--
ALTER TABLE `requerimiento_items`
  ADD CONSTRAINT `fk_ri_pc` FOREIGN KEY (`id_pc`) REFERENCES `producto_cliente` (`id`),
  ADD CONSTRAINT `fk_ri_req` FOREIGN KEY (`id_requerimiento`) REFERENCES `requerimientos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sesiones`
--
ALTER TABLE `sesiones`
  ADD CONSTRAINT `fk_ses_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usr_cliente` FOREIGN KEY (`id_cliente`) REFERENCES `clientes` (`id`),
  ADD CONSTRAINT `fk_usr_rol` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
