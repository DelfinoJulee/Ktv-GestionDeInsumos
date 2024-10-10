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
    echo "No tienes permisos para ver los pedidos.";
    exit;
}

// Filtrar por fecha (opcional)
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-d');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');

// Validar que fecha_fin no sea anterior a fecha_inicio
if ($fecha_fin < $fecha_inicio) {
    echo "<p style='color:red;'>La fecha de fin no puede ser anterior a la fecha de inicio.</p>";
    exit;
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pedido_id'], $_POST['accion_estado'])) {
    $pedido_id = $_POST['pedido_id'];
    $accion_estado = $_POST['accion_estado'];

    // Determinar el nuevo estado
    $nuevo_estado = ($accion_estado == 'entregado') ? 'entregado' : 'pendiente';

    // Actualizar el estado del pedido
    $stmt = $pdo->prepare("UPDATE Pedidos SET estado = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $pedido_id]);

    // Redirigir para evitar reenvío de formulario
    header("Location: ver_pedidos.php?fecha_inicio=$fecha_inicio&fecha_fin=$fecha_fin&success=1");
    exit;
}

// Consulta para obtener los pedidos filtrados por fecha y unir con las tablas necesarias
$stmt = $pdo->prepare("
    SELECT Pedidos.id, 
           IFNULL(Insumos.nombre, Pedidos.nombre_insumo) AS nombre_insumo, 
           Pedidos.cantidad, 
           Pedidos.observacion, 
           Pedidos.fecha_pedido, 
           Pedidos.estado,
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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Visualización de Pedidos</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- Enlace al archivo CSS -->
</head>
<body>

<header>
    <img src="img/kalciyan_logo.png" alt="Logo Kalciyan">
</header>

<h2>Visualización de Pedidos</h2>

<!-- Mostrar mensaje de éxito si existe -->
<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="message success">
        <p>El estado del pedido se ha actualizado con éxito.</p>
    </div>
<?php endif; ?>

<!-- Formulario para filtrar pedidos por fecha -->
<form method="GET" action="ver_pedidos.php">
    <label for="fecha_inicio">Fecha Inicio:</label>
    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= $fecha_inicio ?>" required>

    <label for="fecha_fin">Fecha Fin:</label>
    <input type="date" name="fecha_fin" id="fecha_fin" value="<?= $fecha_fin ?>" required>

    <button type="submit">Filtrar</button>
</form>

<script>
// Obtener los elementos de fecha
const fechaInicioInput = document.getElementById('fecha_inicio');
const fechaFinInput = document.getElementById('fecha_fin');

// Función para actualizar el 'min' de fecha_fin
function actualizarFechaFinMin() {
    fechaFinInput.min = fechaInicioInput.value;
    // Si la fecha_fin actual es menor que fecha_inicio, ajustarla
    if (fechaFinInput.value < fechaInicioInput.value) {
        fechaFinInput.value = fechaInicioInput.value;
    }
}

// Evento al cambiar fecha_inicio
fechaInicioInput.addEventListener('change', actualizarFechaFinMin);

// Inicializar el 'min' de fecha_fin al cargar la página
actualizarFechaFinMin();
</script>

<?php if (!empty($pedidos)): ?>
<table>
    <thead>
        <tr>
            <th>Usuario</th>
            <th>Sector</th>
            <th>Insumo</th>
            <th>Cantidad</th>
            <th>Observación</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pedidos as $pedido): ?>
        <tr>
            <td><?= $pedido['email'] ?></td>
            <td><?= $pedido['nombre_sector'] ?></td>
            <td><?= $pedido['nombre_insumo'] ?></td>
            <td><?= $pedido['cantidad'] ?></td>
            <td class="observacion-cell"><?= $pedido['observacion'] ?></td>
            <td><?= $pedido['fecha_pedido'] ?></td>
            <td class="estado-cell <?= strtolower($pedido['estado']) ?>">
                <?= ucfirst($pedido['estado']) ?>
            </td>
            <td>
                <form method="POST" action="ver_pedidos.php?fecha_inicio=<?= $fecha_inicio ?>&fecha_fin=<?= $fecha_fin ?>">
                    <input type="hidden" name="pedido_id" value="<?= $pedido['id'] ?>">
                    <?php if ($pedido['estado'] == 'pendiente'): ?>
                        <button type="submit" name="accion_estado" value="entregado" class="btn entregado">Entregado</button>
                    <?php else: ?>
                        <button type="submit" name="accion_estado" value="pendiente" class="btn pendiente">Pendiente</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <div class="message error">
        <p>No hay pedidos en el rango de fechas seleccionado.</p>
    </div>
<?php endif; ?>


<!-- Botón de Cerrar Sesión -->
<div class="nav-buttons">
    <a href="logout.php">Cerrar Sesión</a>
    <a href="gestionar_insumos.php">Volver a Gestión de Insumos</a>
</div>

</body>
</html>