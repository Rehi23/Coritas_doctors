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
$redirect_url = 'citas_view.php';

// 2. PROCESAMIENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_cita') {
    
    // Recibir y limpiar datos
    $id_doctor = $user_id;
    $id_paciente = intval($_POST['id_paciente'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';
    $hora = $_POST['hora'] ?? '';
    $motivo = trim($_POST['motivo'] ?? '');

    if ($id_paciente <= 0 || empty($fecha) || empty($hora) || empty($motivo)) {
        $error = "Faltan campos obligatorios o el paciente es inválido.";
        header("Location: $redirect_url?error=" . urlencode($error));
        exit;
    }

    try {
        // 3. AUTORIZACIÓN ADICIONAL (Prevenir que el doctor asigne una cita a un paciente que no le pertenece, si no es admin)
        if ($rol_usuario !== 'admin') {
             $check_auth = $pdo->prepare("SELECT id FROM pacientes WHERE id = ? AND id_doctor = ?");
             $check_auth->execute([$id_paciente, $user_id]);

             if (!$check_auth->fetch()) {
                 $error = "Permiso denegado: El paciente no está asignado a tu perfil.";
                 header("Location: $redirect_url?error=" . urlencode($error));
                 exit;
             }
        }

        // 4. INSERTAR EN LA BASE DE DATOS
        $sql = "INSERT INTO citas (id_doctor, id_paciente, fecha, hora, motivo, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$id_doctor, $id_paciente, $fecha, $hora, $motivo])) {
            $msg = "Cita agendada correctamente.";
            header("Location: $redirect_url?msg=" . urlencode($msg));
            exit;
        } else {
            $error = "Error al agendar la cita.";
            header("Location: $redirect_url?error=" . urlencode($error));
            exit;
        }

    } catch (PDOException $e) {
        $error = "Error DB: " . $e->getMessage();
        // Si el error es una violación de clave foránea (paciente o doctor no existe)
        if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
             $error = "Error al asignar: Asegúrate de que el ID del paciente exista.";
        }
        header("Location: $redirect_url?error=" . urlencode($error));
        exit;
    }
}

// Si se accede sin POST, redirigir
header("Location: $redirect_url");
exit;
?>