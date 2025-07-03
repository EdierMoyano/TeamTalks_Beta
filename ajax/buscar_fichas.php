<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

$id_instructor = (int)$_SESSION['documento'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Get total stats
$stats_sql = "
    SELECT 
        COUNT(DISTINCT f.id_ficha) as total_fichas,
        COUNT(DISTINCT uf.id_user) as total_aprendices
    FROM fichas f
    LEFT JOIN user_ficha uf ON f.id_ficha = uf.id_ficha
    WHERE f.id_instructor = :id
";
$stats_stmt = $conex->prepare($stats_sql);
$stats_stmt->execute(['id' => $id_instructor]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

if ($q === '') {
    // Query with pagination
    $sql = "
        SELECT f.id_ficha, fo.nombre AS nombre_formacion, tf.tipo_ficha, tfo.tipo_formacion
        FROM fichas f
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
        JOIN tipo_formacion tfo ON fo.id_tipo_formacion = tfo.id_tipo_formacion
        WHERE f.id_instructor = :id
        ORDER BY f.id_ficha ASC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $conex->prepare($sql);
    $stmt->execute(['id' => $id_instructor]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total for pagination
    $total = $conex->prepare("SELECT COUNT(*) FROM fichas WHERE id_instructor = :id");
    $total->execute(['id' => $id_instructor]);
    $total_pages = ceil($total->fetchColumn() / $limit);
    $total_fichas_query = $total->fetchColumn();
} else {
    // Search by ficha number or formation name (no pagination)
    $sql = "
        SELECT f.id_ficha, fo.nombre AS nombre_formacion, tf.tipo_ficha, tfo.tipo_formacion
        FROM fichas f
        JOIN formacion fo ON f.id_formacion = fo.id_formacion
        JOIN tipo_ficha tf ON f.id_tipo_ficha = tf.id_tipo_ficha
        JOIN tipo_formacion tfo ON fo.id_tipo_formacion = tfo.id_tipo_formacion
        WHERE f.id_instructor = :id
        AND (CAST(f.id_ficha AS CHAR) LIKE :q1 OR fo.nombre LIKE :q2)
        ORDER BY f.id_ficha ASC
    ";
    $stmt = $conex->prepare($sql);
    $stmt->execute([
        'id' => $id_instructor,
        'q1' => "%$q%",
        'q2' => "%$q%"
    ]);
    $fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_pages = 0;
    $total_fichas_query = count($fichas);
}

// Generate HTML
$html = '';

if (count($fichas) > 0) {
    foreach ($fichas as $ficha) {
        $html .= '
        <div class="ficha-card">
            <div class="ficha-header">
                <div class="ficha-icon">
                    <i class="bi bi-journal-code"></i>
                </div>
                <div class="ficha-info">
                    <h3 class="ficha-number">Ficha ' . htmlspecialchars($ficha['id_ficha']) . '</h3>
                    <p class="ficha-type">' . htmlspecialchars($ficha['tipo_ficha']) . ' • ' . htmlspecialchars($ficha['tipo_formacion']) . '</p>
                </div>
            </div>
            
            <div class="ficha-content">
                <div class="ficha-formation">
                    <div class="formation-label">Programa de Formación</div>
                    <p class="formation-name">' . htmlspecialchars($ficha['nombre_formacion']) . '</p>
                </div>
            </div>
            
            <div class="ficha-actions">
                <button class="btn-modern btn-outline-modern btn-detalles" data-id="' . $ficha['id_ficha'] . '">
                    <i class="bi bi-info-circle"></i>
                    Detalles
                </button>
                <a href="../mod/ver_aprendices.php?id_ficha=' . $ficha['id_ficha'] . '" class="btn-modern btn-primary-modern">
                    <i class="bi bi-people"></i>
                    Aprendices
                </a>
            </div>
        </div>';
    }

    // Add pagination if needed
    if ($q === '' && $total_pages > 1) {
        $html .= '<div class="pagination-container">
            <div class="pagination-modern">';
        
        for ($i = 1; $i <= $total_pages; $i++) {
            $activeClass = ($i == $page) ? 'active' : '';
            $html .= '<a class="page-btn ' . $activeClass . '" href="#" data-page="' . $i . '">' . $i . '</a>';
        }
        
        $html .= '</div></div>';
    }
} else {
    $html = '
    <div class="empty-state">
        <i class="bi bi-folder-x empty-icon"></i>
        <h3 class="empty-title">No se encontraron fichas</h3>
        <p class="empty-description">' . 
        ($q ? 'No hay fichas que coincidan con "' . htmlspecialchars($q) . '"' : 'No tienes fichas asignadas') . 
        '</p>
    </div>';
}

// Generate count text
$count_text = '';


// Return JSON response
echo json_encode([
    'html' => $html,
    'count_text' => $count_text,
    'total_fichas' => $stats['total_fichas'],
    'total_aprendices' => $stats['total_aprendices']
]);
?>