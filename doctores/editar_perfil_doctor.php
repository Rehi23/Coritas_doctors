<?php
session_start();
require '../includes/conexion.php';

// Validar que la sesión de doctor exista
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'doctor' && $_SESSION['user_rol'] !== 'admin')) {
    header("Location: ../public/index.php");
    exit();
}

$id_doctor = $_SESSION['user_id'];
$mensaje = "";
$error = "";

// ----------------------------------------------------
// 1. PROCESAR EL ENVÍO DEL FORMULARIO (POST)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password_nuevo = $_POST['password_nuevo'] ?? '';
    $password_actual = $_POST['password_actual'] ?? '';

    if (empty($nombre) || empty($especialidad) || empty($correo)) {
        $error = "Nombre, especialidad y correo son obligatorios.";
    } else {
        try {
            // Verificar contraseña actual antes de cualquier cambio (seguridad)
            $stmt_check = $pdo->prepare("SELECT password FROM doctores WHERE id = ?");
            $stmt_check->execute([$id_doctor]);
            $current_hash = $stmt_check->fetchColumn();

            if (!password_verify($password_actual, $current_hash)) {
                $error = "Contraseña actual incorrecta. No se realizaron cambios.";
            } else {

                // Preparar los campos y valores a actualizar
                $fields = ['nombre=?, especialidad=?, telefono=?, correo=?'];
                $params = [$nombre, $especialidad, $telefono, $correo];
                $param_types = 'ssss';

                // Si se proporciona una NUEVA contraseña, hashearla e incluirla
                if (!empty($password_nuevo)) {
                    $hash_nuevo = password_hash($password_nuevo, PASSWORD_DEFAULT);
                    $fields[] = 'password=?';
                    $params[] = $hash_nuevo;
                }

                // Agregar el ID del doctor al final de los parámetros de ejecución
                $params[] = $id_doctor;

                // Construir la consulta de actualización
                $sql = "UPDATE doctores SET " . implode(', ', $fields) . " WHERE id=?";
                $stmt_update = $pdo->prepare($sql);

                if ($stmt_update->execute($params)) {
                    // Actualizar la sesión si se cambia el nombre o correo (opcional)
                    $_SESSION['user_nombre'] = $nombre;
                    $mensaje = "Perfil actualizado correctamente.";
                } else {
                    $error = "Error al actualizar el perfil.";
                }
            }
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 2. CARGAR DATOS ACTUALES DEL DOCTOR (GET/Initial Load)
// ----------------------------------------------------
$stmt_load = $pdo->prepare("SELECT nombre, especialidad, telefono, correo FROM doctores WHERE id = ?");
$stmt_load->execute([$id_doctor]);
$doctor = $stmt_load->fetch(PDO::FETCH_ASSOC);

if (!$doctor) {
    session_destroy();
    header("Location: ../public/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Corita's Doctor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #F2F6FC;
            margin: 0;
        }

        .topbar {
            background-color: #1B76D1;
            color: white;
            height: 60px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            font-weight: 600;
            margin-left: 250px;
        }

        .content {
            margin-left: 250px;
            padding: 25px;
        }

        .profile-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 600px;
            margin: auto;
        }

        .section-title {
            font-weight: 700;
            color: #1B76D1;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 14px;
        }

        .btn-primary {
            background-color: #1B76D1;
            border: none;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 10px;
        }

        /* Estilos del sidebar simplificados */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            padding-top: 25px;
            position: fixed;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            text-decoration: none;
        }

        @media (max-width: 991px) {

            .topbar,
            .content {
                margin-left: 0;
            }

            .sidebar {
                transform: translateX(-250px);
            }
        }
    </style>
</head>

<body>

    <div class="sidebar" id="sidebar">
        <h4>Corita’s Doctor</h4>
        <a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
        <a class="active" href="perfil.php"><i class="bi bi-person-circle"></i> Perfil</a>
        <a href="codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
        <a href="pacientes.php"><i class="bi bi-people-fill"></i> Pacientes</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>

    <div class="topbar">
        <span>Editar Perfil</span>
    </div>

    <div class="content">
        <div class="profile-card">
            <h4 class="section-title">Actualizar Datos Personales</h4>

            <?php if ($mensaje): ?>
                <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nombre completo</label>
                    <input name="nombre" type="text" class="form-control" value="<?= htmlspecialchars($doctor['nombre']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Especialidad</label>
                    <input name="especialidad" type="text" class="form-control" value="<?= htmlspecialchars($doctor['especialidad']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input name="telefono" type="text" class="form-control" value="<?= htmlspecialchars($doctor['telefono']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input name="correo" type="email" class="form-control" value="<?= htmlspecialchars($doctor['correo']) ?>" required>
                </div>

                <hr class="my-4">
                <h5 class="text-muted mb-3">Cambio de Contraseña</h5>
                <div class="mb-3">
                    <label class="form-label">Nueva Contraseña (Dejar vacío para no cambiar)</label>
                    <input name="password_nuevo" type="password" class="form-control">
                </div>

                <hr class="my-4">
                <h5 class="text-danger mb-3">Confirmación Requerida</h5>
                <p class="text-muted">Ingresa tu contraseña actual para confirmar cualquier cambio en tu perfil.</p>
                <div class="mb-3">
                    <label class="form-label">Contraseña Actual</label>
                    <input name="password_actual" type="password" class="form-control" required>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="perfil.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }
    </script>
</body>

</html>