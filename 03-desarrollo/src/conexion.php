<?php
$host     = "localhost";
$usuario  = "root";
$password = "";
$db       = "tienda_empresarial";

$conexion = new mysqli($host, $usuario, $password);

if ($conexion->connect_error) {
    die("Error de conexión al servidor MySQL: " . $conexion->connect_error);
}

if (!$conexion->select_db($db)) {
    $crear_db = "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if (!$conexion->query($crear_db)) {
        die("No se pudo crear la base de datos: " . $conexion->error);
    }

    if (!$conexion->select_db($db)) {
        die("No se pudo seleccionar la base de datos: " . $conexion->error);
    }
}

$conexion->set_charset("utf8mb4");

function crear_tablas_si_faltan($conexion) {
    $sql = [];

    $sql[] = "CREATE TABLE IF NOT EXISTS categorias (
        id_categoria INT AUTO_INCREMENT PRIMARY KEY,
        nombre_es VARCHAR(100) NOT NULL,
        nombre_en VARCHAR(100) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql[] = "CREATE TABLE IF NOT EXISTS usuarios (
        id_usuario INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        correo VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        rol ENUM('admin','cliente') NOT NULL DEFAULT 'cliente'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql[] = "CREATE TABLE IF NOT EXISTS productos (
        id_producto INT AUTO_INCREMENT PRIMARY KEY,
        id_categoria INT NOT NULL,
        nombre VARCHAR(150) NOT NULL,
        descripcion TEXT,
        precio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        stock INT NOT NULL DEFAULT 0,
        stock_minimo INT NOT NULL DEFAULT 0,
        estado ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
        imagen VARCHAR(255) DEFAULT NULL,
        FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql[] = "CREATE TABLE IF NOT EXISTS pedidos (
        id_pedido INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        estado ENUM('pendiente','completado','cancelado') NOT NULL DEFAULT 'pendiente',
        fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql[] = "CREATE TABLE IF NOT EXISTS detalle_pedidos (
        id_detalle INT AUTO_INCREMENT PRIMARY KEY,
        id_pedido INT NOT NULL,
        id_producto INT NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (id_pedido) REFERENCES pedidos(id_pedido),
        FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $sql[] = "CREATE TABLE IF NOT EXISTS chatbot_mensajes (
        id_mensaje INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        remitente ENUM('cliente','bot') NOT NULL,
        mensaje TEXT NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    foreach ($sql as $query) {
        if (!$conexion->query($query)) {
            die("Error al crear tabla: " . $conexion->error);
        }
    }
}

crear_tablas_si_faltan($conexion);

