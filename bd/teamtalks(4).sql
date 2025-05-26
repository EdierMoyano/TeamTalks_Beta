-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 26, 2025 at 03:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `teamtalks`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AsignarHorarioClase` (IN `p_id_clase` INT, IN `p_id_dia` INT, IN `p_id_bloque` INT)   BEGIN
    -- Verificar si ya existe un horario para esta clase en este día y bloque
    IF NOT EXISTS (
        SELECT 1 FROM clase_horario 
        WHERE id_clase = p_id_clase 
        AND id_dia = p_id_dia 
        AND id_bloque = p_id_bloque
    ) THEN
        -- Insertar el nuevo horario
        INSERT INTO clase_horario (id_clase, id_dia, id_bloque)
        VALUES (p_id_clase, p_id_dia, p_id_bloque);
        
        SELECT 'Horario asignado correctamente' AS mensaje;
    ELSE
        SELECT 'Este horario ya está asignado a esta clase' AS mensaje;
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ObtenerClasesUsuario` (IN `p_id_user` INT)   BEGIN
    SELECT 
        c.Id_clase,
        c.Nom_clase,
        f.id_ficha,
        f.Jornada,
        fo.Nombre AS nombre_formacion
    FROM Usuarios u
    JOIN detalle_usuarios_fichas duf ON u.Id_user = duf.id_user
    JOIN fichas f ON duf.id_ficha = f.id_ficha
    JOIN clases c ON f.id_ficha = c.id_ficha
    JOIN Formacion fo ON f.id_formacion = fo.id_formacion
    WHERE u.Id_user = p_id_user
    ORDER BY f.id_ficha, c.Nom_clase;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ObtenerHorarioFicha` (IN `p_id_ficha` INT)   BEGIN
    SELECT 
        c.Nom_clase,
        ds.nombre_dia,
        bh.hora_inicio,
        bh.hora_fin,
        bh.descripcion
    FROM clases c
    JOIN clase_horario ch ON c.Id_clase = ch.id_clase
    JOIN dias_semana ds ON ch.id_dia = ds.id_dia
    JOIN bloques_horario bh ON ch.id_bloque = bh.id_bloque
    WHERE c.id_ficha = p_id_ficha
    AND ch.activo = TRUE
    ORDER BY ds.id_dia, bh.hora_inicio;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `VerificarConflictosHorario` (IN `p_id_ficha` INT)   BEGIN
    SELECT 
        c1.Nom_clase AS clase1,
        c2.Nom_clase AS clase2,
        ds.nombre_dia,
        bh.descripcion,
        bh.hora_inicio,
        bh.hora_fin
    FROM clase_horario ch1
    JOIN clases c1 ON ch1.id_clase = c1.Id_clase
    JOIN clase_horario ch2 ON ch1.id_dia = ch2.id_dia AND ch1.id_bloque = ch2.id_bloque
    JOIN clases c2 ON ch2.id_clase = c2.Id_clase
    JOIN dias_semana ds ON ch1.id_dia = ds.id_dia
    JOIN bloques_horario bh ON ch1.id_bloque = bh.id_bloque
    WHERE c1.id_ficha = p_id_ficha
    AND c2.id_ficha = p_id_ficha
    AND c1.Id_clase < c2.Id_clase -- Evitar duplicados
    AND ch1.activo = TRUE
    AND ch2.activo = TRUE
    ORDER BY ds.id_dia, bh.hora_inicio;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `actividades`
--

CREATE TABLE `actividades` (
  `id_actividad` int(11) NOT NULL,
  `id_materia_ficha` int(11) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `actividades_user`
--

CREATE TABLE `actividades_user` (
  `id_actividad_user` int(11) NOT NULL,
  `id_actividad` int(11) DEFAULT NULL,
  `id_estado_actividad` int(11) DEFAULT NULL,
  `contenido` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `id_user` bigint(20) DEFAULT NULL,
  `nota` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ambientes`
--

