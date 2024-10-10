<?php
require 'db.php';
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario_id'])) {
    echo "Debes iniciar sesión como administrador para acceder a esta página.";
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$stmt = $pdo->prepare("SELECT email FROM Usuarios WHERE id = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();

if ($usuario) {
    $usuario_email = $usuario['email'];
    $es_admin = ($usuario_email == 'controldestock@kalciyan.com.ar' || $usuario_email == 'sistemas2@kalciyan.com.ar');

    if (!$es_admin) {
        echo "No tienes permisos para acceder a esta página.";
        exit;
    }
} else {
    echo "Usuario no encontrado.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $contraseña = password_hash($_POST['contraseña'], PASSWORD_DEFAULT);
    $sector_id = $_POST['sector_id'];

    // Inserta el usuario en la base de datos
    $stmt = $pdo->prepare("INSERT INTO Usuarios (nombre, email, password, sector_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$nombre, $email, $contraseña, $sector_id])) {
        $success = "Usuario registrado con éxito.";
    } else {
        $error = "Error al registrar usuario.";
    }
}

// Consulta para obtener todos los sectores
$stmt = $pdo->prepare("SELECT * FROM Sectores");
$stmt->execute();
$sectores = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Usuario</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- Enlace al archivo CSS -->
</head>
<body>
<header>
    <img src="img/kalciyan_logo.png" alt="Logo Kalciyan">
</header>

<div class="form-container">
    <h2>Registro</h2>

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

    <!-- Formulario de registro -->
    <form action="register.php" method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" name="nombre" placeholder="Nombre" required>

        <label for="email">Email:</label>
        <input type="email" name="email" placeholder="Email" required>

        <label for="contraseña">Contraseña:</label>
        <input type="password" name="contraseña" placeholder="Contraseña" required>

        <label for="sector_id">Sector:</label>
        <select name="sector_id" required>
            <!-- Cargar dinámicamente los sectores desde la base de datos -->
            <?php foreach ($sectores as $sector): ?>
                <option value="<?= $sector['id'] ?>"><?= $sector['nombre'] ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit">Registrar</button>
    </form>

    <!-- Botón para ir al Login -->
    <div class="nav-buttons">
        <p>¿Ya tienes una cuenta? <a href="login.php">Inicia sesión aquí</a></p>
    </div>
</div>

</body>
</html>