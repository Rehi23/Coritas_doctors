<?php
session_start();
// RUTA CORREGIDA: Salir de 'admin/' y entrar a 'includes/'
require '../includes/conexion.php';

// VALIDACIÓN DE ROL: Solo el administrador puede acceder a esta página.
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../public/index.php");
    exit();
}

$mensaje = "";
$error = "";

// ----------------------------------------------------
// LÓGICA DE PROCESAMIENTO (INSERTAR DOCTOR)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $especialidad = trim($_POST['especialidad'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol = 'doctor'; // Nuevo rol por defecto

    if (empty($nombre) || empty($especialidad) || empty($correo) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        try {
            // Verificar si el correo ya existe
            $check = $pdo->prepare("SELECT id FROM doctores WHERE correo = ?");
            $check->execute([$correo]);

            if ($check->fetch()) {
                $error = "Este correo ya está registrado.";
            } else {
                // 1. Generar Hash de Contraseña
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                // 2. Insertar en la base de datos (PDO)
                $sql = "INSERT INTO doctores (nombre, especialidad, telefono, correo, password, rol) 
                        VALUES (?, ?, ?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([$nombre, $especialidad, $telefono, $correo, $passwordHash, $rol]);

                // Redirigir al panel principal con mensaje de éxito
                header("Location: panel_admin.php?msg=" . urlencode("Doctor $nombre creado correctamente."));
                exit;
            }
        } catch (PDOException $e) {
            $error = "Error DB: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Doctor - Admin</title>

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
        <span>Agregar Nuevo Doctor</span>
        <button class="btn btn-light d-lg-none" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
    </div>

    <div class="sidebar" id="sidebar">
        <h4>Corita’s Doctor</h4>
        <a href="panel_admin.php"><i class="bi bi-person-badge-fill"></i> Administrar Doctores</a>
        <a class="active" href="crear_doctor.php"><i class="bi bi-person-plus-fill"></i> Agregar Doctor</a>
        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>

    <div class="content">
        <h3 class="mb-4">Registro de Doctor</h3>

        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nombre completo</label>
                    <input name="nombre" type="text" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Especialidad</label>
                    <input name="especialidad" type="text" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Teléfono</label>
                    <input name="telefono" type="text" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input name="correo" type="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input name="password" type="text" class="form-control" required>
                    <div class="form-text">Se guardará de forma segura (hashed).</div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus-fill"></i> Registrar Doctor
                    </button>
                    <a href="panel_admin.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
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