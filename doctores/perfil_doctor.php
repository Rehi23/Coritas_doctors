<?php
session_start();
require '../includes/conexion.php';

// 1. VALIDACIÓN DE SESIÓN
// Si no existe la sesión de doctor, redirigir al login
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'doctor' && $_SESSION['user_rol'] !== 'admin')) {
    header("Location: ../public/index.php");
    exit();
}

// ID del doctor de la sesión
$id_doctor = $_SESSION['user_id'];
$rol_usuario = $_SESSION['user_rol'];

// 2. OBTENER DATOS DEL DOCTOR
try {
    $sql = $pdo->prepare("SELECT id, nombre, especialidad, telefono, correo, rol FROM doctores WHERE id = ?");
    $sql->execute([$id_doctor]);
    $doctor = $sql->fetch(PDO::FETCH_ASSOC);

    // Si no encuentra el doctor, forzar cierre de sesión
    if (!$doctor) {
        session_destroy();
        header("Location: ../public/index.php?error=" . urlencode("Usuario no encontrado o sesión inválida.")); // 🚩 RUTA CORREGIDA
        exit();
    }

    // Usaremos las variables del array $doctor
    $cedula_profesional = "No disponible";
    $fecha_nacimiento = "N/A";
    $edad = "N/A";
} catch (PDOException $e) {
    die("Error al cargar perfil: " . $e->getMessage());
}

// 3. (OPCIONAL) REDIRECCIÓN DEL MENÚ SI ES ADMIN
// Si el admin está en su perfil de doctor, y queremos que use el panel de admin por defecto:
if ($rol_usuario === 'admin') {
    // Si queremos que el admin solo use el panel, redirigimos aquí.
    header("Location: ../panel_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Doctor - Corita's Doctor</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #F2F6FC;
            margin: 0;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            padding-top: 25px;
            border-right: 1px solid #c5d4e3;
            position: fixed;
            transition: 0.3s;
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

        /* Top bar */
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

        /* Content */
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
        }

        .section-title {
            font-weight: 700;
            color: #1B76D1;
            margin-bottom: 20px;
        }

        .label {
            font-size: 14px;
            font-weight: 600;
            color: #4c5d75;
            margin-bottom: 5px;
        }

        .value {
            font-size: 16px;
            color: #1f2d42;
            background: #eff6ff;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #c9dffd;
            font-weight: 500;
            width: 100%;
            margin-bottom: 15px;
        }

        .btn-edit {
            background-color: #1B76D1;
            color: white;
            font-weight: 600;
            padding: 10px 22px;
            border-radius: 10px;
            border: none;
            margin-top: 15px;
        }

        .btn-edit:hover {
            background-color: #0a5ca7;
        }

        /* Responsive */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.show {
                transform: translateX(0);
                z-index: 999;
            }

            .topbar,
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar" id="sidebar">
            <h4>Corita’s Doctor</h4>

        <?php if ($rol_usuario === 'admin'): ?>
            <a href="../panel_admin.php"><i class="bi bi-person-badge-fill"></i> Administrar Doctores</a>
            <a href="../pacientes/pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
        <?php else: ?>
            <a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a class="active" href="perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
            <a href="codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
            <a href="../pacientes/pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
        <?php endif; ?>

        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>

    <div class="content">
            <div class="profile-card">

                    <h4 class="section-title">Información del Doctor</h4>

            <div class="label">Rol en el sistema</div>
                    <div class="value"><?= htmlspecialchars(ucfirst($doctor['rol'])) ?></div>        
            <div class="label">Nombre completo</div>
                    <div class="value"><?= htmlspecialchars($doctor['nombre']) ?></div>        
            <div class="label">Especialidad</div>
                    <div class="value"><?= htmlspecialchars($doctor['especialidad']) ?></div>        
            <div class="label">Correo electrónico</div>
                    <div class="value"><?= htmlspecialchars($doctor['correo']) ?></div>        
            <div class="label">Cédula profesional</div>
                    <div class="value"><?= htmlspecialchars($cedula_profesional) ?></div>        
            <div class="label">Fecha de nacimiento</div>
                    <div class="value"><?= htmlspecialchars($fecha_nacimiento) ?></div>        
            <div class="label">Edad</div>
                    <div class="value"><?= htmlspecialchars($edad) ?></div>        
            <div class="label">Teléfono</div>
                    <div class="value"><?= htmlspecialchars($doctor['telefono']) ?></div>        
            <a href="editar_perfil_doctor.php" class="btn-edit"><i class="bi bi-pencil-square"></i> Editar perfil</a>

               
        </div>
    </div>

    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }
    </script>

</body>

</html>