<?php
session_start();
// 🚩 CORRECCIÓN 1: Ruta de conexión. Salir de 'doctores/' y entrar a 'includes/'
require '../includes/conexion.php';
// Variable para almacenar mensajes de error o éxito
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');

    // Usamos el operador coalescente (?? '') para manejar los campos faltantes del formulario
    $especialidad = trim($_POST['especialidad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? ''; // Recuperamos la confirmación

    /* ───────────────────────────────
       1) Validaciones
    ─────────────────────────────── */

    // Validar que las contraseñas coincidan
    if ($password !== $password2) {
        $error = "Las contraseñas ingresadas no coinciden. Por favor, revísalas.";
    }
    // Validar campos requeridos
    else if (empty($nombre) || empty($correo) || empty($password)) {
        $error = "Por favor, completa todos los campos obligatorios.";
    } else {
        /* ───────────────────────────────
           2) Verificar correo único
        ─────────────────────────────── */
        $check = $pdo->prepare("SELECT id FROM doctores WHERE correo = ?");
        $check->execute([$correo]);

        if ($check->fetch()) {
            $error = "Este correo ya está registrado en el sistema.";
        } else {
            /* ───────────────────────────────
               3) Insertar Doctor
            ─────────────────────────────── */
            try {
                $rol_defecto = 'doctor'; // Nuevo campo de Rol
                // Hash de contraseña
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insert = $pdo->prepare("
                    INSERT INTO doctores (nombre, correo, especialidad, rol, telefono, password)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $insert->execute([$nombre, $correo, $especialidad, $rol_defecto, $telefono, $passwordHash]);

                // ¡ÉXITO! Redirigir
                $_SESSION['msg'] = "Registro exitoso. Inicia sesión.";
                // 🚩 CORRECCIÓN 2: Salir de 'doctores/' y entrar a 'public/index.php'
                header("Location: ../public/index.php");
                exit;
            } catch (PDOException $e) {
                // Capturar errores de la base de datos (ej. problema de conexión o SQL)
                $error = "Error al intentar guardar el registro: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
       
    <meta charset="UTF-8">
        <title>Proceso de Registro</title>
       
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
        .container {
            padding-top: 50px;
        }
    </style>
</head>

<body>
        <div class="container text-center">
                <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <a href="../public/registro.php" class="btn btn-primary">Volver al Registro</a>
                    <?php endif; ?>
            </div>
       
        </body>

</html>