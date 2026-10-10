<?php
require_once __DIR__ . '/config.php';

function conectar(): PDO
{
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    crearTablas($pdo);
    return $pdo;
}

/** Crea las tablas si no existen y siembra las 3 hamburguesas. */
function crearTablas(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS hamburguesas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL UNIQUE,
        precio DECIMAL(10,2) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        direccion VARCHAR(255) NOT NULL,
        estado ENUM('preparando','despachado') NOT NULL DEFAULT 'preparando',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pedido_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        hamburguesa_id INT NOT NULL,
        cantidad INT NOT NULL DEFAULT 0,
        UNIQUE KEY uq_pedido_hamb (pedido_id, hamburguesa_id),
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
        FOREIGN KEY (hamburguesa_id) REFERENCES hamburguesas(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ins = $pdo->prepare("INSERT IGNORE INTO hamburguesas (nombre, precio) VALUES (?, ?)");
    foreach ([['Sencilla', 12000], ['Doble', 18000], ['Mixta', 22000]] as $h) {
        $ins->execute($h);
    }
}

function e($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
