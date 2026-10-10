<?php
require_once __DIR__ . '/db.php';
$pdo = conectar();

// Acciones POST: cambiar estado / eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $accion = $_POST['accion'] ?? '';
    if ($id > 0) {
        if ($accion === 'estado' && in_array($_POST['estado'] ?? '', ['preparando', 'despachado'], true)) {
            $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$_POST['estado'], $id]);
        } elseif ($accion === 'eliminar') {
            $pdo->prepare("DELETE FROM pedidos WHERE id = ?")->execute([$id]);
        }
    }
    header('Location: index.php' . (isset($_GET['f']) ? '?f=' . urlencode($_GET['f']) : ''));
    exit;
}

$filtro = $_GET['f'] ?? '';
$where = in_array($filtro, ['preparando', 'despachado'], true) ? "WHERE p.estado = " . $pdo->quote($filtro) : '';

$pedidos = $pdo->query("
    SELECT p.id, p.direccion, p.estado, p.creado_en,
           SUM(CASE WHEN h.nombre='Sencilla' THEN d.cantidad ELSE 0 END) AS sencilla,
           SUM(CASE WHEN h.nombre='Doble'    THEN d.cantidad ELSE 0 END) AS doble,
           SUM(CASE WHEN h.nombre='Mixta'    THEN d.cantidad ELSE 0 END) AS mixta,
           COALESCE(SUM(d.cantidad * h.precio),0) AS total
    FROM pedidos p
    LEFT JOIN pedido_detalle d ON d.pedido_id = p.id
    LEFT JOIN hamburguesas h ON h.id = d.hamburguesa_id
    $where
    GROUP BY p.id ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hamburguesería - Pedidos</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>🍔 Hamburguesería</h1></header>
<main>
  <div class="barra">
    <nav class="filtros">
      <a href="index.php" class="<?= $filtro === '' ? 'on' : '' ?>">Todos</a>
      <a href="index.php?f=preparando" class="<?= $filtro === 'preparando' ? 'on' : '' ?>">Preparando</a>
      <a href="index.php?f=despachado" class="<?= $filtro === 'despachado' ? 'on' : '' ?>">Despachados</a>
    </nav>
    <a class="btn" href="form.php">+ Nuevo pedido</a>
  </div>

  <div class="tabla-wrap">
  <table>
    <thead>
      <tr><th>#</th><th>Dirección</th><th>Sencilla</th><th>Doble</th><th>Mixta</th><th>Total</th><th>Estado</th><th>Acciones</th></tr>
    </thead>
    <tbody>
    <?php if (!$pedidos): ?>
      <tr><td colspan="8" class="vacio">No hay pedidos.</td></tr>
    <?php endif; ?>
    <?php foreach ($pedidos as $p): ?>
      <tr>
        <td><?= (int)$p['id'] ?></td>
        <td><?= e($p['direccion']) ?><br><small><?= e($p['creado_en']) ?></small></td>
        <td><?= (int)$p['sencilla'] ?></td>
        <td><?= (int)$p['doble'] ?></td>
        <td><?= (int)$p['mixta'] ?></td>
        <td>$<?= number_format((float)$p['total'], 0, ',', '.') ?></td>
        <td><span class="badge <?= e($p['estado']) ?>"><?= e(ucfirst($p['estado'])) ?></span></td>
        <td class="acciones">
          <form method="post">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="accion" value="estado">
            <input type="hidden" name="estado" value="<?= $p['estado'] === 'preparando' ? 'despachado' : 'preparando' ?>">
            <button class="mini"><?= $p['estado'] === 'preparando' ? 'Despachar' : 'Volver a preparar' ?></button>
          </form>
          <a class="mini" href="form.php?id=<?= (int)$p['id'] ?>">Editar</a>
          <form method="post" onsubmit="return confirm('¿Eliminar el pedido #<?= (int)$p['id'] ?>?')">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="accion" value="eliminar">
            <button class="mini peligro">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</main>
</body>
</html>
