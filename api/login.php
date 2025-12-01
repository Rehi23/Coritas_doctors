<?php
// Configuración de cabeceras para permitir CORS y asegurar la respuesta JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// --- 1. CONFIGURACIÓN DE BASE DE DATOS ---
$host = "localhost";
$db_name = "corita_db_web"; 
$username = "root";   
$password = ""; 

$table_name = "pacientes";

// --- 2. CONEXIÓN A LA BASE DE DATOS ---
$conn = new mysqli($host, $username, $password, $db_name);

// Verificar la conexión
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(array("success" => false, "message" => "Error de conexión a la base de datos: " . $conn->connect_error));
    exit;
}

// --- 3. OBTENER DATOS DEL USUARIO ---
// Lee los datos JSON enviados desde Flutter
$data = json_decode(file_get_contents("php://input"));

// Verificar si se recibieron los datos requeridos
if (empty($data->correo) || empty($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(array("success" => false, "message" => "Faltan correo o contraseña."));
    exit;
}

$correo = $conn->real_escape_string($data->correo);
$password = $data->password;

// --- 4. BUSCAR USUARIO EN LA BASE DE DATOS ---
$query = "SELECT id, correo, nombre, contraseña FROM " . $table_name . " WHERE correo = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

// --- 5. VERIFICAR CREDENCIALES ---
if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $hashed_password = $row['contraseña'];

    if (password_verify($password, $hashed_password)) {
        // --- ¡LOGIN EXITOSO! ---
        http_response_code(200);

        // Generación de TOKEN (ejemplo)
        $dummy_token = base64_encode(random_bytes(32)); 

        echo json_encode(array(
            "success" => true,
            "message" => "Inicio de sesión exitoso.",
            "access_token" => $dummy_token, // ¡CRÍTICO! Flutter lo espera.
            "token_type" => "bearer",
            "user_id" => $row['id'],
            "nombre" => $row['nombre']
        ));
        // 💥 Detiene la ejecución aquí para asegurar un JSON limpio.
        exit; 

    } else {
        // Contraseña incorrecta
        http_response_code(401); 
        echo json_encode(array("success" => false, "message" => "Credenciales inválidas."));
    }
} else {
    // Correo no encontrado
    http_response_code(401); 
    echo json_encode(array("success" => false, "message" => "Credenciales inválidas."));
}

$stmt->close();
$conn->close();
// 💡 Se omite el ? > final para prevenir errores de caracteres invisibles.