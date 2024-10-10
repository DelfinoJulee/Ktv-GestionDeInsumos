<?php
require 'db.php';
session_start();

// Procesar el formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $contraseña = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($contraseña, $usuario['password'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        // Redirigir a gestionar_insumos.php
        header("Location: gestionar_insumos.php");
        exit;
    } else {
        $error = "Credenciales incorrectas.";
    }
}

// Verificar si el usuario es administrador para mostrar el enlace de registro
$es_admin = false;
if (isset($_SESSION['usuario_id'])) {
    $usuario_id = $_SESSION['usuario_id'];
    $stmt = $pdo->prepare("SELECT email FROM Usuarios WHERE id = ?");
    $stmt->execute([$usuario_id]);
    $usuario = $stmt->fetch();

    if ($usuario) {
        $usuario_email = $usuario['email'];
        $es_admin = ($usuario_email == 'controldestock@kalciyan.com.ar' || $usuario_email == 'sistemas2@kalciyan.com.ar');
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    <link rel="stylesheet" href="css/styles.css"> <!-- Enlace al archivo CSS -->
</head>
<body>
<header>
    <img src="img/kalciyan_logo.png" alt="Logo Kalciyan">
</header>
<form action="login.php" method="POST">
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Contraseña:</label>
    <input type="password" id="password" name="password" required>
    <button type="submit">Ingresar</button>

    

    <!-- Botón de Registro -->
    <div class="register-container">
       
    </div>
</form>

<?php if (isset($error)): ?>
    <div class="error-message">
        <p><?= $error ?></p>
    </div>
<?php endif; ?>

</body>
</html>