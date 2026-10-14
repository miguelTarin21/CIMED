-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-04-2026 a las 08:14:27
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `simulador_medico`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `archivos`
--

CREATE TABLE `archivos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `id_caso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `archivos`
--

INSERT INTO `archivos` (`id`, `nombre`, `ruta`, `id_caso`) VALUES
(5, 'Estudio1', './uploads/caso_5/reporte_firewall.docx.pdf', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carpetas`
--

CREATE TABLE `carpetas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `id_usuario_creador` int(11) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `carpetas`
--

INSERT INTO `carpetas` (`id`, `nombre`, `descripcion`, `id_usuario_creador`, `fecha_creacion`) VALUES
(1, 'Respiratorios', '', 16, '2026-04-09 06:08:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `casos`
--

CREATE TABLE `casos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `visibilidad` int(11) NOT NULL DEFAULT 0,
  `clave` varchar(255) NOT NULL,
  `descripcion` text NOT NULL,
  `motivo_consulta` text NOT NULL,
  `id_usuario_creador` int(11) DEFAULT NULL,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_carpeta` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `casos`
--

INSERT INTO `casos` (`id`, `nombre`, `visibilidad`, `clave`, `descripcion`, `motivo_consulta`, `id_usuario_creador`, `fecha_actualizacion`, `id_carpeta`) VALUES
(5, '02210769', 1, '', 'pierde la capacidad de respirar', 'Presenta problemas respiratorios', 16, '2026-04-09 06:11:29', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `incisos`
--

CREATE TABLE `incisos` (
  `id` int(11) NOT NULL,
  `id_pregunta` int(11) DEFAULT NULL,
  `texto` varchar(255) DEFAULT NULL,
  `correcto` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `incisos`
--

INSERT INTO `incisos` (`id`, `id_pregunta`, `texto`, `correcto`) VALUES
(25, 13, 'jsjsjs', 1),
(26, 13, 'jsjsjsjs', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `sexo` varchar(50) NOT NULL,
  `edad` int(11) NOT NULL,
  `peso` float NOT NULL,
  `altura` float NOT NULL,
  `enfermedades` text DEFAULT NULL,
  `id_signos_vitales` int(11) DEFAULT NULL,
  `id_caso` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `nombre`, `sexo`, `edad`, `peso`, `altura`, `enfermedades`, `id_signos_vitales`, `id_caso`) VALUES
(5, 'Jorge Ruiz', 'Hombre', 21, 68, 168, 'jsjsjsjs', 5, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `preguntas`
--

CREATE TABLE `preguntas` (
  `id` int(11) NOT NULL,
  `texto_pregunta` text NOT NULL,
  `feedback` text DEFAULT NULL,
  `id_caso` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `preguntas`
--

INSERT INTO `preguntas` (`id`, `texto_pregunta`, `feedback`, `id_caso`) VALUES
(13, 'jsjsjsjs', 'jsjsjsjs', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `signos_pacientes`
--

CREATE TABLE `signos_pacientes` (
  `id` int(11) NOT NULL,
  `FR` int(11) DEFAULT NULL,
  `FC` int(11) DEFAULT NULL,
  `TA` varchar(50) DEFAULT NULL,
  `Temperatura` double DEFAULT NULL,
  `SAT` int(11) DEFAULT NULL,
  `ritmo` varchar(50) DEFAULT 'sinusal',
  `id_paciente` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `signos_pacientes`
--

INSERT INTO `signos_pacientes` (`id`, `FR`, `FC`, `TA`, `Temperatura`, `SAT`, `id_paciente`) VALUES
(5, 18, 90, '90/80', 36.3, 96, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `correo` varchar(255) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `es_admin` tinyint(1) NOT NULL DEFAULT 0,
  `clave_asignada` varchar(255) DEFAULT NULL,
  `superadmin` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish2_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `correo`, `contraseña`, `reset_token`, `es_admin`, `clave_asignada`, `superadmin`) VALUES
(15, '0717502b93ff97a3ab6cab132e014fd61f2749eb9094cb04d37b28ab895bc7cf', '$2y$10$FkGBIUKaebmy4z2E9rkrCuIG/IlVp/UqFHM.uDkjTv5j4SZgvPkgu', '', 0, 'f5bce76b7c8f7798c2b809d80a1edb8bff9c7246171d4b004d17398facf7a80d', 0),
(16, '0c4765c50569dff8a4876191afacf8e30bfbe82f6a8158a62998a6baae883d9e', '$2y$10$85kiqvagDG16arFrlV5SguG2pw28iMpguvxbJhHuldac8JkiIVziK', '', 0, '15101cf8441eef7d0f63fa86011995584d425b02cbbc9eddb6a65544a1a10895', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `id_caso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_caso` (`id_caso`);

--
-- Indices de la tabla `carpetas`
--
ALTER TABLE `carpetas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carpeta_usuario` (`id_usuario_creador`);

--
-- Indices de la tabla `casos`
--
ALTER TABLE `casos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_creador` (`id_usuario_creador`),
  ADD KEY `fk_caso_carpeta` (`id_carpeta`);

--
-- Indices de la tabla `incisos`
--
ALTER TABLE `incisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_incisos_pregunta` (`id_pregunta`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_perfil_paciente_caso` (`id_caso`),
  ADD KEY `id_signos_vitales` (`id_signos_vitales`);

--
-- Indices de la tabla `preguntas`
--
ALTER TABLE `preguntas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_preguntas_caso` (`id_caso`);

--
-- Indices de la tabla `signos_pacientes`
--
ALTER TABLE `signos_pacientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_paciente` (`id_paciente`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`correo`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_video_caso` (`id_caso`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `archivos`
--
ALTER TABLE `archivos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `carpetas`
--
ALTER TABLE `carpetas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `casos`
--
ALTER TABLE `casos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `incisos`
--
ALTER TABLE `incisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `preguntas`
--
ALTER TABLE `preguntas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `signos_pacientes`
--
ALTER TABLE `signos_pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `archivos`
--
ALTER TABLE `archivos`
  ADD CONSTRAINT `archivos_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `carpetas`
--
ALTER TABLE `carpetas`
  ADD CONSTRAINT `carpeta_usuario_fk` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `casos`
--
ALTER TABLE `casos`
  ADD CONSTRAINT `caso_carpeta_fk` FOREIGN KEY (`id_carpeta`) REFERENCES `carpetas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `casos_usuario_fk` FOREIGN KEY (`id_usuario_creador`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `incisos`
--
ALTER TABLE `incisos`
  ADD CONSTRAINT `incisos_ibfk_1` FOREIGN KEY (`id_pregunta`) REFERENCES `preguntas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pacientes_ibfk_2` FOREIGN KEY (`id_signos_vitales`) REFERENCES `signos_pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `preguntas`
--
ALTER TABLE `preguntas`
  ADD CONSTRAINT `preguntas_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `signos_pacientes`
--
ALTER TABLE `signos_pacientes`
  ADD CONSTRAINT `signos_pacientes_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`id_caso`) REFERENCES `casos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
