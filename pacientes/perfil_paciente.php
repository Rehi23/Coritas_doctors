<?php
session_start();
// RUTA CORREGIDA: Salir de 'pacientes/' y entrar a 'includes/'
require '../includes/conexion.php';

// 1. VALIDACIÓN DE ACCESO
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$rol_usuario = $_SESSION['user_rol'] ?? 'doctor';
$user_id = $_SESSION['user_id'];
$mensaje_update = $_GET['msg'] ?? ''; // Para mensajes de éxito/eliminación
$error_update = $_GET['error'] ?? ''; // Para mensajes de error

// 2. OBTENER ID DEL PACIENTE DE LA URL
$id_paciente = $_GET['id'] ?? 0;

if ($id_paciente <= 0) {
    // Si no hay ID, redirigir a la lista de pacientes
    header("Location: pacientes_view.php");
    exit();
}

// 3. LÓGICA DE ACTUALIZACIÓN DE PERFIL (Si se envió el formulario POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_info') {

    // Solo permitir edición a doctores y admins
    if ($rol_usuario !== 'doctor' && $rol_usuario !== 'admin') {
        $error_update = "Permiso denegado para editar.";
    } else {
        $nombre_new = trim($_POST['nombre'] ?? '');
        $correo_new = trim($_POST['correo'] ?? '');
        $diagnostico_new = trim($_POST['diagnostico_principal'] ?? '');

        if (empty($nombre_new) || empty($diagnostico_new)) {
            $error_update = "El nombre y el diagnóstico son obligatorios.";
        } else {
            try {
                // Ejecutar actualización
                $sql_update = "UPDATE pacientes SET nombre=?, correo=?, diagnostico_principal=? WHERE id=?";
                $stmt_update = $pdo->prepare($sql_update);

                if ($stmt_update->execute([$nombre_new, $correo_new, $diagnostico_new, $id_paciente])) {
                    // Redirigir con mensaje de éxito (previene reenvío del formulario)
                    header("Location: perfil_paciente.php?id=$id_paciente&msg=" . urlencode("Información del paciente actualizada correctamente."));
                    exit;
                } else {
                    $error_update = "Error al actualizar los datos.";
                }
            } catch (PDOException $e) {
                $error_update = "Error DB: " . $e->getMessage();
            }
        }
    }
}
// --- FIN LÓGICA DE ACTUALIZACIÓN ---


