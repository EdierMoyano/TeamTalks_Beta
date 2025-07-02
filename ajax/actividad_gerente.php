<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/conexion/init.php';
include 'session.php';

$id_instructor = (int)$_SESSION['documento'];

$sql = "
    SELECT DISTINCT f.id_ficha, fo.nombre AS nombre_formacion
    FROM materia_ficha mf
    JOIN fichas f ON mf.id_ficha = f.id_ficha
    JOIN formacion fo ON f.id_formacion = fo.id_formacion
    WHERE mf.id_instructor = :id
    ORDER BY f.id_ficha ASC
";

$stmt = $conex->prepare($sql);
$stmt->execute(['id' => $id_instructor]);
$fichas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
  /* ESTILOS RESPONSIVE PARA FICHAS */
  .ficha-item {
    cursor: pointer;
    border-left: 5px solid #0E4A86;
    transition: all 0.3s ease;
    margin-bottom: 0.75rem;
  }

  .ficha-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(14, 74, 134, 0.2);
  }

  .ficha-title {
    color: #0E4A86;
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .ficha-info {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
  }

  .ficha-meta {
    color: #6c757d;
    font-size: 0.8rem;
  }

  /* RESPONSIVE BREAKPOINTS PARA FICHAS */

  /* Tablets (768px - 991px) */
  @media (max-width: 991px) {
    .ficha-item {
      margin-bottom: 0.6rem;
    }

    .ficha-title {
      font-size: 1rem;
    }

    .ficha-info {
      font-size: 0.85rem;
    }

    .ficha-meta {
      font-size: 0.75rem;
    }
  }

  /* Mobile Large (576px - 767px) */
  @media (max-width: 767px) {
    .ficha-item {
      margin-bottom: 0.5rem;
      border-left-width: 3px;
    }

    .ficha-item .card-body {
      padding: 1rem;
    }

    .ficha-title {
      font-size: 0.95rem;
      margin-bottom: 0.4rem;
    }

    .ficha-info {
      font-size: 0.8rem;
      margin-bottom: 0.2rem;
    }

    .ficha-meta {
      font-size: 0.7rem;
    }

    /* Efecto hover más sutil en móviles */
    .ficha-item:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(14, 74, 134, 0.15);
    }
  }

  /* Mobile Medium (480px - 575px) */
  @media (max-width: 575px) {
    .ficha-item {
      margin-bottom: 0.4rem;
    }

    .ficha-item .card-body {
      padding: 0.75rem;
    }

    .ficha-title {
      font-size: 0.9rem;
      margin-bottom: 0.3rem;
    }

    .ficha-info {
      font-size: 0.75rem;
      margin-bottom: 0.15rem;
    }

    .ficha-meta {
      font-size: 0.65rem;
    }
  }

  /* Mobile Small (320px - 479px) */
  @media (max-width: 479px) {
    .ficha-item {
      margin-bottom: 0.3rem;
      border-left-width: 2px;
    }

    .ficha-item .card-body {
      padding: 0.5rem;
    }

    .ficha-title {
      font-size: 0.85rem;
      margin-bottom: 0.25rem;
    }

    .ficha-info {
      font-size: 0.7rem;
      margin-bottom: 0.1rem;
    }

    .ficha-meta {
      font-size: 0.6rem;
    }
  }

  /* Estado vacío responsive */
  .empty-fichas {
    text-align: center;
    color: #6c757d;
    padding: 2rem 1rem;
    font-size: 0.9rem;
  }

  @media (max-width: 767px) {
    .empty-fichas {
      padding: 1.5rem 0.5rem;
      font-size: 0.85rem;
    }
  }

  @media (max-width: 479px) {
    .empty-fichas {
      padding: 1rem 0.25rem;
      font-size: 0.8rem;
    }
  }

  /* Optimizaciones para touch */
  @media (pointer: coarse) {
    .ficha-item {
      min-height: 60px;
    }

    .ficha-item .card-body {
      min-height: 60px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
  }
</style>

<?php if (count($fichas) > 0): ?>
  <div class="row g-3">
    <?php foreach ($fichas as $f): ?>
      <div class="col-12">
        <div class="card shadow-sm ficha-item" data-id="<?= $f['id_ficha'] ?>">
          <div class="card-body">
            <h5 class="ficha-title">Formación técnica</h5>
            <p class="ficha-info"><strong>Ficha:</strong> <?= htmlspecialchars($f['id_ficha']) ?></p>
            <p class="ficha-meta">Formación: <?= htmlspecialchars($f['nombre_formacion']) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="empty-fichas">No tienes fichas asignadas.</div>
<?php endif; ?>
