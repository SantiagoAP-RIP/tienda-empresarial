<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$query = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.imagen, p.stock, p.stock_minimo, c.nombre_es AS categoria 
          FROM productos p 
          JOIN categorias c ON p.id_categoria = c.id_categoria 
          WHERE p.estado = 'activo'";

$resultado = $conexion->query($query);
$productos = [];

while ($row = $resultado->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);
?>