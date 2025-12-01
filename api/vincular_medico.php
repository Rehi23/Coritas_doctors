<?php
// Este script es un API endpoint y NO usa sesión (session_start)
// Requiere la conexión doble a ambas bases de datos
require '../includes/conexion.php';

// Establecer encabezados para respuesta JSON
header('Content-Type: application/json');

// Solo aceptar peticiones POST (de la aplicación móvil)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Método no permitido. Use POST."]);
    exit;
}

// 1. OBTENER DATOS DE LA APLICACIÓN MÓVIL
// El token es el contenido del QR. 
// El ID del paciente es el identificador que la app usa para el paciente en la DB WEB.
$qr_token = $_POST['token'] ?? '';
$id_paciente_web = intval($_POST['id_paciente'] ?? 0); // ID del paciente en la tabla 'pacientes' (corita_db_web)

if (empty($qr_token) || $id_paciente_web <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Datos de vinculación incompletos (Token o ID de Paciente)."]);
    exit;
}

try {
    // 2. VALIDAR EL TOKEN Y TIEMPO DE EXPIRACIÓN (Usando DB WEB: $pdo_web)
    $current_time = (new DateTime('now'))->format('Y-m-d H:i:s');

    $stmt = $pdo_web->prepare("SELECT id, nombre, qr_expira FROM doctores WHERE qr_token = ?");
    $stmt->execute([$qr_token]);
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token inválido o no existe."]);
        exit;
    }

    // Verificar Expiración
    if ($doctor['qr_expira'] < $current_time) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Token expirado. Genera uno nuevo."]);
        exit;
    }

    $id_doctor = $doctor['id'];

    /*
    // 3. (OPCIONAL) Validación en la DB de la App (corita_db)
    // Aquí puedes validar si el paciente existe en la DB de la app usando $pdo_app
    if ($pdo_app) {
        // Ejemplo: Valida que el paciente exista en la tabla 'users' de la app
        $stmt_app = $pdo_app->prepare("SELECT COUNT(id) FROM users WHERE id = ?"); 
        $stmt_app->execute([$id_paciente_app]);
        if ($stmt_app->fetchColumn() == 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Usuario de la App no válido en la base de datos de la App."]);
            exit;
        }
    }
    */

    // 4. VINCULAR EL PACIENTE AL DOCTOR (Asignación en la tabla 'pacientes' de la DB WEB)
    $sql_vincular = "UPDATE pacientes SET id_doctor = ? WHERE id = ?";
    $stmt_vincular = $pdo_web->prepare($sql_vincular);

    if ($stmt_vincular->execute([$id_doctor, $id_paciente_web])) {

        // Opcional: Invalidar el token inmediatamente después de usarlo (seguridad)
        $stmt_invalidate = $pdo_web->prepare("UPDATE doctores SET qr_expira = NOW() WHERE id = ?");
        $stmt_invalidate->execute([$id_doctor]);

        echo json_encode([
            "status" => "success",
            "message" => "Vinculación con {$doctor['nombre']} (ID: {$id_doctor}) exitosa.",
            "medico_id" => $id_doctor
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "No se pudo actualizar la asignación del paciente (ID de Paciente WEB puede ser incorrecto)."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno del servidor: " . $e->getMessage()]);
}
