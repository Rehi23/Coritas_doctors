<?php
session_start();
require '../includes/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$rol_usuario = $_SESSION['user_rol'] ?? 'doctor'; // Obtener el rol de la sesión
$user_id = $_SESSION['user_id']; // ID del doctor/administrador logueado
$pacientes = [];
$error_db = null;

// 1. CAPTURAR Y PREPARAR LA BÚSQUEDA
$search_query = trim($_GET['q'] ?? ''); // Capturar el término de búsqueda 'q'
$params = []; // Array para los parámetros de la consulta preparada

try {
    // 2. CONSTRUIR LA CONSULTA BASE Y EL FILTRO DE AUTORIZACIÓN
    $sql = "SELECT id, nombre, correo, diagnostico_principal FROM pacientes";
    $where_clauses = [];

    // FILTRO DE AUTORIZACIÓN (Doctor vs Admin)
    if ($rol_usuario !== 'admin') {
        $where_clauses[] = "id_doctor = ?";
        $params[] = $user_id;
    }
    // FILTRO DE BÚSQUEDA (Si hay un término 'q')
    if (!empty($search_query)) {
        // Buscar en nombre, correo y diagnóstico
        $where_clauses[] = "(nombre LIKE ? OR correo LIKE ? OR diagnostico_principal LIKE ?)";
        $search_term = "%" . $search_query . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    // AÑADIR CONDICIONES WHERE
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY nombre ASC";

    // 3. EJECUTAR LA CONSULTA
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al cargar los pacientes: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
       
    <meta charset="UTF-8">
       
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pacientes - Corita's Doctor</title>

       
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

        .patient-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            cursor: pointer;
            transition: .2s;
        }

        .patient-card:hover {
            transform: scale(1.02);
            background-color: #e9f3ff;
        }

        .patient-name {
            font-size: 17px;
            font-weight: 600;
            color: #002b55;
        }

        .patient-email {
            font-size: 14px;
            color: #60718c;
        }

        .patient-tag {
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

            .sidebar.show {
                transform: translateX(0);
                z-index: 999;
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
        <div class="sidebar" id="sidebar">
                    <h4>Corita’s Doctor</h4>

                <?php if ($rol_usuario === 'admin'): ?>
                        <a href="../admin/panel_admin.php"><i class="bi bi-person-badge-fill"></i> Administrar Doctores</a>
                        <a class="active" href="../pacientes/perfil_paciente.php"><i class="bi bi-people-fill"></i> Pacientes</a>
                    <?php else: ?>
                        <a href="../doctores/dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
                        <a href="../doctores/perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
                        <a href="../doctores/codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
                        <a class="active" href="pacientes.php"><i class="bi bi-people-fill"></i> Pacientes</a>
                    <?php endif; ?>

                    <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
            </div>

        <div class="topbar">
                    <span>Pacientes</span>
                    <button class="btn btn-light d-lg-none" onclick="toggleMenu()">
                                <i class="bi bi-list"></i>
                            </button>
    </div>

    <div class="search-wrapper">
        <div class="d-flex justify-content-between align-items-center">
            <form method="GET" action="pacientes_view.php" class="w-50 d-flex gap-2">
                <input type="text" name="q" id="searchInput" class="form-control" placeholder="Buscar paciente..." value="<?= htmlspecialchars($search_query) ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                <?php if (!empty($search_query)): ?>
                    <a href="pacientes_view.php" class="btn btn-secondary" title="Limpiar búsqueda"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </form>

            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddPatient"><i class="bi bi-plus-lg"></i> Agregar Paciente</button>
        </div>
    </div>

        <div class="content">

                <?php if (isset($error_db)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error_db) ?></div>
                    <?php elseif (empty($pacientes)): ?>
                        <div class="alert alert-info">No tienes pacientes asignados.</div>
                    <?php else: ?>

                        <?php foreach ($pacientes as $paciente): ?>
                                <a href="perfil_paciente.php?id=<?= $paciente['id'] ?>" style="text-decoration:none; color:inherit;">
                                        <div class="patient-card">
                                                <div>
                                                        <div class="patient-name"><?= htmlspecialchars($paciente['nombre']) ?></div>
                                                        <div class="patient-email"><?= htmlspecialchars($paciente['correo']) ?></div>
                                                    </div>
                                                <span class="patient-tag"><?= htmlspecialchars($paciente['diagnostico_principal']) ?></span>
                                            </div>
                                    </a>
                            <?php endforeach; ?>
                    <?php endif; ?>
            </div>
    <div class="modal fade" id="modalAddPatient" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="agregar_paciente.php" method="POST">
                    <input type="hidden" name="action" value="add_patient">

                    <div class="modal-header">
                        <h5 class="modal-title">Registrar Nuevo Paciente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nombre completo</label>
                            <input name="nombre" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Correo electrónico</label>
                            <input name="correo" type="email" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Diagnóstico Principal</label>
                            <input name="diagnostico_principal" type="text" class="form-control" placeholder="Ej: Hipertensión" required>
                            <div class="form-text">Este será el identificador en la lista.</div>
                        </div>

                        <?php if ($rol_usuario === 'admin'): ?>
                            <div class="mb-3">
                                <label class="form-label">Asignar a Doctor (ID)</label>
                                <input name="id_doctor_asignado" type="number" class="form-control" placeholder="Ingrese ID del doctor" required>
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="id_doctor_asignado" value="<?= $user_id ?>">
                        <?php endif; ?>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Registrar Paciente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }

        // LÓGICA DE BÚSQUEDA EN TIEMPO REAL
        document.addEventListener('DOMContentLoaded', function() {
            // Asegúrate de que el input de búsqueda en el HTML tiene id="searchInput"
            const searchInput = document.getElementById('searchInput');

            // Selecciona todos los enlaces (etiquetas <a>) que contienen la tarjeta de paciente
            const patientLinks = document.querySelectorAll('.content a');

            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim().toLowerCase();

                patientLinks.forEach(link => {
                    const card = link.querySelector('.patient-card');

                    // Si la tarjeta no existe o está mal estructurada, saltar
                    if (!card) return;

                    // Obtener el nombre y el diagnóstico del paciente
                    const name = card.querySelector('.patient-name').textContent.toLowerCase();
                    const tag = card.querySelector('.patient-tag').textContent.toLowerCase();

                    // Verificar si la consulta coincide con el nombre o el diagnóstico
                    if (name.includes(query) || tag.includes(query)) {
                        link.style.display = ''; // Mostrar el enlace (y la tarjeta)
                    } else {
                        link.style.display = 'none'; // Ocultar el enlace
                    }
                });
            });
        });
    </script>
</body>
</html>