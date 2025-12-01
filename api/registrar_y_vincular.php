<?php
// Script: api/registrar_y_vincular.php
require '../includes/conexion.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido."]);
    exit;
}

// 1. OBTENER DATOS DE LA APLICACIÓN MÓVIL
$qr_token = $_POST['token'] ?? '';
$nombre_paciente = $_POST['nombre'] ?? '';
$correo_paciente = $_POST['correo'] ?? '';
$diagnostico_inicial = "Registro por QR"; // Diagnóstico temporal

if (empty($qr_token) || empty($nombre_paciente) || empty($correo_paciente)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos de registro incompletos."]);
    exit;
}

try {
    // 2. VALIDAR EL TOKEN Y ENCONTRAR AL MÉDICO (DB WEB: $pdo_web)
    $current_time = (new DateTime('now'))->format('Y-m-d H:i:s');
    
    $stmt_doctor = $pdo_web->prepare("SELECT id, qr_expira FROM doctores WHERE qr_token = ?");
    $stmt_doctor->execute([$qr_token]);
    $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token inválido. Contacte a su médico."]);
        exit;
    }
    
    // Verificar Expiración
    if ($doctor['qr_expira'] < $current_time) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token expirado. Pida a su médico generar uno nuevo."]);
        exit;
    }
    
    $id_doctor = $doctor['id'];

    // 3. REGISTRAR AL PACIENTE Y ASIGNAR AL DOCTOR
    // Asegurarse de que el paciente no exista para evitar duplicados
    $stmt_check = $pdo_web->prepare("SELECT id FROM pacientes WHERE correo = ?");
    $stmt_check->execute([$correo_paciente]);

    if ($stmt_check->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(["status" => "warning", "message" => "Este correo ya existe. Simplemente se vinculará."]);
        // Aquí podrías agregar la lógica de UPDATE si ya existe.
    } else {
        $sql_insert = "INSERT INTO pacientes (nombre, correo, diagnostico_principal, id_doctor) VALUES (?, ?, ?, ?)";
        $stmt_insert = $pdo_web->prepare($sql_insert);
        
        if (!$stmt_insert->execute([$nombre_paciente, $correo_paciente, $diagnostico_inicial, $id_doctor])) {
             http_response_code(500);
             echo json_encode(["status" => "error", "message" => "Fallo al crear el registro del paciente."]);
             exit;
        }
        
        // Opcional: Invalidar el token inmediatamente después de usarlo (seguridad)
        $stmt_invalidate = $pdo_web->prepare("UPDATE doctores SET qr_expira = NOW() WHERE id = ?"); 
        $stmt_invalidate->execute([$id_doctor]);

        echo json_encode([
            "status" => "success", 
            "message" => "Registro exitoso. Vinculado al médico ID {$id_doctor}.",
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
}
?>