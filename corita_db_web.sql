-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-11-2025 a las 02:28:47
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
-- Base de datos: `corita_db_web`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int(11) NOT NULL,
  `id_doctor` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','confirmada','cancelada','completada') DEFAULT 'pendiente',
  `creada_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `id_doctor`, `id_paciente`, `fecha`, `hora`, `motivo`, `estado`, `creada_en`) VALUES
(1, 1, 1, '2025-11-27', '10:00:00', 'Revisión trimestral de presión arterial', 'pendiente', '2025-11-27 22:02:03'),
(2, 1, 2, '2025-12-04', '16:30:00', 'Ajuste de dosis de Metformina y análisis de glucosa', 'cancelada', '2025-11-27 22:02:04'),
(3, 1, 1, '2025-11-12', '09:00:00', 'Evaluación de síntomas de resfriado fuerte', 'completada', '2025-11-27 22:02:04'),
(4, 1, 1, '2025-12-25', '17:30:00', 'Chequeo', 'pendiente', '2025-11-27 22:30:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctores`
--

CREATE TABLE `doctores` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especialidad` varchar(100) NOT NULL,
  `rol` varchar(50) NOT NULL DEFAULT 'doctor',
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `qr_token` varchar(64) DEFAULT NULL,
  `qr_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `doctores`
--

INSERT INTO `doctores` (`id`, `nombre`, `especialidad`, `rol`, `telefono`, `correo`, `password`, `qr_token`, `qr_expira`) VALUES
(1, 'pedrito', '', 'doctor', '', '01@example.com', '$2y$10$bGDEp/g/7vpZcTShsgRRFelNUP./CdhUM08nQ6ilNCSgkTCke1iA2', 'bc3ad777a5047de3ab0c86933e59dacfd8aa1e77c23a008699fdcd661fbd95f8', '2025-11-28 02:09:49'),
(2, 'Michelle Escamilla', '', 'doctor', '', 'mich@gmail.com', '$2y$10$AjPTSkzZAH.1I2u7eMqIOesvYKI8YfzoXj63GvWDQv8vhzlCrPXBm', NULL, NULL),
(3, 'rehiu', '', 'admin', '', 'rehi@example.com', '$2y$10$6KeQ8xYMHxt6KePyGtgxfeYXoRSisPkWU/6Sa.qT0nNsgYhksvVPG', NULL, NULL),
(4, 'Julio Dominguez', '', 'doctor', '', 'julio@gamil.com', '$2y$10$cLLKimn2rcwYxIw5AaCzPeSpELmpQG2.hdxGWiZTqjedQTi97lKS6', NULL, NULL),
(5, 'Fernando Barrera', '', 'doctor', '', 'fer1@example.com', '$2y$10$4Sy9N.MB3dDrVhlbi3RRue7lNmQ805WEaJonIzVVPaJAkygedQvye', NULL, NULL),
(7, 'Admin Pruebas', 'Soporte', 'admin', NULL, 'prueba@corita.com', '$2y$10$0p2W.W0X9QY9gK2p7v4G5u3S1T2U3V4X5Z6A7B8C9D0E1F2G3H4I5J6K7L8M', NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_medicamentos`
--

CREATE TABLE `historial_medicamentos` (
  `id` int(11) NOT NULL,
  `id_paciente` int(11) NOT NULL,
  `medicamento` varchar(100) NOT NULL,
  `dosis` varchar(50) NOT NULL,
  `frecuencia` varchar(100) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_medicamentos`
--

INSERT INTO `historial_medicamentos` (`id`, `id_paciente`, `medicamento`, `dosis`, `frecuencia`, `fecha_inicio`) VALUES
(1, 1, 'Losartán', '50 mg', 'Una vez al día', '2024-05-15'),
(2, 1, 'Metformina', '850 mg', 'Dos veces al día', '2023-11-20'),
(3, 2, 'Amlodipino', '5 mg', 'Una vez al día', '2024-01-10'),
(4, 1, 'Paracetamol', '8gr', '2 veces al dia durante 8 hrs', '2025-11-22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `diagnostico_principal` varchar(100) DEFAULT NULL,
  `id_doctor` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id`, `nombre`, `correo`, `diagnostico_principal`, `id_doctor`) VALUES
(1, 'María Hernández', 'maria@ejemplo.com', 'Hipertensión', 1),
(2, 'Juan Juárez Perez', 'juan@ejemplo.com', 'Diabetes Tipo 2', 1),
(3, 'Laura Gómez', 'laura@otro.com', 'Asma', 2),
(4, 'Michelle Escamilla', 'mich@gmail.com', 'Diabetes Tipo 1', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_doctor` (`id_doctor`),
  ADD KEY `id_paciente` (`id_paciente`);

--
-- Indices de la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `historial_medicamentos`
--
ALTER TABLE `historial_medicamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_paciente` (`id_paciente`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `doctores`
--
ALTER TABLE `doctores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `historial_medicamentos`
--
ALTER TABLE `historial_medicamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `historial_medicamentos`
--
ALTER TABLE `historial_medicamentos`
  ADD CONSTRAINT `historial_medicamentos_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
