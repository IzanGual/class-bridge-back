-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-05-2025 a las 20:53:47
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
-- Base de datos: `classbridge`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `apartados`
--

CREATE TABLE `apartados` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `curso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `apartados`
--

INSERT INTO `apartados` (`id`, `nombre`, `curso_id`) VALUES
(8, 'apartado44b2intensive', 13),
(9, 'apartado55b2intensive', 13),
(12, 'aee', 14),
(13, 'meme', 14),
(14, 'b2megaintensivo', 13),
(15, 'aaaaaa', 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `aulas`
--

CREATE TABLE `aulas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `profesor_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `aulas`
--

INSERT INTO `aulas` (`id`, `nombre`, `profesor_id`) VALUES
(1, 'Matemáticas Avanzadas', 1),
(2, 'Historia Universal', 2),
(25, 'izan', 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calendario`
--

CREATE TABLE `calendario` (
  `id` int(11) NOT NULL,
  `profesor_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calendario`
--

INSERT INTO `calendario` (`id`, `profesor_id`, `alumno_id`, `curso_id`, `fecha`) VALUES
(1, 1, 3, 1, '2024-06-10 10:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `entrega_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `curso_id` int(11) NOT NULL,
  `apartado_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `entrega_id`, `nombre`, `curso_id`, `apartado_id`) VALUES
(13, NULL, 'quea12', 13, 8),
(14, NULL, 'quea123', 13, 8),
(15, NULL, 'quea1234', 13, 8),
(16, NULL, 'quea12345', 13, 8),
(18, NULL, 'cacadebaca', 13, 14),
(20, NULL, 'categoriaparaaeee', 14, 12),
(22, NULL, 'nuevacatparadaph', 12, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_verificacion`
--

CREATE TABLE `codigos_verificacion` (
  `id_usuario` int(11) NOT NULL,
  `codigo_verificacion` varchar(255) NOT NULL,
  `expiracion` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `aula_id` int(11) NOT NULL,
  `img_url` varchar(255) DEFAULT 'http://192.168.1.130/classbridgeapi/uploads/courses/000/banner.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `aula_id`, `img_url`) VALUES
(1, 'Álgebra y Cálculo', 1, 'http://192.168.1.130/classbridgeapi/uploads/courses/000/banner.png'),
(12, 'CursoDeDaph', 25, 'http://192.168.1.130/classbridgeapi/uploads/courses/12/banner.png'),
(13, 'B2 intesivo', 25, 'http://192.168.1.130/classbridgeapi/uploads/courses/13/banner.png'),
(14, 'Curso3', 25, 'http://192.168.1.130/classbridgeapi/uploads/courses/000/banner.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `documentos`
--

INSERT INTO `documentos` (`id`, `categoria_id`, `nombre`, `url`) VALUES
(4, 22, 'izang', 'http://192.168.1.130/classbridgeapi/uploads/courses/12/apartados/15/categorias/22/documentos/izang.pdf'),
(5, 22, 'izaguayy', 'http://192.168.1.130/classbridgeapi/uploads/courses/12/apartados/15/categorias/22/documentos/izaguayy.pdf'),
(6, 22, 'pngf', 'http://192.168.1.130/classbridgeapi/uploads/courses/12/apartados/15/categorias/22/documentos/pngf.png');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `entregas`
--

CREATE TABLE `entregas` (
  `id` int(11) NOT NULL,
  `tarea_id` int(11) NOT NULL,
  `alumno_id` int(11) NOT NULL,
  `comentario` text DEFAULT NULL,
  `nota` decimal(4,2) DEFAULT NULL CHECK (`nota` >= 1 and `nota` <= 10),
  `estado` enum('entregada','noentregada') DEFAULT 'noentregada',
  `fecha_entrega` date DEFAULT NULL,
  `archivo_url` varchar(255) DEFAULT NULL,
  `estado_correccion` enum('corregida','no_corregida') DEFAULT 'no_corregida'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `entregas`
--

INSERT INTO `entregas` (`id`, `tarea_id`, `alumno_id`, `comentario`, `nota`, `estado`, `fecha_entrega`, `archivo_url`, `estado_correccion`) VALUES
(1, 11, 11, NULL, NULL, 'entregada', '2025-05-09', '//AE', 'no_corregida'),
(2, 11, 8, NULL, NULL, 'noentregada', '2025-05-09', '//AE', 'no_corregida'),
(3, 11, 12, NULL, NULL, 'noentregada', '2025-05-09', '//AEe', 'no_corregida'),
(4, 11, 13, NULL, NULL, 'noentregada', '2025-05-09', '//AEeeeeee', 'no_corregida');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pagos`
--

CREATE TABLE `pagos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `estado` enum('completado','pendiente','fallido') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pagos`
--

INSERT INTO `pagos` (`id`, `usuario_id`, `monto`, `metodo_pago`, `estado`) VALUES
(1, 1, 49.99, 'Tarjeta de Crédito', 'completado'),
(2, 2, 49.99, 'PayPal', 'completado'),
(15, 7, 29.99, 'Tarjeta de credito', 'completado'),
(16, 7, 29.99, 'Tarjeta de credito', 'completado'),
(17, 7, 29.99, 'Tarjeta de credito', 'completado'),
(18, 7, 29.99, 'Tarjeta de credito', 'completado'),
(19, 7, 29.99, 'Tarjeta de credito', 'completado'),
(20, 7, 29.99, 'Tarjeta de credito', 'completado'),
(21, 7, 29.99, 'Tarjeta de credito', 'completado'),
(22, 7, 29.99, 'Tarjeta de credito', 'completado'),
(23, 7, 29.99, 'Tarjeta de credito', 'completado'),
(24, 7, 29.99, 'Tarjeta de credito', 'completado'),
(25, 7, 29.99, 'Tarjeta de credito', 'completado'),
(26, 7, 29.99, 'Tarjeta de credito', 'completado'),
(27, 7, 29.99, 'Tarjeta de credito', 'completado'),
(28, 7, 29.99, 'Tarjeta de credito', 'completado'),
(29, 7, 29.99, 'Tarjeta de credito', 'completado'),
(30, 7, 29.99, 'Tarjeta de credito', 'completado'),
(31, 7, 29.99, 'Tarjeta de credito', 'completado'),
(32, 7, 29.99, 'Tarjeta de credito', 'completado'),
(33, 7, 29.99, 'Tarjeta de credito', 'completado'),
(34, 7, 29.99, 'Tarjeta de credito', 'completado'),
(35, 7, 29.99, 'Tarjeta de credito', 'completado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planes_servicio`
--

CREATE TABLE `planes_servicio` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `beneficios` text NOT NULL,
  `precio` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `planes_servicio`
--

INSERT INTO `planes_servicio` (`id`, `nombre`, `descripcion`, `beneficios`, `precio`) VALUES
(1, 'Plan Básico', 'Plan de acceso limitado con funcionalidades básicas.', 'Acceso a funciones básicas, soporte técnico básico, sin funcionalidades avanzadas.', 9.99),
(2, 'Plan Premium', 'Plan con acceso completo a todas las funcionalidades avanzadas.', 'Acceso a todas las funciones, soporte técnico prioritario, funcionalidades avanzadas.', 29.99),
(3, 'Plan Empresarial', 'Plan pensado para empresas, con funciones adicionales y soporte dedicado.', 'Acceso completo, soporte personalizado, funcionalidades avanzadas para empresas, informes detallados.', 49.99);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `curso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tareas`
--

INSERT INTO `tareas` (`id`, `categoria_id`, `nombre`, `fecha_limite`, `curso_id`) VALUES
(11, 13, 'Entrega sexo', '2025-05-16', 13),
(12, 22, 'Tarea de izan', '2025-05-18', 12),
(13, 22, 'OPtra tarea de abimales en ingles', '2025-05-17', 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `tipo` enum('profesor','alumno','normal') NOT NULL,
  `estado_suscripcion` enum('activo','pendiente','cancelado') DEFAULT 'pendiente',
  `img_url` varchar(255) DEFAULT 'http://localhost/classbridgeapi/uploads/profiles/000/profile.png',
  `aulaId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `pass`, `tipo`, `estado_suscripcion`, `img_url`, `aulaId`) VALUES
(1, 'Juan Pérez', 'juanperez@example.com', 'e10adc3949ba59abbe56e057f20f883e', 'profesor', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', NULL),
(2, 'María López', 'marialopez@example.com', 'e10adc3949ba59abbe56e057f20f883e', 'profesor', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', NULL),
(3, 'Carlos García', 'c@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'profesor', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 1),
(4, 'Ana Martínez', 'a@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'alumno', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 25),
(7, 'izangual', 'iesvda.izamar@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'profesor', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 25),
(8, 'Bebe', 'b@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'alumno', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 25),
(11, 'Dedededo', 'd@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'alumno', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 25),
(12, 'caca', 'caca@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'alumno', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 25),
(13, 'meoa', 'meoa@gmail.com', '$2y$10$h0hPw4cwPJX9u9toJMsy4u6EkanLynWGJ/0SXbJacj9DqwuXhAQAy', 'alumno', 'activo', 'http://192.168.1.130/classbridgeapi/uploads/profiles/000/profile.png', 25);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios_cursos`
--

CREATE TABLE `usuarios_cursos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios_cursos`
--

INSERT INTO `usuarios_cursos` (`id`, `usuario_id`, `curso_id`) VALUES
(1, 3, 1),
(116, 4, 12),
(117, 8, 12),
(118, 11, 12),
(119, 12, 12),
(120, 13, 12),
(121, 4, 13),
(122, 8, 13),
(123, 11, 13),
(124, 12, 13),
(125, 13, 13);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `apartados`
--
ALTER TABLE `apartados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_apartados_curso` (`curso_id`);

--
-- Indices de la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`),
  ADD KEY `profesor_id` (`profesor_id`);

--
-- Indices de la tabla `calendario`
--
ALTER TABLE `calendario`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesor_id` (`profesor_id`),
  ADD KEY `alumno_id` (`alumno_id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categorias_curso` (`curso_id`),
  ADD KEY `fk_categoria_apartado` (`apartado_id`);

--
-- Indices de la tabla `codigos_verificacion`
--
ALTER TABLE `codigos_verificacion`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `codigo_verificacion_unique` (`codigo_verificacion`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `aula_id` (`aula_id`);

--
-- Indices de la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_documento_categoria` (`categoria_id`);

--
-- Indices de la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tarea_id` (`tarea_id`),
  ADD KEY `alumno_id` (`alumno_id`);

--
-- Indices de la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `planes_servicio`
--
ALTER TABLE `planes_servicio`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_entregas_curso` (`curso_id`),
  ADD KEY `fk_entrega_categoria` (`categoria_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_aula` (`aulaId`);

--
-- Indices de la tabla `usuarios_cursos`
--
ALTER TABLE `usuarios_cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `curso_id` (`curso_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `apartados`
--
ALTER TABLE `apartados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `aulas`
--
ALTER TABLE `aulas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `calendario`
--
ALTER TABLE `calendario`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `entregas`
--
ALTER TABLE `entregas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pagos`
--
ALTER TABLE `pagos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `planes_servicio`
--
ALTER TABLE `planes_servicio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `tareas`
--
ALTER TABLE `tareas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `usuarios_cursos`
--
ALTER TABLE `usuarios_cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `apartados`
--
ALTER TABLE `apartados`
  ADD CONSTRAINT `fk_apartados_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `aulas`
--
ALTER TABLE `aulas`
  ADD CONSTRAINT `aulas_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `calendario`
--
ALTER TABLE `calendario`
  ADD CONSTRAINT `calendario_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendario_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `calendario_ibfk_3` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categoria_apartado` FOREIGN KEY (`apartado_id`) REFERENCES `apartados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_categorias_apartado` FOREIGN KEY (`apartado_id`) REFERENCES `apartados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_categorias_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD CONSTRAINT `cursos_ibfk_1` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documentos`
--
ALTER TABLE `documentos`
  ADD CONSTRAINT `fk_documento_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `entregas`
--
ALTER TABLE `entregas`
  ADD CONSTRAINT `entregas_ibfk_1` FOREIGN KEY (`tarea_id`) REFERENCES `tareas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `entregas_ibfk_2` FOREIGN KEY (`alumno_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pagos`
--
ALTER TABLE `pagos`
  ADD CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `fk_entrega_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_entregas_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tareas_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_aula` FOREIGN KEY (`aulaId`) REFERENCES `aulas` (`id`);

--
-- Filtros para la tabla `usuarios_cursos`
--
ALTER TABLE `usuarios_cursos`
  ADD CONSTRAINT `usuarios_cursos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usuarios_cursos_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
