<?php

error_reporting(E_ALL); // Habilitar todos los errores temporalmente para debug
ini_set('display_errors', 1);

require '../includes/conexion.php';
header('Content-Type: application/json');

// Solo aceptar métodos PUT (simulado vía POST/JSON si es necesario)
if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido."]);
    exit;
}

// 1. LECTURA Y AUTENTICACIÓN
$input_json = file_get_contents("php://input");
$data = json_decode($input_json, true);

$id_paciente_auth = $data['id'] ?? null;
$token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

if (empty($id_paciente_auth)) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Error de autenticación: ID de paciente faltante."]);
    exit;
}

// 2. CONSTRUIR LA CONSULTA DE ACTUALIZACIÓN DINÁMICA
$set_clauses = [];
$params = [];

// Mapeo de claves de Flutter a columnas de SQL
// CRÍTICO: Usamos el mismo mapeo que el provider para filtrar
$api_to_db_map = [
    'nuevoGenero' => 'genero',
    'nuevaFecha' => 'fecha_nacimiento',
    'nuevoNss' => 'nss',
    'nombre' => 'nombre',
    'correo' => 'correo',
    'telefono' => 'telefono',
];

foreach ($api_to_db_map as $api_key => $db_column) {
    if (array_key_exists($api_key, $data)) {
        $value = $data[$api_key];

        // 🚩 Filtro CRÍTICO: Si el valor es una cadena vacía o nulo, lo establecemos a NULL en SQL.
        if (empty($value) && $value !== 0) { // Permitir 0 si fuera un valor numérico
            $set_clauses[] = "$db_column = NULL";
        } else {
            $set_clauses[] = "$db_column = ?";
            $params[] = $value;
        }
    }
}

if (empty($set_clauses)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No se proporcionaron datos válidos para actualizar."]);
    exit;
}

$params[] = $id_paciente_auth; // Añadir ID al final

try {
    $sql = "UPDATE pacientes SET " . implode(', ', $set_clauses) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute($params)) {

        // 3. OBTENER LOS DATOS ACTUALIZADOS Y DEVOLVER AL CLIENTE
        $stmt_fetch = $pdo->prepare("SELECT genero, fecha_nacimiento, nss FROM pacientes WHERE id = ?");
        $stmt_fetch->execute([$id_paciente_auth]);
        $updated_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        // Limpiar output antes de enviar JSON
        if (ob_get_contents()) ob_clean();

        echo json_encode([
            "success" => true,
            "message" => "Perfil actualizado correctamente.",
            "data" => $updated_data
        ]);
    } else {
        http_response_code(500);
        // Si hay error de SQL, el try/catch lo manejará
        echo json_encode(["success" => false, "message" => "Error al ejecutar la actualización en la DB."]);
    }

} catch (PDOException $e) {
    // Si la DB lanza un error (clave foránea, sintaxis, etc.)
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Error DB: " . $e->getMessage()]);
}
// Desactivar errores después de la ejecución
error_reporting(0);
