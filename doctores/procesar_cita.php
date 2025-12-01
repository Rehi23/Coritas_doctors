<?php
session_start();
require '../includes/conexion.php'; 

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] === 'invitado') {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$rol_usuario = $_SESSION['user_rol'];
$redirect_url = 'citas_view.php';
$id_cita = intval($_POST['id_cita'] ?? 0);
$action = $_POST['action'] ?? '';

if ($id_cita <= 0) {
    header("Location: $redirect_url?error=" . urlencode("ID de cita inválido."));
    exit;
}

// 1. AUTORIZACIÓN: Solo el doctor asignado o un admin pueden modificar la cita.
try {
    $sql_auth = "SELECT id_doctor FROM citas WHERE id = ?";
    $stmt_auth = $pdo->prepare($sql_auth);
    $stmt_auth->execute([$id_cita]);
    $cita_doctor_id = $stmt_auth->fetchColumn();

    $is_authorized = ($rol_usuario === 'admin' || $cita_doctor_id == $user_id);

    if (!$is_authorized) {
        header("Location: $redirect_url?error=" . urlencode("No tienes permiso para modificar esta cita."));
        exit;
    }
} catch (PDOException $e) {
    header("Location: $redirect_url?error=" . urlencode("Error de base de datos en autorización."));
    exit;
}


// 2. PROCESAR ACCIONES (UPDATE o DELETE)
try {
    if ($action === 'update_cita') {
        // --- ACCIÓN ACTUALIZAR ---
        $fecha = $_POST['fecha'] ?? '';
        $hora = $_POST['hora'] ?? '';
        $motivo = $_POST['motivo'] ?? '';
        $estado = $_POST['estado'] ?? 'pendiente';

        $sql = "UPDATE citas SET fecha=?, hora=?, motivo=?, estado=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha, $hora, $motivo, $estado, $id_cita]);
        
        $msg = "Cita actualizada correctamente.";
        header("Location: $redirect_url?msg=" . urlencode($msg));
        exit;

    } elseif ($action === 'delete_cita') {
        // --- ACCIÓN ELIMINAR ---
        $sql = "DELETE FROM citas WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_cita]);
        
        $msg = "Cita eliminada correctamente.";
        header("Location: $redirect_url?msg=" . urlencode($msg));
        exit;
    }

} catch (PDOException $e) {
    $error = "Error al procesar la cita: " . $e->getMessage();
    header("Location: $redirect_url?error=" . urlencode($error));
    exit;
}

header("Location: $redirect_url");
exit;