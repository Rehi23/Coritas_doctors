<?php
// Script: api/vincular_paciente.php (FINAL)
require '../includes/conexion.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// 1. OBTENER DATOS (La App envía JSON, debe contener el ID del doctor)
$input = json_decode(file_get_contents("php://input"), true); 

$id_doctor = intval($input['id_doctor'] ?? 0); // ID del médico escaneado
$id_paciente_web = intval($input['id_paciente'] ?? 0); // ID del paciente registrado

if ($id_doctor <= 0 || $id_paciente_web <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos de vinculación incompletos o QR inválido."]);
    exit;
}

try {
    // 2. ASIGNAR EL PACIENTE AL DOCTOR
    $sql_vincular = "UPDATE pacientes SET id_doctor = ? WHERE id = ?";
    $stmt_vincular = $pdo_web->prepare($sql_vincular);
    
    if ($stmt_vincular->execute([$id_doctor, $id_paciente_web])) {
        
        echo json_encode([
            "status" => "success", 
            "message" => "Vinculación exitosa. Asignado al médico ID {$id_doctor}.",
        ]);
        
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Fallo al asignar el paciente."]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
}
?>