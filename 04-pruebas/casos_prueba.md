# Casos de Prueba – NovaMarket

| ID     | Caso                          | Resultado esperado                                                                 |
|--------|--------------------------------|------------------------------------------------------------------------------------|
| CP-01  | Abrir catálogo                 | Se muestran productos activos con nombre, precio, categoría, stock e imagen.       |
| CP-02  | Registrar usuario              | El usuario queda creado en la BD y puede iniciar sesión con sus credenciales.      |
| CP-03  | Iniciar sesión (Cliente)       | El sistema valida credenciales y redirige al panel de cliente.                     |
| CP-04  | Carrito de compras             | El producto agregado aparece con cantidad, subtotal y se bloquea si excede stock.  |
| CP-05  | Historial de pedidos           | Se listan las órdenes del cliente autenticado con fecha, total y estado.           |
| CP-06  | Chatbot                        | El asistente responde preguntas frecuentes y guarda historial conversacional.      |
| CP-07  | Dashboard administrador        | Se muestran métricas: clientes, productos activos, pedidos y alertas de stock bajo.|
| CP-08  | Gestión de pedidos (Admin)     | El administrador puede cambiar estado de pedidos a Pendiente, Completado o Cancelado.|
| CP-09  | Gestión de inventario (Admin)  | CRUD de productos: crear, modificar, baja lógica y actualización de stock mínimo.  |
| CP-10  | Cambio de idioma (i18n)        | La interfaz cambia dinámicamente entre Español e Inglés sin recargar la página.    |
