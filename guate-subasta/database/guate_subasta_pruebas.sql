-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-10-2026 a las 05:19:19
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
-- Base de datos: `guate_subasta_pruebas`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `cerrar_subasta` (IN `p_subasta` INT)   BEGIN
 DECLARE v_user INT; DECLARE v_monto DECIMAL(12,2);
 START TRANSACTION;
 SELECT usuario_id,monto INTO v_user,v_monto FROM pujas WHERE subasta_id=p_subasta AND cancelada=0 ORDER BY monto DESC,fecha ASC LIMIT 1 FOR UPDATE;
 UPDATE subastas SET estado='finalizada' WHERE id=p_subasta AND fecha_fin<=NOW() AND estado IN('activa','programada');
 IF v_user IS NOT NULL THEN
   UPDATE articulos a JOIN subastas s ON s.articulo_id=a.id SET a.estado='adjudicado' WHERE s.id=p_subasta;
   INSERT IGNORE INTO ordenes_pago(subasta_id,usuario_id,monto,comision) VALUES(p_subasta,v_user,v_monto,v_monto*0.10);
 END IF;
 INSERT INTO auditoria(usuario_id,evento,detalle) VALUES(NULL,'CIERRE_SUBASTA',CONCAT('Subasta ',p_subasta,' procesada'));
 COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulos`
--

CREATE TABLE `articulos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `categoria` varchar(80) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `estado_fisico` varchar(50) DEFAULT NULL,
  `precio_base` decimal(12,2) NOT NULL CHECK (`precio_base` >= 0),
  `certificado` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `modelo` varchar(150) DEFAULT NULL,
  `estado` enum('en_revision','validado','adjudicado') DEFAULT 'validado',
  `creado_por` int(11) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `archivado` tinyint(1) NOT NULL DEFAULT 0,
  `ubicacion_nombre` varchar(200) DEFAULT NULL,
  `ubicacion_lat` decimal(10,7) DEFAULT NULL,
  `ubicacion_lng` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `articulos`
--

