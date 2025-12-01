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

// 2. RECUPERAR DATOS
$id_paciente = intval($_POST['id_paciente'] ?? 0);
$id_medicamento = intval($_POST['id_medicamento'] ?? 0);
$medicamento = trim($_POST['medicamento'] ?? '');
$dosis = trim($_POST['dosis'] ?? '');
$frecuencia = trim($_POST['frecuencia'] ?? '');
$fecha_inicio = $_POST['fecha_inicio'] ?? null;

if ($id_medicamento <= 0 || $id_paciente <= 0 || empty($medicamento) || empty($dosis)) {
    $error = "Faltan datos obligatorios o IDs inválidos.";
    header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
    exit;
}

try {
    // 3. AUTORIZACIÓN: Verificar que el doctor está asignado al paciente o es admin
    $is_authorized = ($rol_usuario === 'admin');
    
    if (!$is_authorized) {
         // Comprobar si el paciente pertenece al doctor
         $check_auth = $pdo->prepare("SELECT id_doctor FROM pacientes WHERE id = ?");
         $check_auth->execute([$id_paciente]);
         $assigned_doctor_id = $check_auth->fetchColumn();

         if ($assigned_doctor_id == $user_id) {
             $is_authorized = true;
         }
    }
    
    if (!$is_authorized) {
        $error = "Acceso denegado al historial de este paciente.";
        header("Location: pacientes_view.php?error=" . urlencode($error));
        exit;
    }

    // 4. EJECUTAR ACTUALIZACIÓN
    $sql = "UPDATE historial_medicamentos SET medicamento=?, dosis=?, frecuencia=?, fecha_inicio=? WHERE id=? AND id_paciente=?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$medicamento, $dosis, $frecuencia, $fecha_inicio, $id_medicamento, $id_paciente])) {
        $msg = "Medicamento actualizado correctamente.";
        header("Location: perfil_paciente.php?id=$id_paciente&msg=" . urlencode($msg));
        exit;
    } else {
        $error = "Error al actualizar el medicamento.";
        header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
        exit;
    }

} catch (PDOException $e) {
    $error = "Error DB: " . $e->getMessage();
    header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
    exit;
}