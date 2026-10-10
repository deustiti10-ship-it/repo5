<?php
require_once __DIR__ . '/db.php';
$pdo = conectar();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$hamb = $pdo->query("SELECT id, nombre, precio FROM hamburguesas ORDER BY precio")->fetchAll();
$errores = [];

$pedido = ['direccion' => '', 'estado' => 'preparando'];
$cant = [];
foreach ($hamb as $h) $cant[$h['id']] = 0;

if ($id > 0) {
    $st = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $st->execute([$id]);
    $pedido = $st->fetch();
    if (!$pedido) { header('Location: index.php'); exit; }
    $st = $pdo->prepare("SELECT hamburguesa_id, cantidad FROM pedido_detalle WHERE pedido_id = ?");
    $st->execute([$id]);
    foreach ($st->fetchAll() as $r) $cant[$r['hamburguesa_id']] = (int)$r['cantidad'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pedido['direccion'] = trim($_POST['direccion'] ?? '');
    $pedido['estado'] = in_array($_POST['estado'] ?? '', ['preparando', 'despachado'], true) ? $_POST['estado'] : 'preparando';
    $total = 0;
    foreach ($hamb as $h) {
        $c = max(0, (int)($_POST['cant'][$h['id']] ?? 0));
        $cant[$h['id']] = $c;
        $total += $c;
    }
    if ($pedido['direccion'] === '') $errores[] = 'La dirección es obligatoria.';
    if (mb_strlen($pedido['direccion']) > 255) $errores[] = 'La dirección es demasiado larga.';
    if ($total === 0) $errores[] = 'Debes pedir al menos una hamburguesa.';

    if (!$errores) {
        $pdo->beginTransaction();
        try {
            if ($id > 0) {
                $pdo->prepare("UPDATE pedidos SET direccion = ?, estado = ? WHERE id = ?")
                    ->execute([$pedido['direccion'], $pedido['estado'], $id]);
            } else {
                $pdo->prepare("INSERT INTO pedidos (direccion, estado) VALUES (?, ?)")
                    ->execute([$pedido['direccion'], $pedido['estado']]);
                $id = (int)$pdo->lastInsertId();
            }
            $up = $pdo->prepare("INSERT INTO pedido_detalle (pedido_id, hamburguesa_id, cantidad) VALUES (?, ?, ?)
                                 ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)");
            foreach ($cant as $hid => $c) $up->execute([$id, $hid, $c]);
            $pdo->commit();
            header('Location: index.php');
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $errores[] = 'Error al guardar: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $id ? 'Editar' : 'Nuevo' ?> pedido</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>🍔 Hamburguesería</h1></header>
<main class="angosto">
  <h2><?= $id ? "Editar pedido #$id" : 'Nuevo pedido' ?></h2>

  <?php foreach ($errores as $err): ?>
    <div class="error"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post" class="card">
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <label>Dirección
      <input type="text" name="direccion" maxlength="255" required value="<?= e($pedido['direccion']) ?>">
    </label>

    <label>Estado
      <select name="estado">
        <option value="preparando" <?= $pedido['estado'] === 'preparando' ? 'selected' : '' ?>>Preparando</option>
        <option value="despachado" <?= $pedido['estado'] === 'despachado' ? 'selected' : '' ?>>Despachado</option>
      </select>
    </label>

    <fieldset>
      <legend>Cantidad por hamburguesa</legend>
      <?php foreach ($hamb as $h): ?>
        <label class="fila">
          <span><?= e($h['nombre']) ?> <small>($<?= number_format((float)$h['precio'], 0, ',', '.') ?>)</small></span>
          <input type="number" min="0" name="cant[<?= (int)$h['id'] ?>]" value="<?= (int)$cant[$h['id']] ?>">
        </label>
      <?php endforeach; ?>
    </fieldset>

    <div class="botones">
      <button class="btn">Guardar</button>
      <a class="btn sec" href="index.php">Cancelar</a>
    </div>
  </form>
</main>
</body>
</html>