// 4. AUTORIZACIÓN Y CARGA DE DATOS DEL PACIENTE (GET y POST-Update)
try {
    // Consulta base: Cargar paciente por ID
    $sql_paciente = "SELECT id, nombre, correo, diagnostico_principal, id_doctor FROM pacientes WHERE id = ?";

    // Si NO es administrador, debe filtrar por ID de doctor (autorización de datos)
    if ($rol_usuario !== 'admin') {
        $sql_paciente .= " AND id_doctor = ?";
        $stmt_paciente = $pdo->prepare($sql_paciente);
        $stmt_paciente->execute([$id_paciente, $user_id]);
    } else {
        // Si es administrador, solo necesita el ID del paciente
        $stmt_paciente = $pdo->prepare($sql_paciente);
        $stmt_paciente->execute([$id_paciente]);
    }

    $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

    // Si el paciente no existe o el doctor no está autorizado
    if (!$paciente) {
        header("Location: pacientes_view.php?error=" . urlencode("Paciente no encontrado o acceso denegado."));
        exit();
    }

    // 5. CARGAR HISTORIAL DE MEDICAMENTOS (Incluir ID para eliminar)
    // En la línea 56 de perfil_paciente.php (Carga Historial de Medicamentos)
    $sql_historial = "SELECT id, medicamento, dosis, frecuencia, fecha_inicio FROM historial_medicamentos WHERE id_paciente = ? ORDER BY fecha_inicio DESC";
    $stmt_historial = $pdo->prepare($sql_historial);
    $stmt_historial->execute([$id_paciente]);
    $medicamentos = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

    // Variables derivadas
    $edad = "N/A"; // Debes calcular o cargar este valor
    $letra_inicial = strtoupper(substr($paciente['nombre'], 0, 1));
} catch (PDOException $e) {
    die("Error al cargar datos del paciente: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?= htmlspecialchars($paciente['nombre']) ?> - Corita's Doctor</title>

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
            text-align: center;
            color: #1B76D1;
            font-weight: 800;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #333;
            font-weight: 500;
            text-decoration: none;
            transition: .2s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #1B76D1;
            color: white;
            border-radius: 10px;
        }

        /* Topbar */
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

        /* Content layout */
        .content {
            margin-left: 250px;
            padding: 20px;
        }

        .card-patient {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #d8eaff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 36px;
            color: #1B76D1;
            font-weight: bold;
        }

        table tbody tr:hover {
            background: #eaf4ff;
        }

        @media(max-width: 991px) {
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

    <div class="sidebar" id="sb">
        <h4>Corita’s Doctor</h4>

        <?php if ($rol_usuario === 'admin'): ?>
            <a href="../panel_admin.php"><i class="bi bi-person-badge-fill"></i> Administrar Doctores</a>
            <a href="pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
        <?php else: ?>
            <a href="../doctores/dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="../doctores/perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
            <a href="../doctores/codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
            <a href="pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
        <?php endif; ?>
        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i>Salir</a>
    </div>

    <div class="topbar">
            <button class="btn btn-light d-lg-none me-2" onclick="toggleMenu()">
                    <i class="bi bi-list"></i>
                </button>
            Perfil del Paciente: <?= htmlspecialchars($paciente['nombre']) ?>
    </div>

    <div class="content">

        <?php if ($mensaje_update): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_update) ?></div>
        <?php endif; ?>

        <?php if ($error_update): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_update) ?></div>
        <?php endif; ?>

        <div class="card-patient">
            <form method="POST">
                <input type="hidden" name="action" value="update_info">

                <div class="d-flex align-items-center gap-4 mb-4">
                    <div class="profile-photo"><?= $letra_inicial ?></div>
                    <div>
                        <h5 class="m-0">Editar Perfil</h5>
                        <small class="text-muted">ID: <?= $id_paciente ?></small>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($paciente['nombre']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Correo</label>
                    <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($paciente['correo']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Diagnóstico Principal</label>
                    <input type="text" name="diagnostico_principal" class="form-control" value="<?= htmlspecialchars($paciente['diagnostico_principal']) ?>" required>
                    <div class="form-text">Usado como etiqueta de identificación.</div>
                </div>

                <button type="submit" class="btn btn-primary mt-3"><i class="bi bi-save"></i> Guardar Cambios</button>
            </form>
        </div>

        <div class="card-patient">
            <div class="d-flex justify-content-between mb-3">
                <h6 class="m-0">Historial de Medicamentos</h6>
                <?php if ($rol_usuario === 'doctor' || $rol_usuario === 'admin'): ?>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddMed"><i class="bi bi-plus"></i> Agregar</button>
                <?php endif; ?>
            </div>

            <table class="table table-hover">
                <thead class="table-primary">
                    <tr>
                        <th>Medicamento</th>
                        <th>Dosis</th>
                        <th>Frecuencia</th>
                        <th>Inicio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicamentos)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No hay medicamentos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medicamentos as $med): ?>
                            <tr>
                                <td><?= htmlspecialchars($med['medicamento']) ?></td>
                                <td><?= htmlspecialchars($med['dosis']) ?></td>
                                <td><?= htmlspecialchars($med['frecuencia']) ?></td>
                                <td><?= htmlspecialchars($med['fecha_inicio'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($rol_usuario === 'doctor' || $rol_usuario === 'admin'): ?>
                                        <button
                                            class="btn btn-warning btn-sm me-2"
                                            title="Editar"
                                            onclick='openEditMed(<?= json_encode($med, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form action="eliminar_medicamento.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar medicamento?');">
                                            <input type="hidden" name="id_medicamento" value="<?= $med['id'] ?>">
                                            <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="modal fade" id="modalAddMed" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="agregar_medicamento.php" method="POST">
                    <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">
                    <input type="hidden" name="action" value="add_med">

                    <div class="modal-header">
                        <h5 class="modal-title">Agregar Nuevo Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Medicamento</label>
                            <input name="medicamento" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosis</label>
                            <input name="dosis" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frecuencia</label>
                            <input name="frecuencia" type="text" class="form-control" placeholder="Ej: 1 vez al día, cada 8 horas" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Inicio</label>
                            <input name="fecha_inicio" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Historial</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditMed" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditMed" action="editar_medicamento.php" method="POST">
                    <input type="hidden" name="id_paciente" value="<?= $id_paciente ?>">
                    <input type="hidden" name="id_medicamento" id="editIdMed" value="">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Medicamento</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Medicamento</label>
                            <input name="medicamento" id="editMedMedicamento" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dosis</label>
                            <input name="dosis" id="editMedDosis" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Frecuencia</label>
                            <input name="frecuencia" id="editMedFrecuencia" type="text" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de Inicio</label>
                            <input name="fecha_inicio" id="editMedFechaInicio" type="date" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Guardar Edición</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById("sb").classList.toggle("show");
        }
        // Función JS para abrir el modal de edición y llenar los campos
        function openEditMed(medData) {
            document.getElementById('editIdMed').value = medData.id;
            document.getElementById('editMedMedicamento').value = medData.medicamento;
            document.getElementById('editMedDosis').value = medData.dosis;
            document.getElementById('editMedFrecuencia').value = medData.frecuencia;
            document.getElementById('editMedFechaInicio').value = medData.fecha_inicio;

            var modal = new bootstrap.Modal(document.getElementById('modalEditMed'));
            modal.show();
        }
    </script>

</body>

</html>