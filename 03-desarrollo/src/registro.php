<?php
session_start();
require_once 'conexion.php';

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $correo   = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($nombre) && !empty($correo) && !empty($password)) {
        // Encriptar contraseña para no guardarla en texto plano
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $rol = 'cliente';

        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nombre, $correo, $password_hash, $rol);

        if ($stmt->execute()) {
            header('Location: login.php?registro=exito');
            exit;
        } else {
            $mensaje = "El correo ya está registrado o hubo un error.";
        }
    } else {
        $mensaje = "Por favor completa todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - NovaMarket</title>
    <style>
        body { font-family: Arial, sans-serif; background: #eef4f8; display: grid; place-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 320px; }
        input { width: 100%; padding: 10px; margin: 8px 0 16px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #246bce; color: white; border: 0; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .error { color: red; font-size: 13px; margin-bottom: 10px; }
        a { color: #246bce; text-decoration: none; font-size: 13px; display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Crear Cuenta</h2>
        <?php if ($mensaje): ?><div class="error"><?php echo htmlspecialchars($mensaje); ?></div><?php endif; ?>
        <form method="POST">
            <label>Nombre completo:</label>
            <input type="text" name="nombre" required>
            
            <label>Correo electrónico:</label>
            <input type="email" name="correo" required>
            
            <label>Contraseña:</label>
            <input type="password" name="password" required>
            
            <button type="submit">Registrarse</button>
        </form>
        <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
    </div>
</body>
</html>