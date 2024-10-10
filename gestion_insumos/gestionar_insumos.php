<?php
require 'db.php';
session_start();

// Evitar que el navegador almacene en caché la página
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php"); // Redirige al login si no está logueado
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Obtener los datos del usuario logueado
$stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

// Obtener el sector del usuario
$sector_id = $usuario['sector_id'];

// Verificar si el usuario es 'controldestock@kalciyan.com.ar' o 'sistemas2@kalciyan.com.ar'
$es_admin = ($usuario['email'] == 'controldestock@kalciyan.com.ar' || $usuario['email'] == 'sistemas2@kalciyan.com.ar');

// Obtener los sectores
if ($es_admin) {
    // Admin: Obtener todos los sectores
    $stmt = $pdo->prepare("SELECT * FROM Sectores");
    $stmt->execute();
    $sectores = $stmt->fetchAll();
} else {
    // Usuario regular: Obtener solo su sector
    $stmt = $pdo->prepare("SELECT * FROM Sectores WHERE id = ?");
    $stmt->execute([$sector_id]);
    $sectores = $stmt->fetchAll();
}

// Procesar el formulario de solicitud de insumo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['insumos']) || empty($_POST['insumos'])) {
        echo "Error: Debes agregar al menos un insumo a la lista.";
        exit;
    }

    $insumos_solicitados = $_POST['insumos'];
    $sector_id_form = $_POST['sector_id'];

    foreach ($insumos_solicitados as $solicitud) {
        $insumo_id = $solicitud['insumo_id'];
        $cantidad = $solicitud['cantidad'];
        $observacion = $solicitud['observacion'] ?? null;

        // Validar que la cantidad sea mayor o igual a 1
        if ($cantidad < 1) {
            echo "Error: La cantidad debe ser mayor o igual a 1.";
            exit;
        }

        if ($insumo_id == 'otro') {
            $nombre_insumo = $solicitud['nombre_insumo'];
            // Validar que el nombre del insumo no esté vacío
            if (empty($nombre_insumo)) {
                echo "Error: Debes proporcionar el nombre del nuevo insumo.";
                exit;
            }
            $stmt = $pdo->prepare("INSERT INTO Pedidos (usuario_id, nombre_insumo, cantidad, observacion, sector_id, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$usuario_id, $nombre_insumo, $cantidad, $observacion, $sector_id_form]);
        } else {
            // Validar que el insumo_id no esté vacío
            if (empty($insumo_id)) {
                echo "Error: Debes seleccionar un insumo.";
                exit;
            }
            // Verificar que el insumo pertenece al sector (solo para usuarios regulares)
            if (!$es_admin) {
                $stmt = $pdo->prepare("SELECT sector_id FROM Insumos WHERE id = ?");
                $stmt->execute([$insumo_id]);
                $insumo_sector = $stmt->fetchColumn();

                if ($insumo_sector != $sector_id) {
                    echo "Error: No tienes permiso para solicitar este insumo.";
                    exit;
                }
            }
            $stmt = $pdo->prepare("INSERT INTO Pedidos (usuario_id, insumo_id, cantidad, observacion, sector_id, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')");
            $stmt->execute([$usuario_id, $insumo_id, $cantidad, $observacion, $sector_id_form]);
        }
    }

    // Redirigir a la misma página con mensaje de éxito
    header("Location: gestionar_insumos.php?success=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitar Insumo</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- Enlace al archivo CSS -->
</head>
<body>
<header>
    <img src="img/kalciyan_logo.png" alt="Logo Kalciyan">
</header>

<div class="container">
    <h2>Solicitar Insumo</h2>

    <!-- Mostrar mensaje de éxito si existe -->
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="message success">
            <p>El pedido se ha realizado con éxito.</p>
        </div>
        <script>
            // Refrescar la página después de 3 segundos
            setTimeout(function() {
                window.location.href = 'gestionar_insumos.php';
            }, 3000);
        </script>
    <?php endif; ?>

    <!-- Mostrar botones para ver y exportar pedidos solo si el usuario es admin -->
    <?php if ($es_admin): ?>
        <div class="nav-buttons">
            <a href="ver_pedidos.php">Ver Pedidos</a>
            <a href="exportar_pedidos.php">Exportar Pedidos</a>
            <a href="admin_reset_password.php">Restablecer Contraseñas</a>
        </div>
    <?php endif; ?>

    <form id="form_solicitud" action="" method="POST">

        <!-- Selección del sector -->
        <?php if ($es_admin): ?>
            <label for="sector">Selecciona un sector:</label>
            <select name="sector_id" id="sector" required>
                <option value="">Selecciona un sector</option>
                <?php foreach ($sectores as $sector): ?>
                    <option value="<?= $sector['id'] ?>"><?= $sector['nombre'] ?></option>
                <?php endforeach; ?>
            </select>
        <?php else: ?>
            <input type="hidden" name="sector_id" id="sector" value="<?= $sector_id ?>">
            <p>Sector: <?= $sectores[0]['nombre'] ?></p>
        <?php endif; ?>

        <!-- Selección de insumo basado en el sector -->
        <label for="insumo">Selecciona un insumo existente o agrega uno nuevo:</label>
        <select name="insumo_id" id="insumo">
            <option value="">Selecciona un insumo</option>
            <option value="otro">Otro (Agregar nuevo insumo)</option>
        </select>

        <!-- Campo para nuevo insumo (aparece solo si selecciona "Otro") -->
        <div id="nuevo_insumo" style="display: none;">
            <label for="nombre_insumo">Nombre del nuevo insumo:</label>
            <input type="text" name="nombre_insumo" placeholder="Nombre del insumo" id="nombre_insumo">
        </div>

        <label for="cantidad">Cantidad:</label>
        <input type="number" name="cantidad" placeholder="Cantidad" min="1">

        <label for="observacion">Observación (opcional):</label>
        <textarea name="observacion" placeholder="Observación"></textarea>

        <button type="button" id="agregar_insumo">Agregar Insumo</button>

        <!-- Lista dinámica de insumos solicitados -->
        <h3>Lista de insumos solicitados:</h3>
        <ul id="lista_insumos"></ul>

        <button type="submit">Enviar Pedido</button>
    </form>
</div>

<script>
// Inicializar el índice de insumo
var insumoIndex = 0;

// Función para obtener los insumos de un sector
function fetchInsumos(sectorId) {
    if (!sectorId) return;
    // Realizar una petición AJAX para obtener los insumos del sector seleccionado
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'obtener_insumos.php?sector_id=' + sectorId, true);
    xhr.onload = function() {
        if (this.status === 200) {
            // Actualizar el select de insumos
            var insumoSelect = document.getElementById('insumo');
            insumoSelect.innerHTML = this.responseText;
        }
    };
    xhr.send();
}

// Inicializar la carga de insumos
document.addEventListener('DOMContentLoaded', function() {
    var sectorSelect = document.getElementById('sector');
    var esAdmin = <?= json_encode($es_admin) ?>;

    if (esAdmin) {
        // Admin: cargar insumos cuando se selecciona un sector
        sectorSelect.addEventListener('change', function() {
            fetchInsumos(this.value);
        });
    } else {
        // Usuario regular: cargar insumos de su sector al cargar la página
        fetchInsumos(sectorSelect.value);
    }
});

// Mostrar campo de nombre de insumo si se selecciona "Otro"
document.getElementById('insumo').addEventListener('change', function() {
    if (this.value === 'otro') {
        document.getElementById('nuevo_insumo').style.display = 'block';
    } else {
        document.getElementById('nuevo_insumo').style.display = 'none';
    }
});

// Dinámica para agregar insumos a la lista
document.getElementById('agregar_insumo').addEventListener('click', function() {
    var insumoSelect = document.getElementById('insumo');
    var cantidadInput = document.querySelector('input[name="cantidad"]');
    var observacionInput = document.querySelector('textarea[name="observacion"]');
    var nombreInsumoInput = document.querySelector('input[name="nombre_insumo"]');

    var insumoId = insumoSelect.value;
    var insumoText = insumoSelect.options[insumoSelect.selectedIndex]?.text || '';
    var cantidad = cantidadInput.value;
    var observacion = observacionInput.value || '';

    // Validar que se haya seleccionado un insumo
    if (!insumoId) {
        alert("Debes seleccionar un insumo.");
        return;
    }

    // Validar que la cantidad no esté vacía
    if (!cantidad) {
        alert("Debes ingresar una cantidad.");
        return;
    }

    if (insumoId === 'otro') {
        insumoText = nombreInsumoInput.value.trim();
        if (!insumoText) {
            alert("Debes proporcionar el nombre del nuevo insumo.");
            return;
        }
    }

    var listaInsumos = document.getElementById('lista_insumos');

    // Crear un nuevo elemento en la lista
    var listItem = document.createElement('li');
    listItem.innerHTML = `Insumo: ${insumoText}, Cantidad: ${cantidad}, Observación: ${observacion} 
        <button type="button" class="editar">Editar</button>
        <button type="button" class="eliminar">Eliminar</button>`;

    // Agregar inputs ocultos para enviar con el formulario
    var inputInsumo = document.createElement('input');
    inputInsumo.type = 'hidden';
    inputInsumo.name = 'insumos[' + insumoIndex + '][insumo_id]';
    inputInsumo.value = insumoId;

    var inputCantidad = document.createElement('input');
    inputCantidad.type = 'hidden';
    inputCantidad.name = 'insumos[' + insumoIndex + '][cantidad]';
    inputCantidad.value = cantidad;

    var inputObservacion = document.createElement('input');
    inputObservacion.type = 'hidden';
    inputObservacion.name = 'insumos[' + insumoIndex + '][observacion]';
    inputObservacion.value = observacion;

    if (insumoId === 'otro') {
        var inputNombreInsumo = document.createElement('input');
        inputNombreInsumo.type = 'hidden';
        inputNombreInsumo.name = 'insumos[' + insumoIndex + '][nombre_insumo]';
        inputNombreInsumo.value = insumoText;
        listItem.appendChild(inputNombreInsumo);
    }

    // Incrementar el índice para el siguiente insumo
    insumoIndex++;

    // Añadir inputs ocultos al listItem
    listItem.appendChild(inputInsumo);
    listItem.appendChild(inputCantidad);
    listItem.appendChild(inputObservacion);

    listaInsumos.appendChild(listItem);

    // Limpiar los campos del formulario
    cantidadInput.value = '';
    observacionInput.value = '';
    if (insumoId === 'otro') {
        nombreInsumoInput.value = '';
        document.getElementById('nuevo_insumo').style.display = 'none';
        insumoSelect.value = '';
    } else {
        insumoSelect.value = '';
    }

    // Eliminar insumo de la lista
    listItem.querySelector('.eliminar').addEventListener('click', function() {
        listaInsumos.removeChild(listItem);
    });

    // Editar insumo de la lista
    listItem.querySelector('.editar').addEventListener('click', function() {
        cantidadInput.value = inputCantidad.value;
        observacionInput.value = inputObservacion.value;
        insumoSelect.value = insumoId;

        if (insumoId === 'otro') {
            document.getElementById('nuevo_insumo').style.display = 'block';
            nombreInsumoInput.value = insumoText;
        } else {
            document.getElementById('nuevo_insumo').style.display = 'none';
            nombreInsumoInput.value = '';
        }

        listaInsumos.removeChild(listItem); // Remover el ítem de la lista para poder editarlo
    });
});

// Validar que la lista de insumos no esté vacía al enviar el formulario
document.getElementById('form_solicitud').addEventListener('submit', function(event) {
    var listaInsumos = document.getElementById('lista_insumos');
    if (listaInsumos.children.length === 0) {
        alert("Debes agregar al menos un insumo a la lista antes de enviar el pedido.");
        event.preventDefault(); // Evita que el formulario se envíe
    }
});
</script>
<!-- Enlace a Mis Pedidos -->
<div class="nav-buttons">
    <a href="ver_mis_pedidos.php">Ver Mis Pedidos</a>
</div>

<!-- Botón de Cerrar Sesión -->
<div class="nav-buttons">
    <a href="logout.php">Cerrar Sesión</a>
</div>
</body>
</html>