function datos_iniciales($conexion) {
    $categorias = [
        ['Tecnología', 'Technology'],
        ['Hogar', 'Home'],
        ['Deportes', 'Sports'],
        ['Accesorios', 'Accessories']
    ];

    foreach ($categorias as [$nombre_es, $nombre_en]) {
        $stmt = $conexion->prepare("INSERT INTO categorias (nombre_es, nombre_en) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre_en = VALUES(nombre_en)");
        $stmt->bind_param('ss', $nombre_es, $nombre_en);
        $stmt->execute();
    }

    $admin = $conexion->query("SELECT id_usuario, correo, password FROM usuarios WHERE rol = 'admin' LIMIT 1")->fetch_assoc();

    if ($admin) {
        if ($admin['correo'] !== 'admin@novamarket.com') {
            $stmt = $conexion->prepare("UPDATE usuarios SET correo = ?, password = ? WHERE id_usuario = ?");
            $new_password = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt->bind_param('ssi', $correo, $password, $id_usuario);
            $correo = 'admin@novamarket.com';
            $password = $new_password;
            $id_usuario = (int) $admin['id_usuario'];
            $stmt->execute();
        } elseif (!password_verify('admin123', $admin['password'])) {
            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id_usuario = ?");
            $new_password = password_hash('admin123', PASSWORD_BCRYPT);
            $stmt->bind_param('si', $new_password, $id_usuario);
            $id_usuario = (int) $admin['id_usuario'];
            $stmt->execute();
        }
    } else {
        $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, correo, password, rol) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param('sss', $nombre, $correo, $password);
        $nombre = 'Administrador';
        $correo = 'admin@novamarket.com';
        $password = $admin_password;
        $stmt->execute();
    }

    $categoria_tec = $conexion->query("SELECT id_categoria FROM categorias WHERE nombre_es = 'Tecnología' LIMIT 1")->fetch_assoc()['id_categoria'];
    $categoria_hogar = $conexion->query("SELECT id_categoria FROM categorias WHERE nombre_es = 'Hogar' LIMIT 1")->fetch_assoc()['id_categoria'];
    $categoria_deportes = $conexion->query("SELECT id_categoria FROM categorias WHERE nombre_es = 'Deportes' LIMIT 1")->fetch_assoc()['id_categoria'];
    $categoria_accesorios = $conexion->query("SELECT id_categoria FROM categorias WHERE nombre_es = 'Accesorios' LIMIT 1")->fetch_assoc()['id_categoria'];

    $productos = [
        [$categoria_tec, 'Laptop Pro 14', 'Laptop de alto rendimiento para trabajo y estudio.', 1299.99, 10, 3, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=600&q=80'],
        [$categoria_tec, 'Auriculares X10', 'Auriculares inalámbricos con cancelación de ruido.', 199.90, 20, 5, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=600&q=80'],
        [$categoria_hogar, 'Lámpara LED Smart', 'Luz inteligente para hogar con control por app.', 79.50, 15, 4, 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=600&q=80'],
        [$categoria_deportes, 'Mancuerna 10kg', 'Set de pesas para entrenamiento en casa.', 89.00, 12, 3, 'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=600&q=80'],
        [$categoria_accesorios, 'Cargador USB-C', 'Cargador rápido para dispositivos USB-C.', 29.99, 30, 6, 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=600&q=80'],
        [$categoria_tec, 'Teclado Mecánico RGB', 'Teclado compacto con switches mecánicos e iluminación RGB.', 74.99, 18, 4, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=600&q=80'],
        [$categoria_tec, 'Monitor UltraView 27', 'Monitor IPS de 27 pulgadas para trabajo y entretenimiento.', 249.00, 9, 2, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?auto=format&fit=crop&w=600&q=80'],
        [$categoria_hogar, 'Cafetera Compacta', 'Cafetera práctica para preparar espresso y café filtrado.', 119.95, 14, 3, 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=600&q=80'],
        [$categoria_deportes, 'Mochila Trek 25L', 'Mochila resistente para senderismo y uso diario.', 64.50, 16, 4, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80'],
        [$categoria_accesorios, 'Reloj Smart Fit', 'Reloj inteligente con seguimiento de actividad y sueño.', 149.90, 11, 3, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=600&q=80']
    ];

    $buscar = $conexion->prepare("SELECT id_producto, imagen FROM productos WHERE nombre = ? LIMIT 1");
    $insertar = $conexion->prepare("INSERT INTO productos (id_categoria, nombre, descripcion, precio, stock, stock_minimo, estado, imagen) VALUES (?, ?, ?, ?, ?, ?, 'activo', ?)");
    $actualizar = $conexion->prepare("UPDATE productos SET imagen = ? WHERE id_producto = ? AND (imagen IS NULL OR imagen = '')");

    foreach ($productos as $producto) {
        [$id_categoria, $nombre, $descripcion, $precio, $stock, $stock_minimo, $imagen] = $producto;
        $buscar->bind_param('s', $nombre);
        $buscar->execute();
        $existente = $buscar->get_result()->fetch_assoc();

        if ($existente) {
            $id_producto = (int) $existente['id_producto'];
            $actualizar->bind_param('si', $imagen, $id_producto);
            $actualizar->execute();
        } else {
            $insertar->bind_param('issdiis', $id_categoria, $nombre, $descripcion, $precio, $stock, $stock_minimo, $imagen);
            $insertar->execute();
        }
    }
}

datos_iniciales($conexion);
?>