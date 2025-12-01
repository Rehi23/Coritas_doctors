<?php
session_start();
// RUTA DE CONEXIÓN: Salir de la carpeta actual (doctores/) y entrar a includes/
require '../includes/conexion.php';

// 1. VALIDACIÓN CENTRALIZADA POR ROL (Correcta)
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../public/index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: panel_admin.php");
    exit();
}

$id = $_GET['id'];
$sql = $pdo->prepare("SELECT * FROM doctores WHERE id = ?");
$sql->execute([$id]);
$doctor = $sql->fetch();

if (!$doctor) {
    header("Location: panel_admin.php");
    exit();
}

$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $especialidad = trim($_POST['especialidad']);
    $telefono = trim($_POST['telefono']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];

    if ($nombre === "" || $especialidad === "") {
        $error = "Nombre y especialidad son obligatorios.";
    } else {

        if ($password !== "") {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            // También se debe actualizar el campo 'rol' si es necesario, aunque en este contexto
            // de edición de doctor no administrador, lo mantendremos como estaba.
            $update = $pdo->prepare("UPDATE doctores SET nombre=?, especialidad=?, telefono=?, correo=?, password=? WHERE id=?");
            $ok = $update->execute([$nombre, $especialidad, $telefono, $correo, $hash, $id]);
        } else {
            $update = $pdo->prepare("UPDATE doctores SET nombre=?, especialidad=?, telefono=?, correo=? WHERE id=?");
            $ok = $update->execute([$nombre, $especialidad, $telefono, $correo, $id]);
        }

        if ($ok) {
            $mensaje = "Doctor actualizado correctamente.";
        } else {
            $error = "Error al actualizar.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Doctor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #F2F6FC;
            margin: 0;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            padding-top: 25px;
            border-right: 1px solid #c5d4e3;
            position: fixed;
        }

        .sidebar h4 {
            color: #1B76D1;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            font-size: 15px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
            gap: 10px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #1B76D1;
            color: white;
            border-radius: 10px;
        }

        .topbar {
            margin-left: 250px;
            height: 60px;
            background-color: #1B76D1;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            font-weight: 600;
            justify-content: space-between;
        }

        .search-wrapper {
            margin-left: 250px;
            padding: 15px 20px;
            background: #F2F6FC;
        }

        .content {
            margin-left: 250px;
            padding: 10px 20px;
        }

        .doctor-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: .2s;
        }

        .doctor-tag {
            background: #D8EDFF;
            color: #004d82;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .topbar,
            .content,
            .search-wrapper {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <span>Editar Doctor</span>
        <button class="btn btn-light d-lg-none" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
    </div>

    <div class="sidebar" id="sidebar">
        <h4>Corita’s Doctor</h4>
        <a href="../admin/panel_admin.php"><i class="bi bi-person-badge-fill"></i> Administrar Doctores</a>
        <a href="../admin/crear_doctor.php"><i class="bi bi-person-plus-fill"></i> Agregar Doctor</a>
        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>

    <div class="content">
        <h3 class="mb-3">Editar Doctor</h3>
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="doctor-card" style="cursor:default;">
            <form method="POST" style="width:100%;">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
                <label class="form-label mt-2">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($doctor['nombre']) ?>" required>
                <label class="form-label mt-3">Especialidad</label>
                <input type="text" name="especialidad" class="form-control" value="<?= htmlspecialchars($doctor['especialidad']) ?>" required>
                <label class="form-label mt-3">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($doctor['telefono']) ?>">
                <label class="form-label mt-3">Correo</label>
                <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($doctor['correo']) ?>">
                <label class="form-label mt-3">Nueva contraseña (opcional)</label>
                <input type="password" name="password" class="form-control">
                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Guardar cambios
                    </button>
                    <a href="panel_admin.php" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }
    </script>
</body>

</html>