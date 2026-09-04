<?php
session_start();
require_once 'conexion.php';

// Validar que el usuario sea Administrador
if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Acción: Actualizar estado del pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado'])) {
    $id_pedido    = intval($_POST['id_pedido']);
    $nuevo_estado = $_POST['estado'];

    $stmt = $conexion->prepare("UPDATE pedidos SET estado = ? WHERE id_pedido = ?");
    $stmt->bind_param("si", $nuevo_estado, $id_pedido);
    $stmt->execute();
}

// Métricas para el Panel Administrativo (Punto 4 del Reto)
$total_usuarios  = $conexion->query("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'cliente'")->fetch_assoc()['total'];
$total_productos = $conexion->query("SELECT COUNT(*) AS total FROM productos WHERE estado = 'activo'")->fetch_assoc()['total'];
$stock_bajo      = $conexion->query("SELECT COUNT(*) AS total FROM productos WHERE stock <= stock_minimo AND estado = 'activo'")->fetch_assoc()['total'];
$total_pedidos   = $conexion->query("SELECT COUNT(*) AS total FROM pedidos")->fetch_assoc()['total'];

// Consultar Pedidos y sus Clientes
$queryPedidos = "SELECT p.id_pedido, u.nombre AS cliente, p.fecha_pedido, p.total, p.estado,
                        GROUP_CONCAT(CONCAT(dp.cantidad, 'x ', pr.nombre) SEPARATOR ', ') AS detalle
                 FROM pedidos p
                 JOIN usuarios u ON p.id_usuario = u.id_usuario
                 JOIN detalle_pedidos dp ON p.id_pedido = dp.id_pedido
                 JOIN productos pr ON dp.id_producto = pr.id_producto
                 GROUP BY p.id_pedido
                 ORDER BY p.fecha_pedido DESC";
$pedidos = $conexion->query($queryPedidos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo - NovaMarket</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef4f8; margin: 0; padding: 25px; color: #17233b; }
        .header { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto 20px; }
        .nav-links a { margin-left: 15px; color: #246bce; font-weight: bold; text-decoration: none; }
        
        /* Tarjetas de Métricas */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; max-width: 1200px; margin: 0 auto 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #dbe4ef; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .card h3 { margin: 0; font-size: 13px; color: #647089; text-transform: uppercase; }
        .card .number { font-size: 28px; font-weight: bold; color: #246bce; margin-top: 10px; }
        .card.warning .number { color: #d9534f; }

        /* Tabla de Pedidos */
        .table-wrap { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; border: 1px solid #dbe4ef; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #e3eaf2; text-align: left; }
        th { background: #17233b; color: white; font-size: 13px; }
        select { padding: 5px; border-radius: 4px; border: 1px solid #ccc; }
        button { background: #246bce; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Panel Administrativo</h1>
        <div class="nav-links">
            <a href="admin_productos.php">📦 Gestionar Productos</a>
            <a href="logout.php" style="color: #d9534f;">Cerrar Sesión</a>
        </div>
    </div>

    <!-- 4. Métricas Clave del Negocio -->
    <div class="stats-grid">
        <div class="card">
            <h3>Clientes Registrados</h3>
            <div class="number"><?php echo $total_usuarios; ?></div>
        </div>
        <div class="card">
            <h3>Productos Activos</h3>
            <div class="number"><?php echo $total_productos; ?></div>
        </div>
        <div class="card <?php echo $stock_bajo > 0 ? 'warning' : ''; ?>">
            <h3>Stock Bajo (Alertas)</h3>
            <div class="number"><?php echo $stock_bajo; ?></div>
        </div>
        <div class="card">
            <h3>Total Pedidos</h3>
            <div class="number"><?php echo $total_pedidos; ?></div>
        </div>
    </div>

    <!-- Listado y Cambio de Estado de Pedidos -->
    <div class="table-wrap">
        <h2>Gestión de Pedidos Recientes</h2>
        <table>
            <thead>
                <tr>
                    <th>Nº</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Productos Solicitados</th>
                    <th>Total</th>
                    <th>Estado Actual</th>
                    <th>Cambiar Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($pedidos->num_rows > 0): ?>
                    <?php while($p = $pedidos->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $p['id_pedido']; ?></td>
                            <td><?php echo htmlspecialchars($p['cliente']); ?></td>
                            <td><?php echo $p['fecha_pedido']; ?></td>
                            <td><?php echo htmlspecialchars($p['detalle']); ?></td>
                            <td>$<?php echo number_format($p['total'], 2); ?></td>
                            <td><strong><?php echo ucfirst($p['estado']); ?></strong></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 5px;">
                                    <input type="hidden" name="id_pedido" value="<?php echo $p['id_pedido']; ?>">
                                    <select name="estado">
                                        <option value="pendiente" <?php echo $p['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                        <option value="completado" <?php echo $p['estado'] === 'completado' ? 'selected' : ''; ?>>Completado</option>
                                        <option value="cancelado" <?php echo $p['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                    </select>
                                    <button type="submit" name="actualizar_estado">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay pedidos registrados en el sistema.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>