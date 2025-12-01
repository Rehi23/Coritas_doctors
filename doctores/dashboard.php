<?php
session_start();
// RUTA DE CONEXIÓN: Salir de 'doctores/' y entrar a 'includes/'
require '../includes/conexion.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'doctor' && $_SESSION['user_rol'] !== 'admin')) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_nombre = $_SESSION['user_nombre'];
$rol_usuario = $_SESSION['user_rol'];

// 1. OBTENER CONTADORES DINÁMICOS Y DATOS DEL GRÁFICO
try {
    // A. Contar Pacientes Asignados
    if ($rol_usuario === 'admin') {
        $sql_pacientes = "SELECT COUNT(id) FROM pacientes";
        $stmt_pacientes = $pdo->query($sql_pacientes);
    } else {
        $sql_pacientes = "SELECT COUNT(id) FROM pacientes WHERE id_doctor = ?";
        $stmt_pacientes = $pdo->prepare($sql_pacientes);
        $stmt_pacientes->execute([$user_id]);
    }
    $num_pacientes = $stmt_pacientes->fetchColumn();

    // B. Contar Citas Pendientes
    $sql_citas = "SELECT COUNT(id) FROM citas WHERE estado = 'pendiente'";
    $params_citas = [];
    if ($rol_usuario !== 'admin') {
        $sql_citas .= " AND id_doctor = ?";
        $params_citas[] = $user_id;
    }

    $stmt_citas = $pdo->prepare($sql_citas);
    $stmt_citas->execute($params_citas);
    $citas_pendientes = $stmt_citas->fetchColumn();

    // C. OBTENER DATOS PARA GRÁFICO: Pacientes por Diagnóstico
    $sql_diagnosticos = "SELECT 
                            diagnostico_principal, 
                            COUNT(id) as total 
                        FROM 
                            pacientes";

    $params_diagnosticos = [];

    if ($rol_usuario !== 'admin') {
        $sql_diagnosticos .= " WHERE id_doctor = ?";
        $params_diagnosticos[] = $user_id;
    }

    $sql_diagnosticos .= " GROUP BY diagnostico_principal ORDER BY total DESC LIMIT 5";

    $stmt_diagnosticos = $pdo->prepare($sql_diagnosticos);
    $stmt_diagnosticos->execute($params_diagnosticos);
    $data_diagnosticos = $stmt_diagnosticos->fetchAll(PDO::FETCH_ASSOC);

    // Formatear los datos para JavaScript
    $chart_labels = json_encode(array_column($data_diagnosticos, 'diagnostico_principal'));
    $chart_data = json_encode(array_column($data_diagnosticos, 'total'));
} catch (PDOException $e) {
    $num_pacientes = "Error";
    $citas_pendientes = "Error";
    $chart_labels = '[]';
    $chart_data = '[]';
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Corita's Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <style>
        /* Estilos CSS Base (Ajusta con tus estilos principales) */
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

        .content {
            margin-left: 250px;
            padding: 25px;
        }

        .info-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .card-title {
            font-size: 1.5rem;
            color: #1B76D1;
            font-weight: 600;
        }

        .card-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #333;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-250px);
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
            <a class="active" href="dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
            <a href="codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
            <a href="../pacientes/pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
            <a href="citas_view.php"><i class="bi bi-calendar-event"></i> Citas</a>
        <?php endif; ?>

        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>

    <div class="topbar">
        <span>Bienvenido, Dr. <?= htmlspecialchars($user_nombre) ?>!</span>
        <button class="btn btn-light d-lg-none" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
    </div>

    <div class="content container-fluid">
        <h3 class="mb-4">Resumen del Panel</h3>

        <div class="row">

            <div class="col-md-6 col-lg-4 mb-4">
                <div class="info-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="card-title">Pacientes Asignados</div>
                            <div class="card-value"><?= $num_pacientes ?></div>
                        </div>
                        <i class="bi bi-people-fill" style="font-size: 3rem; color: #1B76D1;"></i>
                    </div>
                    <a href="../pacientes/pacientes_view.php" class="btn btn-sm btn-outline-primary mt-3">Ver lista</a>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 mb-4">
                <div class="info-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="card-title">Citas Pendientes</div>
                            <div class="card-value"><?= $citas_pendientes ?></div>
                        </div>
                        <i class="bi bi-calendar-check" style="font-size: 3rem; color: #28a745;"></i>
                    </div>
                    <a href="citas_view.php" class="btn btn-sm btn-outline-success mt-3">Ver calendario</a>
                </div>
            </div>

        </div>

        <div class="row">
            <div class="col-md-12 col-lg-8 mb-4">
                <div class="info-card">
                    <div class="card-title mb-3">Top 5 Diagnósticos de Pacientes</div>
                    <div style="max-height: 400px; max-width: 800px;">
                        <canvas id="diagnosticosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('diagnosticosChart');

            // Datos generados por PHP
            const labels = <?= $chart_labels ?>;
            const dataCounts = <?= $chart_data ?>;

            // Solo dibujar si hay datos
            if (dataCounts.length > 0) {
                new Chart(ctx, {
                    type: 'bar', // Tipo de gráfico: barras
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Número de Pacientes',
                            data: dataCounts,
                            backgroundColor: [
                                'rgba(27, 118, 209, 0.8)', // Azul Principal
                                'rgba(40, 167, 69, 0.8)', // Verde
                                'rgba(255, 193, 7, 0.8)', // Amarillo
                                'rgba(220, 53, 69, 0.8)', // Rojo
                                'rgba(108, 117, 125, 0.8)' // Gris
                            ],
                            borderColor: '#1B76D1',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0 // Asegura que los conteos sean enteros
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>