CREATE TABLE `ambientes` (
  `id_ambiente` int(11) NOT NULL,
  `ambiente` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ambientes`
--

INSERT INTO `ambientes` (`id_ambiente`, `ambiente`) VALUES
(1, 'Ambiente Virtual');

-- --------------------------------------------------------

--
-- Table structure for table `empresa`
--

CREATE TABLE `empresa` (
  `nit` int(11) NOT NULL,
  `empresa` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `empresa`
--

INSERT INTO `empresa` (`nit`, `empresa`) VALUES
(0, 'SENA'),
(159, 'sena\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `estado`
--

CREATE TABLE `estado` (
  `id_estado` int(11) NOT NULL,
  `estado` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `estado`
--

INSERT INTO `estado` (`id_estado`, `estado`) VALUES
(1, 'Activo'),
(2, 'Inactivo');

-- --------------------------------------------------------

--
-- Table structure for table `fichas`
--

CREATE TABLE `fichas` (
  `id_ficha` int(11) NOT NULL,
  `id_formacion` int(11) DEFAULT NULL,
  `id_ambiente` int(11) DEFAULT NULL,
  `fecha_creac` date DEFAULT NULL,
  `id_instructor` bigint(20) DEFAULT NULL,
  `id_jornada` int(11) DEFAULT NULL,
  `id_tipo_ficha` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL,
  `id_trimestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fichas`
--

INSERT INTO `fichas` (`id_ficha`, `id_formacion`, `id_ambiente`, `fecha_creac`, `id_instructor`, `id_jornada`, `id_tipo_ficha`, `id_estado`, `id_trimestre`) VALUES
(2323, 12345, NULL, '2025-05-16', 3133676, 1, 1, 1, NULL),
(2900000, 12346, NULL, '2025-05-25', 1598989, 3, 1, 1, NULL),
(2900001, 12345, NULL, '2025-05-23', 1598989, 3, 2, 1, NULL),
(2900002, 12345, NULL, '2025-05-23', 1598989, 3, 2, 1, NULL),
(2900003, 12345, NULL, '2025-05-23', 1598989, 3, 1, 1, NULL),
(2900004, 12346, NULL, '2025-05-23', 1598989, 3, 2, 1, NULL),
(2900005, 12346, NULL, '2025-05-23', 1598989, 3, 2, 1, NULL),
(2900006, 12346, NULL, '2025-05-23', 1598989, 3, 1, 1, NULL),
(2900007, 12345, NULL, '2025-05-23', 3133676, 1, 1, 1, NULL),
(2900008, 12345, NULL, '2025-05-23', 3133676, 3, 1, 1, NULL),
(2900009, 12346, NULL, '2025-05-23', 1598989, 3, 1, 1, NULL),
(2900010, 12346, NULL, '2025-05-23', 1598989, 3, 1, 1, NULL),
(2900011, 12345, NULL, '2025-07-22', 3133676, 1, 2, 1, NULL),
(2900012, 12346, NULL, '2025-05-14', 3133676, 2, 1, 1, NULL),
(2900013, 12345, NULL, '2025-05-25', 3133676, 1, 1, 1, NULL),
(2900014, 12345, NULL, '2025-05-11', 3133676, 3, 1, 1, NULL),
(2900015, 12346, NULL, '2025-05-25', 543232, 2, 1, 1, NULL),
(2900016, 12345, NULL, '2025-05-30', 3133676, 2, 1, 1, NULL),
(2900017, 12345, NULL, '2025-05-30', 3133676, 2, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `formacion`
--

CREATE TABLE `formacion` (
  `id_formacion` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` date DEFAULT current_timestamp(),
  `id_estado` int(11) DEFAULT 1,
  `id_tipo_formacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `formacion`
--

INSERT INTO `formacion` (`id_formacion`, `nombre`, `descripcion`, `fecha_creacion`, `id_estado`, `id_tipo_formacion`) VALUES
(12345, 'Adsi', 'Adsi', '2025-05-16', 1, 2),
(12346, 'Desarrollo de videojuegos', 'Desarrollar videojuegos a medida', '2025-05-22', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `foros`
--

CREATE TABLE `foros` (
  `id_foro` int(11) NOT NULL,
  `id_materia_ficha` int(11) DEFAULT NULL,
  `fecha_foro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `historial_contra`
--

CREATE TABLE `historial_contra` (
  `id` int(11) NOT NULL,
  `id_user` bigint(20) DEFAULT NULL,
  `contraseña_ant` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `id_materia_ficha` int(11) DEFAULT NULL,
  `dia_semana` varchar(15) DEFAULT NULL,
  `hora_inicio` time DEFAULT NULL,
  `hora_fin` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jornada`
--

CREATE TABLE `jornada` (
  `id_jornada` int(11) NOT NULL,
  `jornada` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jornada`
--

INSERT INTO `jornada` (`id_jornada`, `jornada`) VALUES
(1, 'Mañana'),
(2, 'Tarde'),
(3, 'Noche'),
(4, 'Mixta');

-- --------------------------------------------------------

--
-- Table structure for table `licencias`
--

CREATE TABLE `licencias` (
  `id_licencia` varchar(10) NOT NULL,
  `id_tipo_licencia` int(3) NOT NULL,
  `fecha_ini` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `nit` int(50) NOT NULL,
  `estado` enum('Activa','Expirada','Inactiva') DEFAULT 'Activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `materia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materias`
--

INSERT INTO `materias` (`id_materia`, `materia`) VALUES
(1, 'Matemáticas'),
(2, 'Matemáticas'),
(3, 'Inglés'),
(4, 'Educación Física'),
(5, 'Biología'),
(6, 'Química'),
(7, 'Filosofía'),
(8, 'Ética'),
(9, 'Emprendimiento'),
(10, 'Informática'),
(11, 'Arte'),
(12, 'Tecnología'),
(13, 'Lengua Castellana');

-- --------------------------------------------------------

--
-- Table structure for table `materia_ficha`
--

CREATE TABLE `materia_ficha` (
  `id_materia_ficha` int(11) NOT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `id_instructor` bigint(20) DEFAULT NULL,
  `id_trimestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recuperacion`
--

CREATE TABLE `recuperacion` (
  `id_recuperacion` int(11) NOT NULL,
  `id_usuario` bigint(20) DEFAULT NULL,
  `token` int(11) DEFAULT NULL,
  `fecha_expiracion` datetime NOT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recuperacion`
--

INSERT INTO `recuperacion` (`id_recuperacion`, `id_usuario`, `token`, `fecha_expiracion`, `fecha_creacion`) VALUES
(2, 1104940105, 923968, '2025-05-08 03:36:10', '2025-05-07 20:21:10');

-- --------------------------------------------------------

--
-- Table structure for table `respuesta_foro`
--

CREATE TABLE `respuesta_foro` (
  `id_respuesta_foro` int(11) NOT NULL,
  `id_tema_foro` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_respuesta` date DEFAULT NULL,
  `id_user` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id_rol` int(11) NOT NULL,
  `rol` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id_rol`, `rol`) VALUES
(1, 'S_Admin'),
(2, 'Admin'),
(3, 'Instructor'),
(4, 'Aprendiz');

-- --------------------------------------------------------

--
-- Table structure for table `temas_foro`
--

CREATE TABLE `temas_foro` (
  `id_tema_foro` int(11) NOT NULL,
  `id_foro` int(11) DEFAULT NULL,
  `titulo` varchar(100) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` date DEFAULT NULL,
  `id_user` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipo_documento`
--

CREATE TABLE `tipo_documento` (
  `id_tipo` int(11) NOT NULL,
  `tipo_doc` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipo_documento`
--

INSERT INTO `tipo_documento` (`id_tipo`, `tipo_doc`) VALUES
(1, 'Cedula'),
(2, 'Tarjeta Identidad');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_ficha`
--

CREATE TABLE `tipo_ficha` (
  `id_tipo_ficha` int(11) NOT NULL,
  `tipo_ficha` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipo_ficha`
--

INSERT INTO `tipo_ficha` (`id_tipo_ficha`, `tipo_ficha`) VALUES
(0, 'A distancia'),
(1, 'Presencial'),
(2, 'Virtual');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_formacion`
--

CREATE TABLE `tipo_formacion` (
  `id_tipo_formacion` int(11) NOT NULL,
  `tipo_formacion` varchar(50) NOT NULL,
  `Duracion` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tipo_formacion`
--

INSERT INTO `tipo_formacion` (`id_tipo_formacion`, `tipo_formacion`, `Duracion`) VALUES
(1, 'Tecnico', '3 Trimestres'),
(2, 'Tecnologo', '7 Trimestres'),
(3, 'Ténico', '3 Trimestres'),
(4, 'Tecnólogo', '7 Trimestres');

-- --------------------------------------------------------

--
-- Table structure for table `tipo_licencia`
--

CREATE TABLE `tipo_licencia` (
  `id_tipo_licencia` int(11) NOT NULL,
  `licencia` varchar(50) NOT NULL,
  `duracion` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trimestre`
--

CREATE TABLE `trimestre` (
  `id_trimestre` int(11) NOT NULL,
  `trimestre` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trimestre`
--

INSERT INTO `trimestre` (`id_trimestre`, `trimestre`) VALUES
(1, NULL),
(2, NULL),
(3, NULL),
(4, NULL),
(5, NULL),
(6, NULL),
(7, NULL),
(8, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_ficha`
--

CREATE TABLE `user_ficha` (
  `id_user_ficha` int(11) NOT NULL,
  `id_user` bigint(20) DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `fecha_asig` date DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_ficha`
--

INSERT INTO `user_ficha` (`id_user_ficha`, `id_user`, `id_ficha`, `fecha_asig`, `id_estado`) VALUES
(1, 123123, 2323, '2025-05-21', 1),
(2, 76543210, 2323, '2025-05-22', 1),
(3, 765432210, 2323, '2025-05-22', 1),
(4, 44322210, 2323, '2025-05-22', 1),
(5, 443272210, 2323, '2025-05-22', 1),
(6, 25323123, 2323, '2025-05-22', 1),
(7, 85858, 2323, '2025-05-22', 1),
(8, 3157883790, 2323, '2025-05-22', 1),
(9, 31578583790, 2323, '2025-05-25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` bigint(20) NOT NULL,
  `id_tipo` int(11) DEFAULT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `nit` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `id_tipo`, `nombres`, `apellidos`, `correo`, `contraseña`, `avatar`, `telefono`, `id_rol`, `id_estado`, `fecha_registro`, `nit`) VALUES
(12312, 1, 'ssadasdasd', 'asdasd', 'edasda@gmail.com', '$2y$10$d/v28UpCaYFJO7GblUIZxuWiwXOSuRDYztdDu5GR7Kj7AU014wGUS', NULL, '3123123123', 4, 1, '2025-05-21', 159),
(13123, 1, 'sebas', 'aranda', 'aranda@gmail.com', '$2y$10$9Vaj2kPEU6NSPrD1VQZkAOugCa5KvqoyuE1MXBMJfUPpdBhBNSo9u', NULL, '3028623064', 2, 1, '2025-05-22', 159),
(85858, 1, 'rere', 're', 'rere@gmail.com', '$2y$10$KYx2cR2aqM2UULZQKrHUKOj7tYRqi/Vk0xDmOCZeq4G/wOIvj5Nri', NULL, '323232323', 4, 1, '2025-05-22', 159),
(123123, 1, 'asdasd', 'asdasd', 'ediasd@gmail.com', '$2y$10$9hN6EFNRs0gK/shTn6zj8.RuEVoAJgNluu/PXQ7ZFR5YCpPTc4W1W', NULL, '302554554', 4, 1, '2025-05-21', 159),
(123422, 2, 'asdasd', 'asda', 'adsa@gmailcom', '$2y$10$2IbhSPhYH9Ti48lSUnK0NebsaB3WoBe48NgLkgDUrKN6p39PRn5pG', NULL, '2323213123', 4, 1, '2025-05-21', 159),
(542323, 1, 'asdasd', 'asdasd', 'edeirs@gmail.com', '$2y$10$1R1H8aGpKkX9qpFVTOh7iuwOU4fF33qP.nP9mUss85ffqjrWKvoHO', NULL, '123123', 4, 1, '2025-05-21', 159),
(543232, 1, 'rfdd', 'ddd', 'addas@gmail.com', '$2y$10$jNDQk9HfuQnIRgLhWbQD4evzXRgJJu/B.BEqPvqTgZqCq6FLFyh/y', NULL, '543432', 3, 1, '2025-05-25', 159),
(1598989, 1, 'asdasd', 'asdasd', 'asdasdasd@gmail.com', '$2y$10$W6F5TyILsGEmKEWrX/WWr.Ox0kyWdB/gHol06j.OiVs0zDQ8cEcsa', NULL, '123123', 3, 1, '2025-05-22', 159),
(3133676, 1, 'Elmer ', 'Moyano', 'ediermb@hotmail.com', 'edier2005', NULL, '3175942017', 3, 1, NULL, 0),
(5423232, 1, 'dasd', 'asdasd', '2dwadad@gmail.com', '$2y$10$KqNv.hlKYYw/Q/aBp739ZO09zvu1ANYo5V.VyY3T971yhE1yau/Oe', NULL, '123123', 2, 1, '2025-05-21', 159),
(25323123, 2, 'pepericopepe', 'asdasd', 'wadsfad@gmail.com', '$2y$10$ruH47VSgiwqX4f3j.f53CeNqYy3Mlb1z7eyKxVMoYJIMOum6qBA4.', NULL, '225252', 4, 1, '2025-05-22', 159),
(44322210, 1, 'Julian Lópaez', NULL, 'Juliana@gmail.com', '$2y$10$fcFZIPf/Tzf9jW1138YVL.IQo80kHKYcxnVGUp7.nRN2jtnPa6b6i', '', '3102345678', 4, 1, '2025-05-22', 159),
(76543210, 1, 'María López', NULL, 'marialopez@mail.com', '$2y$10$c3wuxXBVCUNBhOzx6d//fOeCT6YD6nt1AQ/DySoz2LBgJ51bpks32', '', '3102345678', 4, 1, '2025-05-22', 159),
(232121111, 1, 'weadad', 'adad', 'adas@gmail.com', '$2y$10$RHLYh4BJNrM5FI9JnTuE1u5/JSAlGvOhVUQvTDCIojwWbFfUpYWea', NULL, '123123123', 4, 1, '2025-05-21', 159),
(443272210, 2, 'Juliaan Lópaez', NULL, 'Juliaana@gmail.com', '$2y$10$gwPMsPjjYfvMfkuXWYQ3DekXUImk5FbPjugn/AyI0.mQZNeTXRF0W', '', '31022345678', 4, 1, '2025-05-22', 159),
(765432210, 1, 'Maríaa López', NULL, 'mariaalopez@mail.com', '$2y$10$bPm7y/Ce0IyQSSB47cfrKeBF4aDsjvORq36YqKj6IQU.cDxYz1Tfm', '', '3102345678', 4, 1, '2025-05-22', 159),
(1104940105, 1, 'Edier\r\n', 'Moyano', 'ediersmb@gmail.com', '$2y$12$Z8XHAwYyhkcYU8LCwUCu3.Ff3LHBigWUDlOjF7wRlyFb6wmQYfply', NULL, '3028623064', 1, 1, NULL, 159),
(2147483647, 1, '222sadasda', 'asdas2', 'peee@gmail.com', '$2y$10$9WMyF7K41WS4hYXfb8kUOex8P495mjZuTSDBRONAriOfyw4mEiQk.', NULL, '32323211', 4, 1, '2025-05-21', 159),
(3157883790, 1, 'marian Lópae5z', NULL, 'piuliaa1na@gmail.com', '$2y$10$PLkz31WOt5euke2lsmp0XuIr/rzdYP2qREneT/l2YickNs5WioMLy', '', '31999345678', 4, 1, '2025-05-23', 159),
(31578583790, 1, 'mariano Lopae5z', NULL, 'piuliaaa1na@gmail.com', '$2y$10$iHjGsHUS.kP3hLq2c5DPDem0nX.xXzD3MWWYZJhY/5NfWvtEh.jD2', '', '31999345678', 3, 1, '2025-05-25', 159),
(315783583790, 1, 'yotasz', NULL, 'yotasa@gmail.com', '$2y$10$wj9ki0Cj.MMwFuajVxtDjOyEWkkJqnHYsNcZ3Cpdm0a3fZ0BtluLG', '', '31999345678', 3, 1, '2025-05-25', 159);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actividades`
--
ALTER TABLE `actividades`
  ADD PRIMARY KEY (`id_actividad`),
  ADD KEY `id_materia_ficha` (`id_materia_ficha`);

--
-- Indexes for table `actividades_user`
--
ALTER TABLE `actividades_user`
  ADD PRIMARY KEY (`id_actividad_user`),
  ADD KEY `id_actividad` (`id_actividad`),
  ADD KEY `id_estado_actividad` (`id_estado_actividad`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `ambientes`
--
ALTER TABLE `ambientes`
  ADD PRIMARY KEY (`id_ambiente`);

--
-- Indexes for table `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`nit`);

--
-- Indexes for table `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id_estado`);

--
-- Indexes for table `fichas`
--
ALTER TABLE `fichas`
  ADD PRIMARY KEY (`id_ficha`),
  ADD KEY `id_ambiente` (`id_ambiente`),
  ADD KEY `id_instructor` (`id_instructor`),
  ADD KEY `id_jornada` (`id_jornada`),
  ADD KEY `id_tipo_ficha` (`id_tipo_ficha`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `nombre_formacion` (`id_formacion`);

--
-- Indexes for table `formacion`
--
ALTER TABLE `formacion`
  ADD PRIMARY KEY (`id_formacion`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `id_tipo_formacion` (`id_tipo_formacion`);

--
-- Indexes for table `foros`
--
ALTER TABLE `foros`
  ADD PRIMARY KEY (`id_foro`),
  ADD KEY `id_materia_ficha` (`id_materia_ficha`);

--
-- Indexes for table `historial_contra`
--
ALTER TABLE `historial_contra`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`),
  ADD KEY `id_materia_ficha` (`id_materia_ficha`);

--
-- Indexes for table `jornada`
--
ALTER TABLE `jornada`
  ADD PRIMARY KEY (`id_jornada`);

--
-- Indexes for table `licencias`
--
ALTER TABLE `licencias`
  ADD PRIMARY KEY (`id_licencia`),
  ADD KEY `nit` (`nit`),
  ADD KEY `id_tipo_licencia` (`id_tipo_licencia`);

--
-- Indexes for table `materias`
--
ALTER TABLE `materias`
  ADD PRIMARY KEY (`id_materia`);

--
-- Indexes for table `materia_ficha`
--
ALTER TABLE `materia_ficha`
  ADD PRIMARY KEY (`id_materia_ficha`),
  ADD KEY `id_materia` (`id_materia`),
  ADD KEY `id_ficha` (`id_ficha`),
  ADD KEY `id_instructor` (`id_instructor`),
  ADD KEY `id_trimestre` (`id_trimestre`);

--
-- Indexes for table `recuperacion`
--
ALTER TABLE `recuperacion`
  ADD PRIMARY KEY (`id_recuperacion`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `recuperacion_ibfk_1` (`id_usuario`);

--
-- Indexes for table `respuesta_foro`
--
ALTER TABLE `respuesta_foro`
  ADD PRIMARY KEY (`id_respuesta_foro`),
  ADD KEY `id_tema_foro` (`id_tema_foro`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indexes for table `temas_foro`
--
ALTER TABLE `temas_foro`
  ADD PRIMARY KEY (`id_tema_foro`),
  ADD KEY `id_foro` (`id_foro`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tipo_documento`
--
ALTER TABLE `tipo_documento`
  ADD PRIMARY KEY (`id_tipo`);

--
-- Indexes for table `tipo_ficha`
--
ALTER TABLE `tipo_ficha`
  ADD PRIMARY KEY (`id_tipo_ficha`);

--
-- Indexes for table `tipo_formacion`
--
ALTER TABLE `tipo_formacion`
  ADD PRIMARY KEY (`id_tipo_formacion`);

--
-- Indexes for table `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  ADD PRIMARY KEY (`id_tipo_licencia`);

--
-- Indexes for table `trimestre`
--
ALTER TABLE `trimestre`
  ADD PRIMARY KEY (`id_trimestre`);

--
-- Indexes for table `user_ficha`
--
ALTER TABLE `user_ficha`
  ADD PRIMARY KEY (`id_user_ficha`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_ficha` (`id_ficha`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indexes for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_tipo` (`id_tipo`),
  ADD KEY `id_rol` (`id_rol`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `nit` (`nit`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `estado`
--
ALTER TABLE `estado`
  MODIFY `id_estado` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fichas`
--
ALTER TABLE `fichas`
  MODIFY `id_ficha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2900018;

--
-- AUTO_INCREMENT for table `formacion`
--
ALTER TABLE `formacion`
  MODIFY `id_formacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12347;

--
-- AUTO_INCREMENT for table `materias`
--
ALTER TABLE `materias`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `recuperacion`
--
ALTER TABLE `recuperacion`
  MODIFY `id_recuperacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tipo_documento`
--
ALTER TABLE `tipo_documento`
  MODIFY `id_tipo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tipo_formacion`
--
ALTER TABLE `tipo_formacion`
  MODIFY `id_tipo_formacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_ficha`
--
ALTER TABLE `user_ficha`
  MODIFY `id_user_ficha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `actividades`
--
ALTER TABLE `actividades`
  ADD CONSTRAINT `actividades_ibfk_1` FOREIGN KEY (`id_materia_ficha`) REFERENCES `materia_ficha` (`id_materia_ficha`);

--
-- Constraints for table `actividades_user`
--
ALTER TABLE `actividades_user`
  ADD CONSTRAINT `actividades_user_ibfk_1` FOREIGN KEY (`id_actividad`) REFERENCES `actividades` (`id_actividad`),
  ADD CONSTRAINT `actividades_user_ibfk_2` FOREIGN KEY (`id_estado_actividad`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `actividades_user_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `fichas`
--
ALTER TABLE `fichas`
  ADD CONSTRAINT `fichas_ibfk_1` FOREIGN KEY (`id_ambiente`) REFERENCES `ambientes` (`id_ambiente`),
  ADD CONSTRAINT `fichas_ibfk_2` FOREIGN KEY (`id_instructor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fichas_ibfk_3` FOREIGN KEY (`id_jornada`) REFERENCES `jornada` (`id_jornada`),
  ADD CONSTRAINT `fichas_ibfk_4` FOREIGN KEY (`id_tipo_ficha`) REFERENCES `tipo_ficha` (`id_tipo_ficha`),
  ADD CONSTRAINT `fichas_ibfk_5` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `nombre_formacion` FOREIGN KEY (`id_formacion`) REFERENCES `formacion` (`id_formacion`);

--
-- Constraints for table `formacion`
--
ALTER TABLE `formacion`
  ADD CONSTRAINT `formacion_ibfk_1` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `formacion_ibfk_2` FOREIGN KEY (`id_tipo_formacion`) REFERENCES `tipo_formacion` (`id_tipo_formacion`);

--
-- Constraints for table `foros`
--
ALTER TABLE `foros`
  ADD CONSTRAINT `foros_ibfk_1` FOREIGN KEY (`id_materia_ficha`) REFERENCES `materia_ficha` (`id_materia_ficha`);

--
-- Constraints for table `historial_contra`
--
ALTER TABLE `historial_contra`
  ADD CONSTRAINT `historial_contra_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `horario`
--
ALTER TABLE `horario`
  ADD CONSTRAINT `horario_ibfk_1` FOREIGN KEY (`id_materia_ficha`) REFERENCES `materia_ficha` (`id_materia_ficha`);

--
-- Constraints for table `licencias`
--
ALTER TABLE `licencias`
  ADD CONSTRAINT `licencias_ibfk_1` FOREIGN KEY (`nit`) REFERENCES `empresa` (`nit`),
  ADD CONSTRAINT `licencias_ibfk_2` FOREIGN KEY (`id_tipo_licencia`) REFERENCES `tipo_licencia` (`id_tipo_licencia`);

--
-- Constraints for table `materia_ficha`
--
ALTER TABLE `materia_ficha`
  ADD CONSTRAINT `materia_ficha_ibfk_1` FOREIGN KEY (`id_materia`) REFERENCES `materias` (`id_materia`),
  ADD CONSTRAINT `materia_ficha_ibfk_2` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id_ficha`),
  ADD CONSTRAINT `materia_ficha_ibfk_3` FOREIGN KEY (`id_instructor`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `materia_ficha_ibfk_4` FOREIGN KEY (`id_trimestre`) REFERENCES `trimestre` (`id_trimestre`);

--
-- Constraints for table `recuperacion`
--
ALTER TABLE `recuperacion`
  ADD CONSTRAINT `recuperacion_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `respuesta_foro`
--
ALTER TABLE `respuesta_foro`
  ADD CONSTRAINT `respuesta_foro_ibfk_1` FOREIGN KEY (`id_tema_foro`) REFERENCES `temas_foro` (`id_tema_foro`),
  ADD CONSTRAINT `respuesta_foro_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `temas_foro`
--
ALTER TABLE `temas_foro`
  ADD CONSTRAINT `temas_foro_ibfk_1` FOREIGN KEY (`id_foro`) REFERENCES `foros` (`id_foro`),
  ADD CONSTRAINT `temas_foro_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`id`);

--
-- Constraints for table `user_ficha`
--
ALTER TABLE `user_ficha`
  ADD CONSTRAINT `user_ficha_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `user_ficha_ibfk_2` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id_ficha`),
  ADD CONSTRAINT `user_ficha_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);

--
-- Constraints for table `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipo_documento` (`id_tipo`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`),
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`),
  ADD CONSTRAINT `usuarios_ibfk_4` FOREIGN KEY (`nit`) REFERENCES `empresa` (`nit`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
