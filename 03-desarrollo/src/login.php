<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if (isset($_GET['registro']) && $_GET['registro'] === 'exito') {
    $mensaje = "¡Registro exitoso! Ya puedes iniciar sesión.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conexion->prepare("SELECT id_usuario, nombre, password, rol FROM usuarios WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($row = $resultado->fetch_assoc()) {
        $password_valida = password_verify($password, $row['password']);

        if ($password_valida) {
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $row['id_usuario'];
            $_SESSION['nombre']     = $row['nombre'];
            $_SESSION['rol']        = $row['rol'];

            if ($row['rol'] === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $mensaje = "Contraseña incorrecta.";
        }
    } else {
        $mensaje = "El correo no está registrado.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - NovaMarket</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef4f8; display: grid; place-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 320px; }
        input { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #246bce; color: white; border: 0; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .info { color: green; font-size: 13px; margin-bottom: 10px; }
        .error { color: red; font-size: 13px; margin-bottom: 10px; }
        a { color: #246bce; text-decoration: none; font-size: 13px; display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Iniciar Sesión</h2>
        <?php if ($mensaje): ?>
            <div class="<?php echo strpos($mensaje, 'exitoso') !== false ? 'info' : 'error'; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <label>Correo electrónico:</label>
            <input type="email" name="correo" required>
            
            <label>Contraseña:</label>
            <input type="password" name="password" required>
            
            <button type="submit">Ingresar</button>
        </form>
        <a href="registro.php">¿No tienes cuenta? Regístrate aquí</a>
    </div>
</body>
</html>