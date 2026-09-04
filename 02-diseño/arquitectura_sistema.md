# Arquitectura del sistema

NovaMarket utiliza una arquitectura web cliente-servidor sencilla:

- **Presentacion:** HTML, CSS y JavaScript en `03-desarrollo/src/index.php`.
- **Backend:** endpoints y paginas PHP para autenticacion, catalogo, pedidos y chatbot.
- **Persistencia:** MySQL mediante `conexion.php` y la extension `mysqli`.
- **Sesion:** sesiones PHP para identificar clientes y administradores.

El navegador consulta `obtener_productos.php` para cargar el catalogo y envia los pedidos a `procesar_pedido.php`.
