<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

$query = "SELECT p.id_pedido, p.fecha_pedido, p.total, p.estado, 
                 GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') AS productos
          FROM pedidos p
          JOIN detalle_pedidos dp ON p.id_pedido = dp.id_pedido
          JOIN productos pr ON dp.id_producto = pr.id_producto
          WHERE p.id_usuario = ?
          GROUP BY p.id_pedido
          ORDER BY p.fecha_pedido DESC";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$pedidos = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos - NovaMarket</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef4f8; margin: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; max-width: 900px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #246bce; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .pendiente { background: #ffeeba; color: #856404; }
        .completado { background: #d4edda; color: #155724; }
        .cancelado { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Historial de Mis Pedidos</h2>
        <a href="index.php">← Volver a la Tienda</a>
        <table>
            <thead>
                <tr>
                    <th>Nº Pedido</th>
                    <th>Fecha</th>
                    <th>Productos</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pedidos->num_rows > 0): ?>
                    <?php while($p = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $p['id_pedido']; ?></td>
                            <td><?php echo $p['fecha_pedido']; ?></td>
                            <td><?php echo htmlspecialchars($p['productos']); ?></td>
                            <td>$<?php echo number_format($p['total'], 2); ?></td>
                            <td><span class="badge <?php echo $p['estado']; ?>"><?php echo ucfirst($p['estado']); ?></span></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5">Aún no has realizado ningún pedido.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>