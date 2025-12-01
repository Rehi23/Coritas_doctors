<?php
session_start();
require '../includes/conexion.php';

// 1. VALIDACIÓN DE ACCESO
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$rol_usuario = $_SESSION['user_rol'] ?? 'doctor';
$user_id = $_SESSION['user_id'];

// Solo Doctores y Admins pueden agregar medicamentos
if ($rol_usuario !== 'doctor' && $rol_usuario !== 'admin') {
    header("Location: pacientes_view.php?error=" . urlencode("Permiso denegado."));
    exit();
}

// 2. PROCESAMIENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_med') {

    $id_paciente = intval($_POST['id_paciente'] ?? 0);
    $medicamento = trim($_POST['medicamento'] ?? '');
    $dosis = trim($_POST['dosis'] ?? '');
    $frecuencia = trim($_POST['frecuencia'] ?? '');
    $fecha_inicio = $_POST['fecha_inicio'] ?? null;

    if ($id_paciente <= 0 || empty($medicamento) || empty($dosis)) {
        $error = "Faltan datos obligatorios.";
        header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
        exit;
    }

    try {
        // 3. AUTORIZACIÓN ADICIONAL (Previene que un doctor agregue medicamentos a un paciente que no le pertenece)
        $is_authorized = ($rol_usuario === 'admin');

        if (!$is_authorized) {
            $check_auth = $pdo->prepare("SELECT id_doctor FROM pacientes WHERE id = ?");
            $check_auth->execute([$id_paciente]);
            $assigned_doctor_id = $check_auth->fetchColumn();

            if ($assigned_doctor_id == $user_id) {
                $is_authorized = true;
            }
        }

        if (!$is_authorized) {
            header("Location: pacientes_view.php?error=" . urlencode("Acceso denegado al historial de este paciente."));
            exit;
        }

        // 4. INSERTAR EN LA BASE DE DATOS
        $sql = "INSERT INTO historial_medicamentos (id_paciente, medicamento, dosis, frecuencia, fecha_inicio) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);

        if ($stmt->execute([$id_paciente, $medicamento, $dosis, $frecuencia, $fecha_inicio])) {
            $msg = "Medicamento agregado correctamente.";
            header("Location: perfil_paciente.php?id=$id_paciente&msg=" . urlencode($msg));
            exit;
        } else {
            $error = "Error al guardar el medicamento.";
            header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
            exit;
        }
    } catch (PDOException $e) {
        $error = "Error DB: " . $e->getMessage();
        header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
        exit;
    }
}
// Si no es POST, redirigir
header("Location: pacientes_view.php");
exit;
