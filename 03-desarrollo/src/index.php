<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'conexion.php';

$usuario_logueado = !empty($_SESSION['id_usuario']);
$nombre_usuario   = $_SESSION['nombre'] ?? '';
$rol_usuario      = $_SESSION['rol'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tienda Empresarial - NovaMarket</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; padding: 0; color: #17233b; }
        header { background: #17233b; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .nav-right { display: flex; align-items: center; gap: 15px; }
        .lang-btn { background: #246bce; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 15px; display: grid; grid-template-columns: 3fr 1fr; gap: 20px; }
        
        /* Grid de Productos */
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .card { background: white; border: 1px solid #dbe4ef; border-radius: 8px; padding: 15px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .card-image { width: 100%; height: 170px; object-fit: cover; border-radius: 6px; background: #eef4f8; }
        .card h4 { margin: 10px 0 5px; }
        .card-description { color: #647089; font-size: 13px; min-height: 36px; }
        .card .price { color: #246bce; font-weight: bold; font-size: 18px; margin: 5px 0; }
        .card .stock { font-size: 12px; color: #647089; margin-bottom: 10px; }
        .btn-add { background: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; width: 100%; }
        
        /* Sidebar del Carrito */
        .cart-box { background: white; border: 1px solid #dbe4ef; border-radius: 8px; padding: 15px; height: fit-content; }
        .cart-item { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; font-size: 14px; }
        .cart-total { font-weight: bold; font-size: 16px; margin: 15px 0; }
        .btn-checkout { background: #246bce; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; font-weight: bold; cursor: pointer; }
        a { color: #246bce; text-decoration: none; }
    </style>
</head>
<body>

    <header>
        <h2>NovaMarket</h2>
        <div class="nav-right">
            <!-- Selección de Idioma -->
            <button class="lang-btn" onclick="toggleLanguage()">ES / EN</button>

            <?php if ($usuario_logueado): ?>
                <span><span id="txt-welcome">Hola</span>, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></span>
                <a href="mis_pedidos.php" style="color:white;" id="txt-orders">Mis Pedidos</a>
                <a href="chatbot.php" style="color:#8ee3a2;" id="txt-chat">Chat Ayuda</a>
                <?php if ($rol_usuario === 'admin'): ?>
                    <a href="admin_dashboard.php" style="color:#ffc107;" id="txt-admin">Panel Admin</a>
                <?php endif; ?>
                <a href="logout.php" style="color:#ff6b6b;" id="txt-logout">Cerrar Sesión</a>
            <?php else: ?>
                <a href="login.php" style="color:white;" id="txt-login">Iniciar Sesión</a>
                <a href="registro.php" style="color:white;" id="txt-register">Registrarse</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="container">
        <!-- Catálogo de Productos -->
        <div>
            <h3 id="txt-catalog-title">Catálogo de Productos</h3>
            <div class="products-grid" id="productos-container"></div>
        </div>

        <!-- Carrito de Compras -->
        <div class="cart-box">
            <h3 id="txt-cart-title">Carrito de Compras</h3>
            <div id="cart-items">
                <p id="txt-empty-cart">El carrito está vacío.</p>
            </div>
            <div class="cart-total">
                <span id="txt-total">Total</span>: $<span id="cart-total-val">0.00</span>
            </div>
            <button class="btn-checkout" id="txt-btn-checkout" onclick="procesarCompra()">Confirmar Compra</button>
        </div>
    </div>

    <script>
    // 1. DICCIONARIO DE INTERNACIONALIZACIÓN (ES / EN)
    let currentLang = 'es';
    const dict = {
        es: {
            welcome: "Hola",
            orders: "Mis Pedidos",
            admin: "Panel Admin",
            logout: "Cerrar Sesión",
            login: "Iniciar Sesión",
            register: "Registrarse",
            catalogTitle: "Catálogo de Productos",
            cartTitle: "Carrito de Compras",
            emptyCart: "El carrito está vacío.",
            total: "Total",
            btnCheckout: "Confirmar Compra",
            addCart: "Agregar al Carrito",
            stock: "Disponible",
            noStock: "Sin Stock",
            confirmLogin: "Debes iniciar sesión para comprar."
        },
        en: {
            welcome: "Hello",
            orders: "My Orders",
            admin: "Admin Panel",
            logout: "Log Out",
            login: "Log In",
            register: "Register",
            catalogTitle: "Product Catalog",
            cartTitle: "Shopping Cart",
            emptyCart: "The cart is empty.",
            total: "Total",
            btnCheckout: "Confirm Purchase",
            addCart: "Add to Cart",
            stock: "Available",
            noStock: "Out of Stock",
            confirmLogin: "You must log in to purchase."
        }
    };

    let productos = [];
    let carrito = [];

    // Cambiar Idioma
    function toggleLanguage() {
        currentLang = currentLang === 'es' ? 'en' : 'es';
        applyLanguage();
        renderProductos();
        renderCarrito();
    }

    function applyLanguage() {
        const t = dict[currentLang];
        if (document.getElementById('txt-welcome')) document.getElementById('txt-welcome').innerText = t.welcome;
        if (document.getElementById('txt-orders')) document.getElementById('txt-orders').innerText = t.orders;
        if (document.getElementById('txt-admin')) document.getElementById('txt-admin').innerText = t.admin;
        if (document.getElementById('txt-logout')) document.getElementById('txt-logout').innerText = t.logout;
        if (document.getElementById('txt-login')) document.getElementById('txt-login').innerText = t.login;
        if (document.getElementById('txt-register')) document.getElementById('txt-register').innerText = t.register;
        document.getElementById('txt-catalog-title').innerText = t.catalogTitle;
        document.getElementById('txt-cart-title').innerText = t.cartTitle;
        document.getElementById('txt-total').innerText = t.total;
        document.getElementById('txt-btn-checkout').innerText = t.btnCheckout;
    }

    // 2. CARGAR PRODUCTOS DESDE EL BACKEND
    async function cargarProductos() {
        const res = await fetch('obtener_productos.php');
        productos = await res.json();
        renderProductos();
    }

    function renderProductos() {
        const container = document.getElementById('productos-container');
        const t = dict[currentLang];
        container.innerHTML = '';

        productos.forEach(p => {
            container.innerHTML += `
                <div class="card">
                    <img class="card-image" src="${p.imagen || 'https://images.unsplash.com/photo-1560393464-5c69a73c5770?auto=format&fit=crop&w=600&q=80'}" alt="${p.nombre}" loading="lazy">
                    <h4>${p.nombre}</h4>
                    <p class="card-description">${p.descripcion || ''}</p>
                    <div class="price">$${parseFloat(p.precio).toFixed(2)}</div>
                    <div class="stock">${t.stock}: ${p.stock}</div>
                    <button class="btn-add" onclick="agregarAlCarrito(${p.id_producto})" ${p.stock <= 0 ? 'disabled' : ''}>
                        ${p.stock > 0 ? t.addCart : t.noStock}
                    </button>
                </div>
            `;
        });
    }

    // 3. LÓGICA DEL CARRITO
    function agregarAlCarrito(id) {
        const prod = productos.find(p => p.id_producto == id);
        const itemEnCarrito = carrito.find(item => item.id_producto == id);
        const cantActual = itemEnCarrito ? itemEnCarrito.cantidad : 0;

        // Regla de Negocio: Impedir superar el inventario disponible
        if (cantActual + 1 > prod.stock) {
            alert(`No puedes agregar más unidades. Inventario máximo: ${prod.stock}`);
            return;
        }

        if (itemEnCarrito) {
            itemEnCarrito.cantidad++;
        } else {
            carrito.push({ id_producto: prod.id_producto, nombre: prod.nombre, precio: parseFloat(prod.precio), cantidad: 1 });
        }
        renderCarrito();
    }

    function renderCarrito() {
        const container = document.getElementById('cart-items');
        const t = dict[currentLang];
        
        if (carrito.length === 0) {
            container.innerHTML = `<p id="txt-empty-cart">${t.emptyCart}</p>`;
            document.getElementById('cart-total-val').innerText = '0.00';
            return;
        }

        let total = 0;
        container.innerHTML = '';

        carrito.forEach(item => {
            const subtotal = item.precio * item.cantidad;
            total += subtotal;
            container.innerHTML += `
                <div class="cart-item">
                    <div><strong>${item.nombre}</strong><br>${item.cantidad} x $${item.precio.toFixed(2)}</div>
                    <div>$${subtotal.toFixed(2)}</div>
                </div>
            `;
        });

        document.getElementById('cart-total-val').innerText = total.toFixed(2);
    }

    // 4. PROCESAR COMPRA VIA FETCH
    async function procesarCompra() {
        const t = dict[currentLang];
        if (carrito.length === 0) return;

        const estaLogueado = <?php echo $usuario_logueado ? 'true' : 'false'; ?>;
        if (!estaLogueado) {
            alert(t.confirmLogin);
            window.location.href = 'login.php';
            return;
        }

        const res = await fetch('procesar_pedido.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ carrito: carrito })
        });

        const data = await res.json();

        if (data.status === 'success') {
            alert(data.message);
            carrito = [];
            cargarProductos();
            renderCarrito();
        } else {
            alert("Error: " + data.message);
        }
    }

    cargarProductos();
    </script>
</body>
</html>