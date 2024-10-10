<?php
require 'db.php';
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo "Debes iniciar sesión.";
    exit;
}

// Obtener los datos del usuario logueado
$usuario_id = $_SESSION['usuario_id'];

// Obtener los datos del usuario
$stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Verificar si el usuario es administrador
$usuario_email = $usuario['email'];
if ($usuario_email != 'sistemas2@kalciyan.com.ar' && $usuario_email != 'controldestock@kalciyan.com.ar') {
    echo "No tienes permisos para exportar los pedidos.";
    exit;
}

// Obtener las fechas de filtrado del formulario
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-d');
$fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : date('Y-m-d');

// Validar que fecha_fin no sea anterior a fecha_inicio
if ($fecha_fin < $fecha_inicio) {
    echo "<p style='color:red;'>La fecha de fin no puede ser anterior a la fecha de inicio.</p>";
    exit;
}


// Obtener los pedidos filtrados por fecha
$stmt = $pdo->prepare("
    SELECT Pedidos.id, 
           IFNULL(Insumos.nombre, Pedidos.nombre_insumo) AS nombre_insumo, 
           Pedidos.cantidad, 
           Pedidos.observacion, 
           Pedidos.fecha_pedido, 
           Usuarios.email,
           Sectores.nombre AS nombre_sector
    FROM Pedidos
    LEFT JOIN Usuarios ON Pedidos.usuario_id = Usuarios.id
    LEFT JOIN Insumos ON Pedidos.insumo_id = Insumos.id
    LEFT JOIN Sectores ON Pedidos.sector_id = Sectores.id
    WHERE DATE(Pedidos.fecha_pedido) BETWEEN ? AND ?
    ORDER BY Pedidos.fecha_pedido DESC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$pedidos = $stmt->fetchAll();

// Crear el archivo CSV
$filename = "pedidos_" . date('Ymd') . ".csv";

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=\"$filename\"");

$output = fopen("php://output", "w");
fputcsv($output, array('Usuario', 'Sector', 'Insumo', 'Cantidad', 'Observación', 'Fecha'));

foreach ($pedidos as $pedido) {
    fputcsv($output, array(
        $pedido['email'], 
        $pedido['nombre_sector'],
        $pedido['nombre_insumo'], 
        $pedido['cantidad'], 
        $pedido['observacion'], 
        $pedido['fecha_pedido']
    ));
}

fclose($output);
exit;
?>