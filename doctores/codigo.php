<?php
session_start();
// RUTA DE CONEXIÓN: Salir de 'doctores/' y entrar a 'includes/'
require '../includes/conexion.php';

// Tiempo de vida del token (Ejemplo: 2 minutos)
const QR_EXPIRATION_SECONDS = 120; // 2 minutos
const QR_EXPIRATION_TEXT = '2 minutos';

// 1. VALIDACIÓN DE ACCESO
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'doctor' && $_SESSION['user_rol'] !== 'admin')) {
    header("Location: ../public/index.php"); // Redirigir al login en public/
    exit();
}

$user_id = $_SESSION['user_id'];
$rol_usuario = $_SESSION['user_rol'];
$current_time = new DateTime('now');
$qr_token = null;

try {
    // 2. CARGAR DATOS ACTUALES DEL TOKEN
    $stmt = $pdo->prepare("SELECT qr_token, qr_expira FROM doctores WHERE id = ?");
    $stmt->execute([$user_id]);
    $doctor_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor_data) {
        die("Error: Datos de doctor no disponibles.");
    }

    $qr_expira_db = $doctor_data['qr_expira'];
    $qr_token_db = $doctor_data['qr_token'];
    $is_expired = true;

    // Verificar si el token existente es válido
    if ($qr_expira_db) {
        $expiration_time = new DateTime($qr_expira_db);
        if ($expiration_time > $current_time) {
            $qr_token = $qr_token_db;
            $is_expired = false;
        }
    }

    // 3. GENERAR NUEVO TOKEN SI ES NECESARIO
    if ($is_expired) {
        $qr_token = hash('sha256', $user_id . time() . rand(0, 9999));
        $new_expiration = (new DateTime('now'))->modify('+' . QR_EXPIRATION_SECONDS . ' seconds');
        $new_expiration_str = $new_expiration->format('Y-m-d H:i:s');

        // Guardar el nuevo token y expiración en la DB
        $stmt_update = $pdo->prepare("UPDATE doctores SET qr_token = ?, qr_expira = ? WHERE id = ?");
        $stmt_update->execute([$qr_token, $new_expiration_str, $user_id]);
        $qr_expira_js = $new_expiration_str; // Usar la nueva expiración para el JS
    } else {
        $qr_expira_js = $qr_expira_db; // Usar la expiración existente
    }

    // 4. PREPARAR CONTENIDO FINAL DEL QR
    // $app_verification_content = "CORITAS_DOCTOR_TOKEN=" . $qr_token;
    // $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($app_verification_content);

    // 🚩 SIMPLIFICACIÓN: Usamos el ID permanente del doctor, no un token temporal.
    $app_verification_content = "CORITAS_DOCTOR_ID=" . $user_id;
    $qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($app_verification_content);
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}

// 5. PREPARAR MARCA DE TIEMPO UNIX PARA JAVASCRIPT
// Esto nos da los milisegundos de la expiración para que JS no tenga dudas.
$expiration_timestamp_ms = (new DateTime($qr_expira_js))->getTimestamp() * 1000;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Código QR - Corita's Doctor</title>

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
            transition: .3s;
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
            transition: .2s;
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

        .content {
            margin-left: 250px;
            padding: 60px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .qr-container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
            width: 320px;
        }

        .qr-container img {
            width: 100%;
        }

        .desc-text {
            font-size: 15px;
            color: #4a5568;
            margin-top: 20px;
            max-width: 330px;
        }

        .expire-text {
            color: #E63946;
            margin-top: 10px;
            font-weight: 600;
            font-size: 14px;
        }

        @media(max-width:991px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .sidebar.show {
                transform: translateX(0);
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
            <a href="perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
            <a class="active" href="codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
            <a href="../pacientes/pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
            <a href="citas_view.php"><i class="bi bi-calendar-event"></i> Citas</a>
        <?php endif; ?>
        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>
    <div class="topbar">
        <span>Código QR</span>
        <button class="btn btn-light d-lg-none" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
    </div>
    <div class="content">
        <h4 class="fw-bold mb-4" style="color:#1B76D1;">Compartir Código de Acceso</h4>
        <div class="qr-container">
            [Image of Doctor QR Code]
            <img src="<?= $qr_image_url ?>" alt="Código QR del Doctor">
        </div>
        <p class="desc-text">
            Este código contiene un **token único y temporal** (ID: <?= $user_id ?>) asociado a tu perfil.
        </p>
        <p id="expireText" class="expire-text">
        </p>
    </div>
    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }

        // Lógica JavaScript para el temporizador y auto-recarga
        document.addEventListener('DOMContentLoaded', function() {
            // 🚩 CORRECCIÓN: Obtener la marca de tiempo UNIX (en milisegundos)
            const expirationTimeMs = <?= $expiration_timestamp_ms ?>;

            function updateTimer() {
                const now = new Date().getTime();
                // Calcular el tiempo restante en milisegundos
                let timeLeft = expirationTimeMs - now;

                if (timeLeft < 0) {
                    document.getElementById('expireText').innerHTML = '⚠️ El código ha expirado. Generando nuevo código...';
                    setTimeout(() => location.reload(), 1000);
                    return;
                }

                // Cálculo y Formateo a Minutos:Segundos
                let totalSeconds = Math.floor(timeLeft / 1000);
                let minutes = Math.floor(totalSeconds / 60);
                let seconds = totalSeconds % 60;

                let seconds_padded = seconds < 10 ? '0' + seconds : seconds;

                // Muestra el contador
                document.getElementById('expireText').innerHTML = `⏳ Código válido por ${minutes}:${seconds_padded} minutos`;

                setTimeout(updateTimer, 1000);
            }
            updateTimer();
        });
    </script>
</body>

</html>