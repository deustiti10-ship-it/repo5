# Gestión de Hamburguesería (PHP + MySQL)

## Requisitos
PHP 7.4+ con extensión PDO MySQL.

## Ejecutar
    php -S localhost:8000
Abrir http://localhost:8000

Las tablas (`hamburguesas`, `pedidos`, `pedido_detalle`) se crean automáticamente
si no existen, junto con las 3 hamburguesas: Sencilla, Doble y Mixta.
Credenciales en `config.php`.

## Funciones
- Crear, editar y eliminar pedidos (dirección, estado y cantidad por tipo)
- Cambiar estado: preparando / despachado
- Filtrar por estado y ver total por pedido
