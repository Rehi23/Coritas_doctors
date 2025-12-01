<?php
session_start();
require '../includes/conexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'doctor') {
    die(json_encode(["status" => "error", "message" => "No autorizado"]));
}

$id_doctor = $_SESSION['user_id'];
$id_paciente = intval($_POST['id_paciente'] ?? 0);
$mensaje = trim($_POST['mensaje'] ?? '');

if ($id_paciente > 0 && !empty($mensaje)) {
    $sql = "INSERT INTO mensajes (id_doctor, id_paciente, remitente, mensaje) VALUES (?, ?, 'doctor', ?)";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$id_doctor, $id_paciente, $mensaje])) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error"]);
    }
}
?>