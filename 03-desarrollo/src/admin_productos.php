<?php
session_start();
require_once 'conexion.php';

// Validar que el usuario sea Administrador
if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$mensaje = '';

// ACCIÓN: Crear o Editar Producto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_producto'])) {
    $id           = $_POST['id_producto'] ?? '';
    $nombre       = trim($_POST['nombre']);
    $descripcion  = trim($_POST['descripcion']);
    $precio       = floatval($_POST['precio']);
    $stock        = intval($_POST['stock']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $id_categoria = intval($_POST['id_categoria']);

    if ($id) {
        $stmt = $conexion->prepare("UPDATE productos SET nombre=?, descripcion=?, precio=?, stock=?, stock_minimo=?, id_categoria=? WHERE id_producto=?");
        $stmt->bind_param("ssdiiii", $nombre, $descripcion, $precio, $stock, $stock_minimo, $id_categoria, $id);
        $mensaje = "Producto actualizado con éxito.";
    } else {
        $stmt = $conexion->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, stock_minimo, id_categoria) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiii", $nombre, $descripcion, $precio, $stock, $stock_minimo, $id_categoria);
        $mensaje = "Producto creado con éxito.";
    }
    $stmt->execute();
}

// ACCIÓN: Eliminar Producto
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $conexion->prepare("UPDATE productos SET estado = 'inactivo' WHERE id_producto = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: admin_productos.php');
    exit;
}

// Consultas para listados y categorías
$categorias = $conexion->query("SELECT * FROM categorias");
$productos  = $conexion->query("SELECT p.*, c.nombre_es AS categoria FROM productos p JOIN categorias c ON p.id_categoria = c.id_categoria WHERE p.estado = 'activo' ORDER BY p.id_producto DESC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Productos - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef4f8; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .alert-low { background-color: #ffcccc; color: #990000; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #246bce; color: white; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; }
        input, select, textarea { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { background: #246bce; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-danger { background: #d9534f; }
        .topbar { display: flex; justify-space-between; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <h2>Gestión de Productos e Inventario</h2>
            <a href="admin_dashboard.php">Volver al Dashboard</a>
        </div>

        <?php if ($mensaje): ?><p style="color: green;"><?php echo $mensaje; ?></p><?php endif; ?>

        <!-- Formulario CRUD -->
        <h3>Agregar / Editar Producto</h3>
        <form method="POST">
            <input type="hidden" name="id_producto" id="id_producto">
            <div class="form-group">
                <label>Nombre:</label>
                <input type="text" name="nombre" id="nombre" required>
            </div>
            <div class="form-group">
                <label>Categoría:</label>
                <select name="id_categoria" id="id_categoria" required>
                    <?php while($cat = $categorias->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>"><?php echo $cat['nombre_es']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Descripción:</label>
                <textarea name="descripcion" id="descripcion" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Precio ($):</label>
                <input type="number" step="0.01" name="precio" id="precio" required>
            </div>
            <div class="form-group">
                <label>Stock Disponible:</label>
                <input type="number" name="stock" id="stock" required>
            </div>
            <div class="form-group">
                <label>Stock Mínimo (Alerta):</label>
                <input type="number" name="stock_minimo" id="stock_minimo" value="5" required>
            </div>
            <button type="submit" name="guardar_producto">Guardar Producto</button>
        </form>

        <hr>

        <!-- Tabla de Inventario -->
        <h3>Inventario de Productos</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Precio</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $productos->fetch_assoc()): ?>
                    <!-- Regla de negocio: Alerta visual si stock <= stock_minimo -->
                    <tr class="<?php echo ($p['stock'] <= $p['stock_minimo']) ? 'alert-low' : ''; ?>">
                        <td><?php echo $p['id_producto']; ?></td>
                        <td><?php echo htmlspecialchars($p['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                        <td>$<?php echo number_format($p['precio'], 2); ?></td>
                        <td>
                            <?php echo $p['stock']; ?>
                            <?php if ($p['stock'] <= $p['stock_minimo']): ?>
                                ⚠️ <strong>¡Stock Bajo!</strong>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick='editar(<?php echo json_encode($p); ?>)'>Editar</button>
                            <a href="admin_productos.php?eliminar=<?php echo $p['id_producto']; ?>" onclick="return confirm('¿Deseas eliminar este producto?')"><button class="btn-danger">Eliminar</button></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    function editar(p) {
        document.getElementById('id_producto').value = p.id_producto;
        document.getElementById('nombre').value = p.nombre;
        document.getElementById('id_categoria').value = p.id_categoria;
        document.getElementById('descripcion').value = p.descripcion;
        document.getElementById('precio').value = p.precio;
        document.getElementById('stock').value = p.stock;
        document.getElementById('stock_minimo').value = p.stock_minimo;
    }
    </script>
</body>
</html>