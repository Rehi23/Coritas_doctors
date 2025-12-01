<?php
session_start();
// 🚩 RUTA DE CONEXIÓN: Salir de 'pacientes/' y entrar a 'includes/'
require '../includes/conexion.php'; 

// 1. VALIDACIÓN DE ACCESO
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$rol_usuario = $_SESSION['user_rol'] ?? 'doctor';
$user_id = $_SESSION['user_id'];
$id_paciente = intval($_POST['id_paciente'] ?? 0);
$id_medicamento = intval($_POST['id_medicamento'] ?? 0);

if ($id_medicamento <= 0 || $id_paciente <= 0) {
    header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode("ID de medicamento o paciente inválido."));
    exit;
}

// 2. AUTORIZACIÓN: Verificar que el doctor está asignado al paciente o es admin
try {
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
        header("Location: pacientes_view.php?error=" . urlencode("Acceso denegado al historial de este paciente."));
        exit;
    }

    // 3. EJECUTAR ELIMINACIÓN
    $sql = "DELETE FROM historial_medicamentos WHERE id = ? AND id_paciente = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id_medicamento, $id_paciente])) {
        $msg = "Medicamento eliminado correctamente.";
        header("Location: perfil_paciente.php?id=$id_paciente&msg=" . urlencode($msg));
        exit;
    } else {
        $error = "Error al eliminar el medicamento.";
        header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
        exit;
    }

} catch (PDOException $e) {
    $error = "Error DB: " . $e->getMessage();
    header("Location: perfil_paciente.php?id=$id_paciente&error=" . urlencode($error));
    exit;
}