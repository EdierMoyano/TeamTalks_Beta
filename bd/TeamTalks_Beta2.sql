-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2025 at 03:57 AM
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
  `id_user` int(11) DEFAULT NULL,
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
  `ficha_nom` varchar(100) DEFAULT NULL,
  `id_ambiente` int(11) DEFAULT NULL,
  `fecha_creac` date DEFAULT NULL,
  `id_instructor` int(11) DEFAULT NULL,
  `id_jornada` int(11) DEFAULT NULL,
  `id_tipo_ficha` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `id_user` int(11) DEFAULT NULL,
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

-- --------------------------------------------------------

--
-- Table structure for table `licencias`
--

CREATE TABLE `licencias` (
  `id_licencia` varchar(10) NOT NULL,
  `id_tipo_licencia` int(3) NOT NULL,
  `fecha_ini` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `id_estado` int(3) NOT NULL,
  `nit` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materias`
--

CREATE TABLE `materias` (
  `id_materia` int(11) NOT NULL,
  `materia` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `materia_ficha`
--

CREATE TABLE `materia_ficha` (
  `id_materia_ficha` int(11) NOT NULL,
  `id_materia` int(11) DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `id_instructor` int(11) DEFAULT NULL,
  `id_trimestre` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recuperacion`
--

CREATE TABLE `recuperacion` (
  `id_recuperacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
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
  `id_user` int(11) DEFAULT NULL
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
  `id_user` int(11) DEFAULT NULL
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

-- --------------------------------------------------------

--
-- Table structure for table `user_ficha`
--

CREATE TABLE `user_ficha` (
  `id_user_ficha` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `fecha_asig` date DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `id_tipo` int(11) DEFAULT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contraseña` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `fecha_registro` date DEFAULT NULL,
  `nit` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `usuarios`
--

INSERT INTO `usuarios` (`id`, `id_tipo`, `nombres`, `apellidos`, `correo`, `contraseña`, `avatar`, `telefono`, `id_rol`, `id_estado`, `id_ficha`, `fecha_registro`, `nit`) VALUES
(1104940105, 1, 'Edier\r\n', 'Moyano', 'ediersmb@gmail.com', '$2y$12$Z8XHAwYyhkcYU8LCwUCu3.Ff3LHBigWUDlOjF7wRlyFb6wmQYfply', NULL, '3028623064', 2, 1, NULL, NULL, 0);

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
  ADD KEY `id_estado` (`id_estado`);

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
  ADD KEY `id_tipo_licencia` (`id_tipo_licencia`),
  ADD KEY `id_estado` (`id_estado`);

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
  ADD KEY `id_usuario` (`id_usuario`),
  ADD KEY `idx_token` (`token`);

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
  ADD CONSTRAINT `fichas_ibfk_5` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);

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
  ADD CONSTRAINT `licencias_ibfk_2` FOREIGN KEY (`id_tipo_licencia`) REFERENCES `tipo_licencia` (`id_tipo_licencia`),
  ADD CONSTRAINT `licencias_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_estado`);

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
