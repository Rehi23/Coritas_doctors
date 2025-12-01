<?php
error_reporting(0); // Suprime todos los errores de salida.
ob_clean(); // Limpia cualquier output buffering si existe (CRÍTICO para evitar que se envíe basura como <br />)

require '../includes/conexion.php';

// Establecer encabezados para respuesta JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido. Use POST."]);
    exit;
}

// 1. OBTENER DATOS (La App envía JSON, PHP lo lee)
$input = json_decode(file_get_contents("php://input"), true);

$nombre = $input['nombre'] ?? '';
$correo = $input['correo'] ?? '';
$telefono = $input['telefono'] ?? '';
$password = $input['password'] ?? '';
$diagnostico_inicial = "Registro por QR";

if (empty($nombre) || empty($correo) || empty($password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["success" => false, "message" => "Faltan campos obligatorios."]);
    exit;
}

// ----------------------------------------------------
// LÓGICA DE REGISTRO (Mantenemos la lógica de la DB WEB)
// ----------------------------------------------------
try {
    // Verificar correo único (omitiendo id_doctor por ahora)
    $stmt_check = $pdo->prepare("SELECT id FROM pacientes WHERE correo = ?");
    $stmt_check->execute([$correo]);

    if ($stmt_check->fetch()) {
        http_response_code(409); // Conflict
        echo json_encode(["success" => false, "message" => "Este correo ya está registrado."]);
        exit;
    }

    // Insertar el paciente
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql_insert = "INSERT INTO pacientes (nombre, correo, telefono, diagnostico_principal, password, id_doctor) 
                VALUES (?, ?, ?, ?, ?, NULL)"; // <- Esto ahora será permitido

    $stmt_insert = $pdo->prepare($sql_insert);

    if ($stmt_insert->execute([$nombre, $correo, $telefono, $diagnostico_inicial, $passwordHash])) {

        $id_paciente = $pdo->lastInsertId();

        echo json_encode([
            "success" => true,
            "message" => "Cuenta creada. ¡Bienvenido!",
            "access_token" => "MOCK_TOKEN_{$id_paciente}",
            "token_type" => "bearer"
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Fallo al crear la cuenta en la DB."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error interno del servidor: " . $e->getMessage()]);
}