INSERT INTO `articulos` (`id`, `nombre`, `categoria`, `descripcion`, `estado_fisico`, `precio_base`, `certificado`, `imagen`, `modelo`, `estado`, `creado_por`, `creado_en`, `archivado`, `ubicacion_nombre`, `ubicacion_lat`, `ubicacion_lng`) VALUES
(1, 'Reloj de colección', 'Relojes', 'Reloj de lujo con certificado de autenticidad.', 'Excelente', 50000.00, 'CERT-RELOJ-001', NULL, NULL, 'validado', 2, '2026-10-25 23:38:54', 0, NULL, NULL, NULL),
(2, 'Cuadro colonial', 'Arte', 'Pieza artística para subasta privada.', 'Bueno', 10000.00, 'CERT-ARTE-001', NULL, NULL, 'validado', 2, '2026-10-25 23:38:54', 0, NULL, NULL, NULL),
(4, 'Mazda CX5', 'Vehículos', 'carro en perfectas condiciones', 'Bueno', 15000.00, NULL, '[\"art_6ade95bf107bb0.81831673.jpg\"]', '2024', 'validado', 5, '2026-10-25 23:50:23', 0, NULL, 7.0000000, 180.0000000),
(5, 'djdkjakjfk', 'Accesorios', 'afafadfas', 'Excelente', 54545.00, NULL, '[\"art_6adeddea6cbb46.96171852.jpeg\",\"art_6adeddea6cea26.42512772.jpg\"]', '2010', 'validado', 6, '2026-10-26 04:58:18', 0, 'ds', 10.0000000, 102.0000000),
(6, 'pagina web', 'Tecnología', 'Pagina web en desarrollo', 'Bueno', 58000.00, NULL, '[\"art_6adee372119139.56995880.png\",\"art_6adee37211b3d3.97768767.png\"]', '2025', 'validado', 6, '2026-10-26 05:21:54', 0, 'ds', 14.6243584, -90.8460032),
(7, 'alejandro', 'Accesorios', '484848', 'Bueno', 85000.00, NULL, '[\"art_6adee55ae42398.04465666.png\",\"art_6adee55ae43b32.61436696.png\",\"art_6adee55ae44b65.63345294.png\",\"art_6adee55ae45bc3.35118536.png\"]', '2024', 'adjudicado', 6, '2026-10-26 05:30:02', 0, 'San Andres Iztapa', 14.6182184, -90.8436762),
(8, 'Nissan Frontier', 'Antigüedades', 'ewttqrqwerq', 'Excelente', 50.00, NULL, '[\"art_6adf709bd5a207.11257680.jpeg\",\"art_6adf709bd5eb79.47874120.jpg\"]', '2024', 'validado', 6, '2026-10-26 15:24:11', 0, 'chimaltenango', 14.6610787, -90.8212304);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `evento` varchar(80) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `evento`, `detalle`, `fecha`) VALUES
(1, 1, 'LOGIN', 'Inicio de sesión', '2026-10-25 23:43:02'),
(2, 1, 'ELIMINAR_ARTICULO', 'Artículo eliminado: Moneda de oro 1921', '2026-10-25 23:43:31'),
(3, 5, 'LOGIN', 'Inicio de sesión', '2026-10-25 23:44:39'),
(4, 5, 'PUJA', 'Subasta #1 (Reloj de colección) - Monto: Q 52,000.00', '2026-10-25 23:45:45'),
(5, 5, 'CANCELAR_PUJA', 'Puja #1 cancelada en subasta #1; nuevo monto Q 50000', '2026-10-25 23:45:58'),
(6, 5, 'LOGIN', 'Inicio de sesión', '2026-10-25 23:48:28'),
(7, 5, 'CREAR_ARTICULO', 'Artículo: Mazda CX5 | Categoría: Vehículos | Base: Q 15,000.00', '2026-10-25 23:50:23'),
(8, 5, 'PROGRAMAR_SUBASTA', 'Artículo ID #4 | Inicio: 2026-10-25 17:00:00 ert{} Fin: 2026-11-01 19:00:00 | Incremento: Q 500.00', '2026-10-25 23:50:42'),
(9, 6, 'LOGIN', 'Inicio de sesión', '2026-10-25 23:52:19'),
(10, 6, 'PUJA', 'Subasta #3 (Mazda CX5) - Monto: Q 15,500.00', '2026-10-25 23:52:28'),
(11, 6, 'PUJA', 'Subasta #3 (Mazda CX5) - Monto: Q 16,000.00', '2026-10-25 23:52:31'),
(12, 5, 'LOGIN', 'Inicio de sesión', '2026-10-25 23:52:42'),
(13, 1, 'LOGIN', 'Inicio de sesión', '2026-10-26 04:37:21'),
(14, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 04:48:30'),
(15, 5, 'PUJA', 'Subasta #1 (Reloj de colección) - Monto: Q 52,000.00', '2026-10-26 04:51:21'),
(16, 5, 'PUJA', 'Subasta #1 (Reloj de colección) - Monto: Q 54,000.00', '2026-10-26 04:51:24'),
(17, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 04:51:36'),
(18, 6, 'PUJA', 'Subasta #1 (Reloj de colección) - Monto: Q 56,000.00', '2026-10-26 04:51:53'),
(19, 6, 'CREAR_ARTICULO', 'Artículo: djdkjakjfk | Categoría: Accesorios | Base: Q 54,545.00', '2026-10-26 04:58:18'),
(20, 6, 'PROGRAMAR_SUBASTA', 'Artículo ID #5 | Inicio: 2026-10-25 22:00:00 ert{} Fin: 2026-11-01 00:00:00 | Incremento: Q 500.00', '2026-10-26 04:58:32'),
(21, 6, 'CREAR_ARTICULO', 'Artículo: pagina web | Categoría: Tecnología | Base: Q 58,000.00', '2026-10-26 05:21:54'),
(22, 6, 'PROGRAMAR_SUBASTA', 'Artículo ID #6 | Inicio: 2026-10-25 23:00:00 ert{} Fin: 2026-11-01 01:00:00 | Incremento: Q 500.00', '2026-10-26 05:22:16'),
(23, 6, 'CREAR_ARTICULO', 'Artículo: alejandro | Categoría: Accesorios | Base: Q 85,000.00', '2026-10-26 05:30:02'),
(24, 6, 'PROGRAMAR_SUBASTA', 'Artículo ID #7 | Inicio: 2026-10-25 23:00:00 ert{} Fin: 2026-10-25 23:35:00 | Incremento: Q 500.00', '2026-10-26 05:30:41'),
(25, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:31:01'),
(26, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:31:18'),
(27, 5, 'PUJA', 'Subasta #6 (alejandro) - Monto: Q 85,500.00', '2026-10-26 05:31:23'),
(28, 5, 'COMPRA_TOKENS', 'Subasta #6, tokens: 85500', '2026-10-26 05:35:08'),
(29, 1, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:36:51'),
(30, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:38:26'),
(31, 5, 'PUJA', 'Subasta #4 (djdkjakjfk) - Monto: Q 55,045.00', '2026-10-26 05:40:30'),
(32, 5, 'PUJA', 'Subasta #4 (djdkjakjfk) - Monto: Q 55,545.00', '2026-10-26 05:40:34'),
(33, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:44:48'),
(34, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:45:11'),
(35, 1, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:46:34'),
(36, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:51:55'),
(37, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:55:55'),
(38, 5, 'REVERSO_TOKENS_DEMO', 'Subasta #6: compra anterior de prueba revertida; 85500 tokens reintegrados por cambio a tarjeta ficticia', '2026-10-26 05:56:23'),
(39, 5, 'PAGO_TARJETA_DEMO', 'Subasta #6 | Referencia TARJETA-DEMO-F7C1AFC705 | Q 85,500.00 | SIN COBRO REAL', '2026-10-26 05:56:23'),
(40, 1, 'LOGIN', 'Inicio de sesión', '2026-10-26 05:59:15'),
(41, 1, 'LOGIN', 'Inicio de sesión', '2026-10-26 14:12:04'),
(42, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 14:16:54'),
(43, 6, 'PUJA', 'Subasta #1 (Reloj de colección) - Monto: Q 58,000.00', '2026-10-26 14:17:08'),
(44, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 15:12:47'),
(45, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 15:13:07'),
(46, 1, 'LOGIN', 'Inicio de sesión', '2026-10-26 15:20:56'),
(47, 6, 'LOGIN', 'Inicio de sesión', '2026-10-26 15:23:34'),
(48, 6, 'CREAR_ARTICULO', 'Artículo: Nissan Frontier | Categoría: Antigüedades | Base: Q 50.00', '2026-10-26 15:24:11'),
(49, 6, 'PROGRAMAR_SUBASTA_30MIN', 'Subasta #7 de artículo #7 - 2026-10-26 09:44 a 10:14', '2026-10-26 15:30:03'),
(50, 5, 'LOGIN', 'Inicio de sesión', '2026-10-26 15:44:40'),
(51, 5, 'PUJA', 'Subasta #7 (alejandro) - Monto: Q 85,500.00', '2026-10-26 15:44:59'),
(52, 1, 'LOGIN', 'Inicio de sesión', '2026-10-27 02:35:48'),
(53, 6, 'LOGIN', 'Inicio de sesión', '2026-10-27 03:44:02'),
(54, 6, 'PROGRAMAR_SUBASTA', 'Subasta #8 de artículo #8 - 2026-10-26 22:09 a 22:14 - Duración: 5 minutos', '2026-10-27 03:54:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avisos_subastas`
--

CREATE TABLE `avisos_subastas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `creado_por` int(11) NOT NULL,
  `tipo` enum('programada','reprogramada','cierre_modificado') NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `mensaje` varchar(500) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `avisos_subastas`
--

INSERT INTO `avisos_subastas` (`id`, `subasta_id`, `creado_por`, `tipo`, `titulo`, `mensaje`, `fecha_inicio`, `fecha_fin`, `creado_en`) VALUES
(1, 7, 6, 'programada', 'Nueva subasta programada', 'Se programó «alejandro» para el 26/10/2026 09:44. Duración: 30 minutos.', '2026-10-26 09:44:00', '2026-10-26 10:14:00', '2026-10-26 15:30:03'),
(2, 8, 6, 'programada', 'Nueva subasta programada', 'Se programó «Nissan Frontier» para el 26/10/2026 22:09. Duración: 5 minuto(s).', '2026-10-26 22:09:00', '2026-10-26 22:14:00', '2026-10-27 03:54:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avisos_subastas_correos`
--

CREATE TABLE `avisos_subastas_correos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `aviso_id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `correo_destino` varchar(150) NOT NULL,
  `estado` enum('pendiente','procesando','enviada','error') NOT NULL DEFAULT 'pendiente',
  `intentos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_error` varchar(255) DEFAULT NULL,
  `enviado_en` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `avisos_subastas_correos`
--

INSERT INTO `avisos_subastas_correos` (`id`, `aviso_id`, `usuario_id`, `correo_destino`, `estado`, `intentos`, `ultimo_error`, `enviado_en`, `creado_en`, `actualizado_en`) VALUES
(1, 2, 5, 'gab@gmail.com', 'pendiente', 0, NULL, NULL, '2026-10-27 03:54:33', '2026-10-27 03:54:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `avisos_subastas_leidos`
--

CREATE TABLE `avisos_subastas_leidos` (
  `usuario_id` int(11) NOT NULL,
  `aviso_id` bigint(20) UNSIGNED NOT NULL,
  `leido_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `billeteras_tokens`
--

CREATE TABLE `billeteras_tokens` (
  `usuario_id` int(11) NOT NULL,
  `saldo` bigint(20) NOT NULL DEFAULT 100000
) ;

--
-- Volcado de datos para la tabla `billeteras_tokens`
--

INSERT INTO `billeteras_tokens` (`usuario_id`, `saldo`) VALUES
(1, 100000),
(2, 100000),
(3, 100000),
(4, 100000),
(5, 100000),
(6, 100000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `compras_tokens`
--

CREATE TABLE `compras_tokens` (
  `id` bigint(20) NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tokens_gastados` bigint(20) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones_ganador`
--

CREATE TABLE `notificaciones_ganador` (
  `id` bigint(20) NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `correo_destino` varchar(150) NOT NULL,
  `estado` enum('pendiente','procesando','enviada','error') NOT NULL DEFAULT 'pendiente',
  `intentos` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_error` varchar(255) DEFAULT NULL,
  `enviado_en` datetime DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ordenes_pago`
--

CREATE TABLE `ordenes_pago` (
  `id` int(11) NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `comision` decimal(12,2) NOT NULL,
  `estado` enum('pendiente','pagado') DEFAULT 'pendiente',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `referencia_demo` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `ordenes_pago`
--

INSERT INTO `ordenes_pago` (`id`, `subasta_id`, `usuario_id`, `monto`, `comision`, `estado`, `creado_en`, `referencia_demo`) VALUES
(1, 6, 5, 85500.00, 0.00, 'pagado', '2026-10-26 05:56:23', 'TARJETA-DEMO-F7C1AFC705');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `orden_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo` varchar(50) DEFAULT NULL,
  `estado` varchar(30) DEFAULT 'pendiente',
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `orden_id`, `monto`, `metodo`, `estado`, `fecha`) VALUES
(1, 1, 85500.00, 'TARJETA_DEMO', 'aprobado', '2026-10-26 05:56:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preferencias_avisos_subastas`
--

CREATE TABLE `preferencias_avisos_subastas` (
  `usuario_id` int(11) NOT NULL,
  `correo_habilitado` tinyint(1) NOT NULL DEFAULT 0,
  `actualizado_en` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preferencias_avisos_subastas`
--

INSERT INTO `preferencias_avisos_subastas` (`usuario_id`, `correo_habilitado`, `actualizado_en`) VALUES
(5, 1, '2026-10-26 15:44:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pujas`
--

CREATE TABLE `pujas` (
  `id` bigint(20) NOT NULL,
  `subasta_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `fecha` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `cancelada` tinyint(1) NOT NULL DEFAULT 0,
  `cancelada_en` datetime DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pujas`
--

INSERT INTO `pujas` (`id`, `subasta_id`, `usuario_id`, `monto`, `fecha`, `cancelada`, `cancelada_en`, `estado`) VALUES
(1, 1, 5, 52000.00, '2026-10-25 23:45:45.484755', 1, '2026-10-25 17:45:58', 'activa'),
(2, 3, 6, 15500.00, '2026-10-25 23:52:28.078994', 0, NULL, 'activa'),
(3, 3, 6, 16000.00, '2026-10-25 23:52:31.753491', 0, NULL, 'activa'),
(4, 1, 5, 52000.00, '2026-10-26 04:51:21.019510', 0, NULL, 'activa'),
(5, 1, 5, 54000.00, '2026-10-26 04:51:24.513963', 0, NULL, 'activa'),
(6, 1, 6, 56000.00, '2026-10-26 04:51:53.858483', 0, NULL, 'activa'),
(7, 6, 5, 85500.00, '2026-10-26 05:31:23.420411', 0, NULL, 'activa'),
(8, 4, 5, 55045.00, '2026-10-26 05:40:30.470269', 0, NULL, 'activa'),
(9, 4, 5, 55545.00, '2026-10-26 05:40:34.012402', 0, NULL, 'activa'),
(10, 1, 6, 58000.00, '2026-10-26 14:17:08.677324', 0, NULL, 'activa'),
(11, 7, 5, 85500.00, '2026-10-26 15:44:59.239197', 0, NULL, 'activa');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`) VALUES
(1, 'Administrador'),
(4, 'Auditor'),
(3, 'Cliente'),
(2, 'Subastador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subastas`
--

CREATE TABLE `subastas` (
  `id` int(11) NOT NULL,
  `articulo_id` int(11) NOT NULL,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `incremento_minimo` decimal(12,2) NOT NULL,
  `monto_actual` decimal(12,2) NOT NULL,
  `estado` enum('programada','activa','cerrada','finalizada','cancelada') DEFAULT 'programada'
) ;

--
-- Volcado de datos para la tabla `subastas`
--

INSERT INTO `subastas` (`id`, `articulo_id`, `fecha_inicio`, `fecha_fin`, `incremento_minimo`, `monto_actual`, `estado`) VALUES
(1, 1, '2026-10-25 17:38:54', '2026-11-01 17:38:54', 2000.00, 58000.00, 'activa'),
(2, 2, '2026-10-26 17:38:54', '2026-11-02 17:38:54', 500.00, 10000.00, 'activa'),
(3, 4, '2026-10-25 17:00:00', '2026-11-01 19:00:00', 500.00, 16000.00, 'activa'),
(4, 5, '2026-10-25 22:00:00', '2026-11-01 00:00:00', 500.00, 55545.00, 'activa'),
(5, 6, '2026-10-25 23:00:00', '2026-11-01 01:00:00', 500.00, 58000.00, 'activa'),
(6, 7, '2026-10-25 23:00:00', '2026-10-25 23:35:00', 500.00, 85500.00, 'finalizada'),
(7, 7, '2026-10-26 09:44:00', '2026-10-26 10:14:00', 500.00, 85500.00, 'cerrada'),
(8, 8, '2026-10-26 22:09:00', '2026-10-26 22:14:00', 10.00, 50.00, 'programada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol_id` int(11) NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `rol_id`, `activo`, `creado_en`) VALUES
(1, 'Administrador General', 'admin@guate.test', '$2y$12$BCMTS4I0KUUWAuns8yZXq.dEURUzhncC3yCa0F6bX/ODR4h2FGyWa', 1, 1, '2026-10-25 23:38:54'),
(2, 'Subastador Demo', 'subastador@guate.test', '$2y$12$BCMTS4I0KUUWAuns8yZXq.dEURUzhncC3yCa0F6bX/ODR4h2FGyWa', 2, 1, '2026-10-25 23:38:54'),
(3, 'Cliente Demo', 'cliente@guate.test', '$2y$12$BCMTS4I0KUUWAuns8yZXq.dEURUzhncC3yCa0F6bX/ODR4h2FGyWa', 3, 1, '2026-10-25 23:38:54'),
(4, 'Auditor Demo', 'auditor@guate.test', '$2y$12$BCMTS4I0KUUWAuns8yZXq.dEURUzhncC3yCa0F6bX/ODR4h2FGyWa', 4, 1, '2026-10-25 23:38:54'),
(5, 'Gabriel sal', 'gab@gmail.com', '$2y$10$RMkiV5l7MvrG.HmFnY0kQ.bSRiz.MnrcaEEGrCjECLOe.uY2Em4za', 3, 1, '2026-10-25 23:44:31'),
(6, 'Ricardo Osorio', 'admin@admin.com', '$2y$10$hYyjbfFYnm0GoyHrPyJJjebOwr6XauYvdaf9pw7RILAyjkPe3qPVC', 3, 1, '2026-10-25 23:52:13');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `creado_por` (`creado_por`),
  ADD KEY `idx_articulos_archivado` (`archivado`);

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `avisos_subastas`
--
ALTER TABLE `avisos_subastas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ix_aviso_reciente` (`creado_en`,`id`),
  ADD KEY `av_subasta_fk` (`subasta_id`),
  ADD KEY `av_creador_fk` (`creado_por`);

--
-- Indices de la tabla `avisos_subastas_correos`
--
ALTER TABLE `avisos_subastas_correos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_aviso_usuario` (`aviso_id`,`usuario_id`),
  ADD KEY `ix_envio_pendiente` (`estado`,`intentos`),
  ADD KEY `ac_usuario_fk` (`usuario_id`);

--
-- Indices de la tabla `avisos_subastas_leidos`
--
ALTER TABLE `avisos_subastas_leidos`
  ADD PRIMARY KEY (`usuario_id`,`aviso_id`),
  ADD KEY `al_aviso_fk` (`aviso_id`);

--
-- Indices de la tabla `billeteras_tokens`
--
ALTER TABLE `billeteras_tokens`
  ADD PRIMARY KEY (`usuario_id`);

--
-- Indices de la tabla `compras_tokens`
--
ALTER TABLE `compras_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subasta_id` (`subasta_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `notificaciones_ganador`
--
ALTER TABLE `notificaciones_ganador`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subasta_id` (`subasta_id`),
  ADD KEY `ng_usuario_fk` (`usuario_id`);

--
-- Indices de la tabla `ordenes_pago`
--
ALTER TABLE `ordenes_pago`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subasta_id` (`subasta_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `orden_id` (`orden_id`);

--
-- Indices de la tabla `preferencias_avisos_subastas`
--
ALTER TABLE `preferencias_avisos_subastas`
  ADD PRIMARY KEY (`usuario_id`);

--
-- Indices de la tabla `pujas`
--
ALTER TABLE `pujas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_puja_subasta_monto` (`subasta_id`,`monto`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `subastas`
--
ALTER TABLE `subastas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `articulo_id` (`articulo_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `rol_id` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulos`
--
ALTER TABLE `articulos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `avisos_subastas`
--
ALTER TABLE `avisos_subastas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `avisos_subastas_correos`
--
ALTER TABLE `avisos_subastas_correos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `compras_tokens`
--
ALTER TABLE `compras_tokens`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `notificaciones_ganador`
--
ALTER TABLE `notificaciones_ganador`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ordenes_pago`
--
ALTER TABLE `ordenes_pago`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pujas`
--
ALTER TABLE `pujas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `subastas`
--
ALTER TABLE `subastas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulos`
--
ALTER TABLE `articulos`
  ADD CONSTRAINT `articulos_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `auditoria_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `avisos_subastas`
--
ALTER TABLE `avisos_subastas`
  ADD CONSTRAINT `av_creador_fk` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `av_subasta_fk` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `avisos_subastas_correos`
--
ALTER TABLE `avisos_subastas_correos`
  ADD CONSTRAINT `ac_aviso_fk` FOREIGN KEY (`aviso_id`) REFERENCES `avisos_subastas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ac_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `avisos_subastas_leidos`
--
ALTER TABLE `avisos_subastas_leidos`
  ADD CONSTRAINT `al_aviso_fk` FOREIGN KEY (`aviso_id`) REFERENCES `avisos_subastas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `al_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `billeteras_tokens`
--
ALTER TABLE `billeteras_tokens`
  ADD CONSTRAINT `billeteras_tokens_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `compras_tokens`
--
ALTER TABLE `compras_tokens`
  ADD CONSTRAINT `compras_tokens_ibfk_1` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`),
  ADD CONSTRAINT `compras_tokens_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `notificaciones_ganador`
--
ALTER TABLE `notificaciones_ganador`
  ADD CONSTRAINT `ng_subasta_fk` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`),
  ADD CONSTRAINT `ng_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `ordenes_pago`
--
ALTER TABLE `ordenes_pago`
  ADD CONSTRAINT `ordenes_pago_ibfk_1` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`),
  ADD CONSTRAINT `ordenes_pago_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`orden_id`) REFERENCES `ordenes_pago` (`id`);

--
-- Filtros para la tabla `preferencias_avisos_subastas`
--
ALTER TABLE `preferencias_avisos_subastas`
  ADD CONSTRAINT `pr_usuario_fk` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pujas`
--
ALTER TABLE `pujas`
  ADD CONSTRAINT `pujas_ibfk_1` FOREIGN KEY (`subasta_id`) REFERENCES `subastas` (`id`),
  ADD CONSTRAINT `pujas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `subastas`
--
ALTER TABLE `subastas`
  ADD CONSTRAINT `subastas_ibfk_1` FOREIGN KEY (`articulo_id`) REFERENCES `articulos` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
