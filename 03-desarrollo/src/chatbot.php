<?php
session_start();
require_once 'conexion.php';

if (empty($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = (int) $_SESSION['id_usuario'];
$nombre_usuario = $_SESSION['nombre'] ?? 'Cliente';
$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensaje'])) {
    $texto = trim($_POST['mensaje']);

    if ($texto !== '') {
        $stmt = $conexion->prepare("INSERT INTO chatbot_mensajes (id_usuario, remitente, mensaje) VALUES (?, 'cliente', ?)");
        $stmt->bind_param('is', $id_usuario, $texto);
        $stmt->execute();

        $mensaje_lower = strtolower($texto);
        $respuesta = "Gracias por contactarnos. En este momento te puedo ayudar con pedidos, productos, pagos y devoluciones.";

        if (str_contains($mensaje_lower, 'hola') || str_contains($mensaje_lower, 'buenas') || str_contains($mensaje_lower, 'buenos')) {
            $respuesta = "¡Hola! Soy el asistente de NovaMarket. ¿En qué puedo ayudarte hoy?";
        } elseif (str_contains($mensaje_lower, 'pedido') || str_contains($mensaje_lower, 'estado')) {
            $respuesta = "Puedes revisar tu historial de compras en la sección 'Mis Pedidos'. Si necesitas ayuda con un pedido específico, dime el número o el nombre del producto.";
        } elseif (str_contains($mensaje_lower, 'pago') || str_contains($mensaje_lower, 'tarjeta') || str_contains($mensaje_lower, 'paypal')) {
            $respuesta = "Aceptamos pagos con tarjeta, transferencias y métodos digitales disponibles en la tienda. Si tu pago falló, te recomendamos revisar datos de la tarjeta o contactar soporte.";
        } elseif (str_contains($mensaje_lower, 'devol') || str_contains($mensaje_lower, 'cancel')) {
            $respuesta = "Las devoluciones se gestionan dentro de los primeros 7 días hábiles desde la recepción del pedido, siempre que el producto esté en condiciones adecuadas.";
        } elseif (str_contains($mensaje_lower, 'producto') || str_contains($mensaje_lower, 'stock') || str_contains($mensaje_lower, 'disponible')) {
            $respuesta = "Nuestra tienda actualiza el stock en tiempo real. Si un producto no aparece disponible, puedes intentar más tarde o consultar con soporte para disponibilidad.";
        } elseif (str_contains($mensaje_lower, 'gracias') || str_contains($mensaje_lower, 'ayuda')) {
            $respuesta = "¡Con gusto! Estamos aquí para ayudarte con compras, pedidos y soporte general.";
        } elseif (str_contains($mensaje_lower, 'horario') || str_contains($mensaje_lower, 'soporte')) {
            $respuesta = "Nuestro soporte está disponible durante el horario de atención de la tienda. Puedes escribir aquí tus dudas y responderemos lo antes posible.";
        }

        $stmtBot = $conexion->prepare("INSERT INTO chatbot_mensajes (id_usuario, remitente, mensaje) VALUES (?, 'bot', ?)");
        $stmtBot->bind_param('is', $id_usuario, $respuesta);
        $stmtBot->execute();
    }
}

$query = "SELECT remitente, mensaje, fecha_creacion FROM chatbot_mensajes WHERE id_usuario = ? ORDER BY fecha_creacion ASC";
$stmt = $conexion->prepare($query);
$stmt->bind_param('i', $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();
$historial = [];
while ($fila = $resultado->fetch_assoc()) {
    $historial[] = $fila;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chat de Soporte - NovaMarket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #eef4f8;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        .header {
            background: #17233b;
            color: white;
            padding: 18px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header a {
            color: white;
            text-decoration: none;
        }
        .chatbox {
            height: 420px;
            overflow-y: auto;
            padding: 20px;
            background: #f8fafc;
        }
        .msg {
            max-width: 70%;
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 12px;
            line-height: 1.4;
            white-space: pre-line;
        }
        .msg.user {
            margin-left: auto;
            background: #246bce;
            color: white;
        }
        .msg.bot {
            margin-right: auto;
            background: #e9edf5;
            color: #17233b;
        }
        .composer {
            display: flex;
            gap: 10px;
            padding: 15px 20px 20px;
            border-top: 1px solid #e6edf5;
        }
        .composer input {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #cdd7e5;
        }
        .composer button {
            background: #246bce;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Chat de Soporte</h2>
            <a href="index.php">Volver a la tienda</a>
        </div>

        <div class="chatbox">
            <?php foreach ($historial as $m): ?>
                <div class="msg <?= $m['remitente'] === 'cliente' ? 'user' : 'bot'; ?>">
                    <?= htmlspecialchars($m['mensaje']); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" class="composer">
            <input type="text" name="mensaje" placeholder="Escribe tu mensaje..." required>
            <button type="submit">Enviar</button>
        </form>
    </div>
</body>
</html>
