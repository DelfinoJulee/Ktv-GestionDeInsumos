<?php
// admin_reset_password.php

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
$es_admin = ($usuario_email == 'sistemas2@kalciyan.com.ar' || $usuario_email == 'controldestock@kalciyan.com.ar');

if (!$es_admin) {
    echo "No tienes permisos para acceder a esta página.";
    exit;
}

// Procesar el formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verificar el token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "<p style='color: red;'>Error de validación de seguridad.</p>";
        exit;
    }

    $usuario_id_to_reset = $_POST['usuario_id'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validar que las contraseñas coinciden
    if ($new_password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        // Validar la fortaleza de la contraseña (ejemplo: mínimo 8 caracteres)
        if (strlen($new_password) < 8) {
            $error = "La contraseña debe tener al menos 8 caracteres.";
        } else {
            // Validar que el usuario existe
            $stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE id = ?");
            $stmt->execute([$usuario_id_to_reset]);
            $usuario_a_resetear = $stmt->fetch();

            if ($usuario_a_resetear) {
                // Encriptar la nueva contraseña
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Actualizar la contraseña en la base de datos
                $stmt = $pdo->prepare("UPDATE Usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $usuario_id_to_reset]);

                // Opcional: Registrar la acción en un log
                $stmt = $pdo->prepare("INSERT INTO Logs (admin_id, usuario_id, accion, fecha) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$usuario_id, $usuario_id_to_reset, 'Restablecimiento de contraseña']);

                $success = "La contraseña ha sido restablecida con éxito.";
            } else {
                $error = "El usuario seleccionado no existe.";
            }
        }
    }
}

// Generar un token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Obtener la lista de usuarios (incluyendo a los administradores si lo deseas)
$stmt = $pdo->prepare("SELECT id, email FROM Usuarios");
$stmt->execute();
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Restablecer Contraseña de Usuario</title>
    <link rel="stylesheet" href="styles.css"> <!-- Enlace al archivo CSS -->
</head>
<body>

<header>
    <img src="img/logo.png" alt="Logo de Kalciyan">
</header>

<h2>Restablecer Contraseña de Usuario</h2>

<!-- Mostrar mensajes de éxito o error -->
<?php if (isset($success)): ?>
    <div class="message success">
        <p><?= $success ?></p>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="message error">
        <p><?= $error ?></p>
    </div>
<?php endif; ?>

<form method="POST" action="admin_reset_password.php">
    <label for="usuario_id">Selecciona un usuario:</label>
    <select name="usuario_id" id="usuario_id" required>
        <option value="">Seleccione un usuario</option>
        <?php foreach ($usuarios as $user): ?>
            <option value="<?= $user['id'] ?>"><?= $user['email'] ?></option>
        <?php endforeach; ?>
    </select>

    <label for="new_password">Nueva Contraseña:</label>
    <input type="password" name="new_password" id="new_password" required>

    <label for="confirm_password">Confirmar Nueva Contraseña:</label>
    <input type="password" name="confirm_password" id="confirm_password" required>

    <!-- Token CSRF -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <button type="submit">Restablecer Contraseña</button>
</form>

<!-- Botones de navegación -->
<div class="nav-buttons">
    <a href="gestionar_insumos.php">Volver a Gestión de Insumos</a>
    <a href="login.php">Volver al Login</a>
</div>

</body>
</html>