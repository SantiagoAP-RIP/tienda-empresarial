<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

if (empty($_SESSION['id_usuario'])) {
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para realizar un pedido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$carrito = $data['carrito'] ?? [];

if (!is_array($carrito) || empty($carrito)) {
    echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío.']);
    exit;
}

$id_usuario = (int) $_SESSION['id_usuario'];
$total_pedido = 0.0;

$conexion->begin_transaction();

try {
    foreach ($carrito as $item) {
        if (!isset($item['id_producto'], $item['cantidad'])) {
            throw new Exception('Datos del producto incompletos.');
        }

        $id_prod = (int) $item['id_producto'];
        $cant = (int) $item['cantidad'];

        if ($cant <= 0) {
            throw new Exception('La cantidad debe ser mayor a cero.');
        }

        $stmt = $conexion->prepare("SELECT stock, precio, nombre FROM productos WHERE id_producto = ? AND estado = 'activo'");
        $stmt->bind_param('i', $id_prod);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();

        if (!$prod) {
            throw new Exception('El producto seleccionado ya no está disponible.');
        }

        if ($cant > (int) $prod['stock']) {
            throw new Exception('Stock insuficiente para: ' . $prod['nombre'] . '. Disponible: ' . $prod['stock']);
        }

        $total_pedido += (float) $prod['precio'] * $cant;
    }

    $stmtPedido = $conexion->prepare("INSERT INTO pedidos (id_usuario, total, estado) VALUES (?, ?, 'pendiente')");
    $stmtPedido->bind_param('id', $id_usuario, $total_pedido);
    $stmtPedido->execute();
    $id_pedido = (int) $stmtPedido->insert_id;

    $stmtDetalle = $conexion->prepare("INSERT INTO detalle_pedidos (id_pedido, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
    $stmtStock = $conexion->prepare("UPDATE productos SET stock = stock - ? WHERE id_producto = ?");

    foreach ($carrito as $item) {
        $id_prod = (int) $item['id_producto'];
        $cant = (int) $item['cantidad'];

        $stmtPrecio = $conexion->prepare("SELECT precio FROM productos WHERE id_producto = ? AND estado = 'activo'");
        $stmtPrecio->bind_param('i', $id_prod);
        $stmtPrecio->execute();
        $precio_u = $stmtPrecio->get_result()->fetch_assoc()['precio'] ?? null;

        if ($precio_u === null) {
            throw new Exception('No se pudo obtener el precio del producto.');
        }

        $stmtDetalle->bind_param('iiid', $id_pedido, $id_prod, $cant, $precio_u);
        $stmtDetalle->execute();

        $stmtStock->bind_param('ii', $cant, $id_prod);
        $stmtStock->execute();
    }

    $conexion->commit();
    echo json_encode(['status' => 'success', 'message' => '¡Pedido realizado con éxito!', 'id_pedido' => $id_pedido]);
    exit;
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
?>