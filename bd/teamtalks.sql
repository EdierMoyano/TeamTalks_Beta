-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-05-2025 a las 13:29:31
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
-- Base de datos: `teamtalks`
--

DELIMITER $$
--
-- Procedimientos
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
-- Estructura de tabla para la tabla `clases`
--

CREATE TABLE `clases` (
  `Id_clase` int(11) NOT NULL,
  `id_ficha` int(11) DEFAULT NULL,
  `Nom_clase` varchar(255) DEFAULT NULL,
  `Trimestre` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clase_horario`
--

CREATE TABLE `clase_horario` (
  `id_clase_horario` int(11) NOT NULL,
  `id_clase` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_usuarios_fichas`
--

CREATE TABLE `detalle_usuarios_fichas` (
  `id_usuario_ficha` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `fecha_asignacion` datetime DEFAULT current_timestamp(),
  `estado_alumno` varchar(255) DEFAULT NULL,
  `id_ficha` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresa`
--

CREATE TABLE `empresa` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `empresa`
--

INSERT INTO `empresa` (`id`, `nombre`) VALUES
(1, 'Desarrolladores'),
(2, 'TeamTalks'),
(3, 'AgroStock');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `Id_estado` int(11) NOT NULL,
  `Tipo_estado` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado_tarea`
--

CREATE TABLE `estado_tarea` (
  `Id_tarea_estado` int(11) NOT NULL,
  `Estado_tarea` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fichas`
--

CREATE TABLE `fichas` (
  `id_ficha` int(11) NOT NULL,
  `id_formacion` int(11) DEFAULT NULL,
  `fecha_creacion` datetime DEFAULT current_timestamp(),
  `Jornada` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `formacion`
--

CREATE TABLE `formacion` (
  `id_formacion` int(11) NOT NULL,
  `Nombre` varchar(255) DEFAULT NULL,
  `id_tipo` int(11) DEFAULT NULL,
  `Jornada` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `foro`
--

CREATE TABLE `foro` (
  `Id_foro` int(11) NOT NULL,
  `Id_clase` int(11) NOT NULL,
  `Fecha_foro` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id_horario` int(11) NOT NULL,
  `dia_semana` tinyint(4) NOT NULL COMMENT '1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo',
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `licencias`
--

CREATE TABLE `licencias` (
  `id` int(11) NOT NULL,
  `codigo_licencia` varchar(20) NOT NULL,
  `id_tipo_licencia` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `estado` enum('Activa','Inactiva','Expirada') DEFAULT 'Activa',
  `id_empresa` int(11) NOT NULL,
  `fecha_compra` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `licencias`
--
DELIMITER $$
CREATE TRIGGER `actualizar_estado_licencia` BEFORE UPDATE ON `licencias` FOR EACH ROW BEGIN
    -- Si la fecha de fin es anterior a la fecha actual, marcar como expirada
    IF NEW.fecha_fin < CURDATE() AND NEW.estado = 'Activa' THEN
        SET NEW.estado = 'Expirada';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `calcular_fecha_fin` BEFORE INSERT ON `licencias` FOR EACH ROW BEGIN
    DECLARE dias INT;
    DECLARE codigo_aleatorio VARCHAR(20);
    
    -- Generar un código único aleatorio de 20 caracteres
    SET codigo_aleatorio = CONCAT(
        SUBSTRING(MD5(RAND()), 1, 10),
        SUBSTRING(MD5(UNIX_TIMESTAMP()), 1, 10)
    );
    
    SET NEW.codigo_licencia = codigo_aleatorio;
    
    -- Obtener la duración en días del tipo de licencia
    SELECT duracion_dias INTO dias FROM tipo_licencia WHERE id = NEW.id_tipo_licencia;
    
    -- Calcular la fecha de fin
    SET NEW.fecha_fin = DATE_ADD(NEW.fecha_inicio, INTERVAL dias DAY);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `Id_nota` int(11) NOT NULL,
  `Id_tarea_user` int(11) NOT NULL,
  `Nota` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuestas`
--

CREATE TABLE `respuestas` (
  `Id_respuestas` int(11) NOT NULL,
  `Contenido` varchar(255) NOT NULL,
  `Fecha_resp` datetime NOT NULL,
  `Id_tema` int(11) DEFAULT NULL,
  `Id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `respuesta_tarea`
--

CREATE TABLE `respuesta_tarea` (
  `Id_Respuesta` int(11) NOT NULL,
  `Contenido` varchar(255) DEFAULT NULL,
  `Archivo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `Id_rol` int(11) NOT NULL,
  `Tipo_rol` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`Id_rol`, `Tipo_rol`) VALUES
(1, 'S_Admin'),
(2, 'Administrador'),
(3, 'Instructor'),
(4, 'Aprendiz');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas`
--

CREATE TABLE `tareas` (
  `Id_tarea` int(11) NOT NULL,
  `Titulo_tarea` varchar(255) NOT NULL,
  `Desc_tarea` varchar(255) DEFAULT NULL,
  `Archivo_tarea` varchar(255) DEFAULT NULL,
  `Fecha_entreg` datetime NOT NULL,
  `id_clase` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tareas_user`
--

CREATE TABLE `tareas_user` (
  `Id_tarea_user` int(11) NOT NULL,
  `Id_tarea` int(11) NOT NULL,
  `Id_tarea_estado` int(11) NOT NULL,
  `Id_Respuesta` int(11) DEFAULT NULL,
  `Fecha_subido` datetime NOT NULL,
  `id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tema_foro`
--

CREATE TABLE `tema_foro` (
  `Id_tema` int(11) NOT NULL,
  `Titulo` varchar(255) DEFAULT NULL,
  `Contenido` varchar(255) DEFAULT NULL,
  `Fecha_tema` datetime DEFAULT NULL,
  `Id_foro` int(11) DEFAULT NULL,
  `Id_user` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_docu`
--

CREATE TABLE `tipo_docu` (
  `id_docu` int(11) NOT NULL,
  `docu` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_formacion`
--

CREATE TABLE `tipo_formacion` (
  `id_tipo` int(11) NOT NULL,
  `tipo_formacion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_licencia`
--

CREATE TABLE `tipo_licencia` (
  `id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `duracion_dias` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tipo_licencia`
--

INSERT INTO `tipo_licencia` (`id`, `nombre`, `descripcion`, `duracion_dias`) VALUES
(1, 'Demo', 'Versión de prueba limitada', 3),
(2, 'Freeware', 'Software gratuito con funcionalidades básicas', 365),
(3, 'Shareware', 'Software de prueba con funcionalidades completas', 30),
(4, 'Anual', 'Licencia completa por un año', 365),
(5, 'Semestral', 'Licencia completa por seis meses', 182);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `Id_user` int(11) NOT NULL,
  `Nombres` varchar(255) NOT NULL,
  `Correo` varchar(255) NOT NULL,
  `Contrasena` varchar(255) NOT NULL,
  `Avatar` varchar(255) DEFAULT NULL,
  `Telefono` bigint(20) DEFAULT NULL,
  `Id_rol` int(11) NOT NULL,
  `Id_estado` int(11) NOT NULL,
  `id_docu` int(11) NOT NULL,
  `fecha_registro` datetime DEFAULT current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expira` datetime DEFAULT NULL,
  `id_empresa` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `clases`
--
ALTER TABLE `clases`
  ADD PRIMARY KEY (`Id_clase`),
  ADD KEY `fk_clases_id_ficha` (`id_ficha`);

--
-- Indices de la tabla `clase_horario`
--
ALTER TABLE `clase_horario`
  ADD PRIMARY KEY (`id_clase_horario`),
  ADD KEY `fk_clase_horario_id_clase_idx` (`id_clase`),
  ADD KEY `fk_clase_horario_id_horario_idx` (`id_horario`);

--
-- Indices de la tabla `detalle_usuarios_fichas`
--
ALTER TABLE `detalle_usuarios_fichas`
  ADD PRIMARY KEY (`id_usuario_ficha`),
  ADD KEY `fk_detalle_usuarios_fichas_id_ficha` (`id_ficha`),
  ADD KEY `fk_detalle_usuarios_fichas_id_user` (`id_user`);

--
-- Indices de la tabla `empresa`
--
ALTER TABLE `empresa`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`Id_estado`);

--
-- Indices de la tabla `estado_tarea`
--
ALTER TABLE `estado_tarea`
  ADD PRIMARY KEY (`Id_tarea_estado`);

--
-- Indices de la tabla `fichas`
--
ALTER TABLE `fichas`
  ADD PRIMARY KEY (`id_ficha`),
  ADD KEY `fk_fichas_id_formacion` (`id_formacion`);

--
-- Indices de la tabla `formacion`
--
ALTER TABLE `formacion`
  ADD PRIMARY KEY (`id_formacion`),
  ADD KEY `id_tipo` (`id_tipo`);

--
-- Indices de la tabla `foro`
--
ALTER TABLE `foro`
  ADD PRIMARY KEY (`Id_foro`),
  ADD KEY `fk_foro_Id_clase` (`Id_clase`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id_horario`);

--
-- Indices de la tabla `licencias`
--
ALTER TABLE `licencias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_licencias_id_tipo_licencia` (`id_tipo_licencia`),
  ADD KEY `fk_licencias_id_empresa` (`id_empresa`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`Id_nota`),
  ADD KEY `fk_notas_Id_tarea_user` (`Id_tarea_user`);

--
-- Indices de la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD PRIMARY KEY (`Id_respuestas`),
  ADD KEY `fk_respuestas_Id_user` (`Id_user`);

--
-- Indices de la tabla `respuesta_tarea`
--
ALTER TABLE `respuesta_tarea`
  ADD PRIMARY KEY (`Id_Respuesta`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`Id_rol`);

--
-- Indices de la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD PRIMARY KEY (`Id_tarea`),
  ADD KEY `fk_tareas_id_clase` (`id_clase`);

--
-- Indices de la tabla `tareas_user`
--
ALTER TABLE `tareas_user`
  ADD PRIMARY KEY (`Id_tarea_user`),
  ADD KEY `fk_tareas_user_Id_tarea` (`Id_tarea`),
  ADD KEY `fk_tareas_user_Id_tarea_estado` (`Id_tarea_estado`),
  ADD KEY `fk_tareas_user_Id_Respuesta` (`Id_Respuesta`);

--
-- Indices de la tabla `tema_foro`
--
ALTER TABLE `tema_foro`
  ADD PRIMARY KEY (`Id_tema`),
  ADD KEY `fk_tema_foro_Id_foro` (`Id_foro`);

--
-- Indices de la tabla `tipo_docu`
--
ALTER TABLE `tipo_docu`
  ADD PRIMARY KEY (`id_docu`);

--
-- Indices de la tabla `tipo_formacion`
--
ALTER TABLE `tipo_formacion`
  ADD PRIMARY KEY (`id_tipo`);

--
-- Indices de la tabla `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`Id_user`),
  ADD KEY `fk_usuarios_id_empresa` (`id_empresa`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `clases`
--
ALTER TABLE `clases`
  MODIFY `Id_clase` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clase_horario`
--
ALTER TABLE `clase_horario`
  MODIFY `id_clase_horario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_usuarios_fichas`
--
ALTER TABLE `detalle_usuarios_fichas`
  MODIFY `id_usuario_ficha` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `empresa`
--
ALTER TABLE `empresa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `fichas`
--
ALTER TABLE `fichas`
  MODIFY `id_ficha` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `formacion`
--
ALTER TABLE `formacion`
  MODIFY `id_formacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `licencias`
--
ALTER TABLE `licencias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tipo_licencia`
--
ALTER TABLE `tipo_licencia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `clases`
--
ALTER TABLE `clases`
  ADD CONSTRAINT `fk_clases_id_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id_ficha`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `clase_horario`
--
ALTER TABLE `clase_horario`
  ADD CONSTRAINT `fk_clase_horario_id_clase` FOREIGN KEY (`id_clase`) REFERENCES `clases` (`Id_clase`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_clase_horario_id_horario` FOREIGN KEY (`id_horario`) REFERENCES `horarios` (`id_horario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalle_usuarios_fichas`
--
ALTER TABLE `detalle_usuarios_fichas`
  ADD CONSTRAINT `fk_detalle_usuarios_fichas_id_ficha` FOREIGN KEY (`id_ficha`) REFERENCES `fichas` (`id_ficha`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_usuarios_fichas_id_user` FOREIGN KEY (`id_user`) REFERENCES `usuarios` (`Id_user`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `fichas`
--
ALTER TABLE `fichas`
  ADD CONSTRAINT `fk_fichas_id_formacion` FOREIGN KEY (`id_formacion`) REFERENCES `formacion` (`id_formacion`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `formacion`
--
ALTER TABLE `formacion`
  ADD CONSTRAINT `formacion_ibfk_1` FOREIGN KEY (`id_tipo`) REFERENCES `tipo_formacion` (`id_tipo`);

--
-- Filtros para la tabla `foro`
--
ALTER TABLE `foro`
  ADD CONSTRAINT `fk_foro_Id_clase` FOREIGN KEY (`Id_clase`) REFERENCES `clases` (`Id_clase`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `licencias`
--
ALTER TABLE `licencias`
  ADD CONSTRAINT `fk_licencias_id_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_licencias_id_tipo_licencia` FOREIGN KEY (`id_tipo_licencia`) REFERENCES `tipo_licencia` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `fk_notas_Id_tarea_user` FOREIGN KEY (`Id_tarea_user`) REFERENCES `tareas_user` (`Id_tarea_user`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `respuestas`
--
ALTER TABLE `respuestas`
  ADD CONSTRAINT `fk_respuestas_Id_user` FOREIGN KEY (`Id_user`) REFERENCES `usuarios` (`Id_user`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tareas`
--
ALTER TABLE `tareas`
  ADD CONSTRAINT `fk_tareas_id_clase` FOREIGN KEY (`id_clase`) REFERENCES `clases` (`Id_clase`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tareas_user`
--
ALTER TABLE `tareas_user`
  ADD CONSTRAINT `fk_tareas_user_Id_Respuesta` FOREIGN KEY (`Id_Respuesta`) REFERENCES `respuesta_tarea` (`Id_Respuesta`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tareas_user_Id_tarea` FOREIGN KEY (`Id_tarea`) REFERENCES `tareas` (`Id_tarea`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tareas_user_Id_tarea_estado` FOREIGN KEY (`Id_tarea_estado`) REFERENCES `estado_tarea` (`Id_tarea_estado`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `tema_foro`
--
ALTER TABLE `tema_foro`
  ADD CONSTRAINT `fk_tema_foro_Id_foro` FOREIGN KEY (`Id_foro`) REFERENCES `foro` (`Id_foro`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_id_empresa` FOREIGN KEY (`id_empresa`) REFERENCES `empresa` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
