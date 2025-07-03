<?php
/**
 * Funciones para validación visual de notas de actividades
 * Archivo separado para mejor organización y mantenimiento
 */

/**
 * Función para determinar la clase CSS según la nota
 * @param float $nota La nota asignada por el instructor
 * @return string La clase CSS correspondiente
 */
function obtenerClaseNotaActividad($nota) {
    if ($nota === null || $nota === '') {
        return 'sin-nota';
    }
    
    $nota = floatval($nota);
    
    if ($nota >= 1.0 && $nota <= 2.9) {
        return 'nota-roja';
    } elseif ($nota >= 3.0 && $nota <= 3.9) {
        return 'nota-amarilla';
    } elseif ($nota >= 4.0 && $nota <= 5.0) {
        return 'nota-verde';
    }
    
    return 'sin-nota';
}

/**
 * Función para obtener el color de fondo según la nota
 * @param float $nota La nota asignada por el instructor
 * @return string El color hexadecimal
 */
function obtenerColorNota($nota) {
    if ($nota === null || $nota === '') {
        return '#f8fff8'; // Color por defecto (verde claro)
    }
    
    $nota = floatval($nota);
    
    if ($nota >= 1.0 && $nota <= 2.9) {
        return '#fff5f5'; // Rojo claro
    } elseif ($nota >= 3.0 && $nota <= 3.9) {
        return '#fffbeb'; // Amarillo claro
    } elseif ($nota >= 4.0 && $nota <= 5.0) {
        return '#f0fdf4'; // Verde claro
    }
    
    return '#f8fff8'; // Color por defecto
}

/**
 * Función para obtener el color del borde según la nota
 * @param float $nota La nota asignada por el instructor
 * @return string El color hexadecimal del borde
 */
function obtenerColorBordeNota($nota) {
    if ($nota === null || $nota === '') {
        return '#28a745'; // Verde por defecto
    }
    
    $nota = floatval($nota);
    
    if ($nota >= 1.0 && $nota <= 2.9) {
        return '#dc3545'; // Rojo
    } elseif ($nota >= 3.0 && $nota <= 3.9) {
        return '#fbbf24'; // Amarillo
    } elseif ($nota >= 4.0 && $nota <= 5.0) {
        return '#10b981'; // Verde
    }
    
    return '#28a745'; // Verde por defecto
}

/**
 * Función para obtener estadísticas de notas por rangos
 * @param array $actividades Array de actividades con notas
 * @return array Estadísticas organizadas por rangos de notas
 */
function obtenerEstadisticasNotas($actividades) {
    $estadisticas = [
        'total' => count($actividades),
        'rojas' => 0,
        'amarillas' => 0,
        'verdes' => 0,
        'sin_nota' => 0
    ];
    
    foreach ($actividades as $actividad) {
        $clase = obtenerClaseNotaActividad($actividad['nota']);
        
        switch ($clase) {
            case 'nota-roja':
                $estadisticas['rojas']++;
                break;
            case 'nota-amarilla':
                $estadisticas['amarillas']++;
                break;
            case 'nota-verde':
                $estadisticas['verdes']++;
                break;
            default:
                $estadisticas['sin_nota']++;
                break;
        }
    }
    
    return $estadisticas;
}

/**
 * Función para generar un reporte de rendimiento
 * @param array $actividades Array de actividades con notas
 * @return array Reporte detallado de rendimiento
 */
function generarReporteRendimiento($actividades) {
    $estadisticas = obtenerEstadisticasNotas($actividades);
    $total = $estadisticas['total'];
    
    if ($total === 0) {
        return [
            'mensaje' => 'No hay actividades para evaluar',
            'porcentajes' => [],
            'recomendacion' => 'Completa algunas actividades para ver tu rendimiento'
        ];
    }
    
    $porcentajes = [
        'excelente' => round(($estadisticas['verdes'] / $total) * 100, 1),
        'bueno' => round(($estadisticas['amarillas'] / $total) * 100, 1),
        'necesita_mejora' => round(($estadisticas['rojas'] / $total) * 100, 1),
        'sin_calificar' => round(($estadisticas['sin_nota'] / $total) * 100, 1)
    ];
    
    // Determinar recomendación
    $recomendacion = '';
    if ($porcentajes['excelente'] >= 70) {
        $recomendacion = '¡Excelente trabajo! Mantén este nivel de rendimiento.';
    } elseif ($porcentajes['bueno'] >= 50) {
        $recomendacion = 'Buen rendimiento. Intenta mejorar en las áreas que necesitan atención.';
    } elseif ($porcentajes['necesita_mejora'] >= 50) {
        $recomendacion = 'Hay oportunidades de mejora. Considera revisar los temas con calificaciones bajas.';
    } else {
        $recomendacion = 'Sigue trabajando. Cada actividad completada es un paso hacia el éxito.';
    }
    
    return [
        'estadisticas' => $estadisticas,
        'porcentajes' => $porcentajes,
        'recomendacion' => $recomendacion
    ];
}
?>
