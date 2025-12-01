<?php
session_start();
// RUTA DE CONEXIÓN: Salir de 'doctores/' y entrar a 'includes/'
require '../includes/conexion.php';

// 1. VALIDACIÓN DE ACCESO
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'doctor' && $_SESSION['user_rol'] !== 'admin')) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$rol_usuario = $_SESSION['user_rol'];
$citas = [];
$error_db = null;
$mensaje_ui = $_GET['msg'] ?? '';
$error_ui = $_GET['error'] ?? '';

// 2. OBTENER DATOS PRINCIPALES (Citas y Pacientes para Modal)
try {
    // A. Consulta para obtener CITAS (Filtrando por doctor o mostrando todas si es admin)
    $sql = "SELECT 
                c.id, c.fecha, c.hora, c.motivo, c.estado, 
                p.nombre AS nombre_paciente, p.id AS id_paciente 
            FROM 
                citas c
            JOIN 
                pacientes p ON c.id_paciente = p.id";

    $params = [];
    if ($rol_usuario !== 'admin') {
        $sql .= " WHERE c.id_doctor = ?";
        $params[] = $user_id;
    }

    $sql .= " ORDER BY c.fecha DESC, c.hora DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // B. CARGAR PACIENTES PARA EL MODAL AGENDAR CITA (Select dinámico)
    $sql_pacientes_modal = "SELECT id, nombre FROM pacientes";
    $params_pacientes = [];

    if ($rol_usuario !== 'admin') {
        $sql_pacientes_modal .= " WHERE id_doctor = ?";
        $params_pacientes[] = $user_id;
    }

    $sql_pacientes_modal .= " ORDER BY nombre ASC";

    $stmt_pacientes = $pdo->prepare($sql_pacientes_modal);
    $stmt_pacientes->execute($params_pacientes);
    $pacientes_modal = $stmt_pacientes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al cargar los datos: " . $e->getMessage();
    $citas = [];
    $pacientes_modal = [];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Corita's Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
            <a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
            <a href="codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
            <a href="../pacientes/pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
            <a class="active" href="citas_view.php"><i class="bi bi-calendar-event"></i> Citas</a>
        <?php endif; ?>

        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>

    <div class="topbar">
        <span>Citas Agendadas</span>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddCita"><i class="bi bi-plus"></i> Agendar Cita</button>
    </div>

    <div class="content">

        <?php if ($mensaje_ui): ?>
            <div class="alert alert-success"><?= htmlspecialchars($mensaje_ui) ?></div>
        <?php endif; ?>
        <?php if ($error_db || $error_ui): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_db ?: $error_ui) ?></div>
        <?php endif; ?>

        <table class="table table-hover info-card">
            <thead class="table-primary">
                <tr>
                    <th>Paciente</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Motivo</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citas)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted">No hay citas agendadas.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citas as $cita): ?>
                        <tr>
                            <td><?= htmlspecialchars($cita['nombre_paciente']) ?></td>
                            <td><?= date('d/m/Y', strtotime($cita['fecha'])) ?></td>
                            <td><?= htmlspecialchars($cita['hora']) ?></td>
                            <td><?= htmlspecialchars($cita['motivo']) ?></td>
                            <td><span class="badge bg-<?= ($cita['estado'] === 'pendiente' ? 'warning' : ($cita['estado'] === 'cancelada' ? 'danger' : 'success')) ?>"><?= ucfirst($cita['estado']) ?></span></td>
                            <td>
                                <button
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Editar Cita"
                                    onclick='openEditCita(<?= json_encode($cita, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="modalAddCita" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="agregar_cita.php" method="POST">
                    <input type="hidden" name="action" value="add_cita">

                    <div class="modal-header">
                        <h5 class="modal-title">Agendar Nueva Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Paciente</label>
                            <select name="id_paciente" class="form-select" required>
                                <option value="">Seleccione un paciente</option>
                                <?php foreach ($pacientes_modal as $pac): ?>
                                    <option value="<?= $pac['id'] ?>"><?= htmlspecialchars($pac['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input name="fecha" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora</label>
                            <input name="hora" type="time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo de la Cita</label>
                            <textarea name="motivo" class="form-control" rows="2" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditCita" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="formEditCita" action="procesar_cita.php" method="POST">
                    <input type="hidden" name="action" value="update_cita">
                    <input type="hidden" name="id_cita" id="editCitaId" value="">

                    <div class="modal-header">
                        <h5 class="modal-title">Editar/Actualizar Cita</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <h6 class="mb-3" id="citaPacienteNombre"></h6>

                        <div class="mb-3">
                            <label class="form-label">Fecha</label>
                            <input name="fecha" id="editCitaFecha" type="date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Hora</label>
                            <input name="hora" id="editCitaHora" type="time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <textarea name="motivo" id="editCitaMotivo" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" id="editCitaEstado" class="form-select" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="confirmada">Confirmada</option>
                                <option value="completada">Completada</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" id="btnDeleteCita" class="btn btn-danger me-auto"><i class="bi bi-trash"></i> Eliminar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
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

        // Función JS para abrir el modal de edición y llenar los campos
        function openEditCita(citaData) {
            // Asegúrate de que citas_view.php también obtenga el nombre_paciente
            document.getElementById('citaPacienteNombre').textContent = 'Paciente: ' + citaData.nombre_paciente;
            document.getElementById('editCitaId').value = citaData.id;
            document.getElementById('editCitaFecha').value = citaData.fecha;
            document.getElementById('editCitaHora').value = citaData.hora;
            document.getElementById('editCitaMotivo').value = citaData.motivo;
            document.getElementById('editCitaEstado').value = citaData.estado;

            // Mostrar el modal
            var modal = new bootstrap.Modal(document.getElementById('modalEditCita'));
            modal.show();

            // Lógica para el botón de Eliminar (usa un listener para enviar la acción 'delete')
            const deleteButton = document.getElementById('btnDeleteCita');
            deleteButton.onclick = function() {
                if (confirm("¿Estás seguro de que quieres eliminar esta cita?")) {
                    // Modifica la acción del formulario y añade el campo de acción 'delete'
                    const form = document.getElementById('formEditCita');
                    form.action = 'procesar_cita.php';

                    let actionInput = form.querySelector('input[name="action"]');
                    if (!actionInput) {
                        actionInput = document.createElement('input');
                        actionInput.type = 'hidden';
                        actionInput.name = 'action';
                        form.appendChild(actionInput);
                    }
                    actionInput.value = 'delete_cita';

                    // Remover el botón de submit 'Guardar Cambios' para evitar conflicto
                    form.querySelector('button[type="submit"]').click();
                }
            };
        }
    </script>
</body>

</html>