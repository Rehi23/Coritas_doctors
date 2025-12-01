<?php
session_start();
// RUTA DE CONEXIÓN: Salir de 'pacientes/' y entrar a 'includes/'
require '../includes/conexion.php'; 

// 1. VALIDACIÓN DE ACCESO
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php");
    exit();
}

$rol_usuario = $_SESSION['user_rol'] ?? 'doctor';
$user_id = $_SESSION['user_id'];
$redirect_url = 'pacientes_view.php'; 

// 2. PROCESAMIENTO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add_patient') {
    
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $diagnostico = trim($_POST['diagnostico_principal'] ?? '');
    $id_doctor_asignado = intval($_POST['id_doctor_asignado'] ?? 0);

    // Si el rol es DOCTOR, el ID de asignación debe ser el propio ID de la sesión
    if ($rol_usuario === 'doctor') {
        $id_doctor_asignado = $user_id;
    }

    if (empty($nombre) || empty($diagnostico) || $id_doctor_asignado <= 0) {
        $error = "Faltan datos obligatorios (Nombre, Diagnóstico o ID de Doctor).";
        header("Location: $redirect_url?error=" . urlencode($error));
        exit;
    }

    try {
        // 3. INSERTAR EN LA BASE DE DATOS
        $sql = "INSERT INTO pacientes (nombre, correo, diagnostico_principal, id_doctor) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nombre, $correo, $diagnostico, $id_doctor_asignado])) {
            $msg = "Paciente registrado y asignado correctamente.";
            header("Location: $redirect_url?msg=" . urlencode($msg));
            exit;
        } else {
            $error = "Error al guardar el paciente.";
            header("Location: $redirect_url?error=" . urlencode($error));
            exit;
        }

    } catch (PDOException $e) {
        $error = "Error DB: " . $e->getMessage();
        header("Location: $redirect_url?error=" . urlencode($error));
        exit;
    }
}

// Si se accede sin POST, redirigir
header("Location: $redirect_url");
exit;