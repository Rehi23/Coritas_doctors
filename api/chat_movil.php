<?php
// api/chat_movil.php
require '../includes/conexion.php';

// Permitir acceso desde la App (CORS)
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type");

// Leer el JSON que envía Flutter
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Determinar acción (GET o POST) y datos
$action = $_GET['action'] ?? '';
// Si es POST, los datos vienen en el body JSON, si es GET en la URL
$id_paciente = isset($_GET['id_paciente']) ? intval($_GET['id_paciente']) : ($input['user_id'] ?? 0);

if ($id_paciente <= 0) {
    echo json_encode(["status" => "error", "message" => "ID Paciente requerido"]);
    exit;
}

try {
    // 1. LEER MENSAJES (GET)
    if ($action === 'read') {
        $sql = "SELECT remitente, mensaje, fecha_envio 
                FROM mensajes 
                WHERE id_paciente = ? 
                ORDER BY fecha_envio ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_paciente]);
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapear para que coincida con lo que espera Flutter (is_from_patient)
        $response = [];
        foreach ($mensajes as $m) {
            $response[] = [
                "content" => $m['mensaje'],
                "is_from_patient" => ($m['remitente'] === 'paciente'),
                "timestamp" => $m['fecha_envio']
            ];
        }
        
        echo json_encode($response); // Devolver lista directa
    
    // 2. ENVIAR MENSAJE (POST)
    } elseif ($action === 'send') {
        $mensaje = trim($input['content'] ?? '');
        
        if (empty($mensaje)) {
            echo json_encode(["status" => "error", "message" => "Mensaje vacío"]);
            exit;
        }

        // Buscar el doctor asignado al paciente
        $stmtDoc = $pdo->prepare("SELECT id_doctor FROM pacientes WHERE id = ?");
        $stmtDoc->execute([$id_paciente]);
        $id_doctor = $stmtDoc->fetchColumn();

        if (!$id_doctor) {
            echo json_encode(["status" => "error", "message" => "Paciente sin doctor asignado"]);
            exit;
        }

        $sql = "INSERT INTO mensajes (id_doctor, id_paciente, remitente, mensaje) VALUES (?, ?, 'paciente', ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$id_doctor, $id_paciente, $mensaje])) {
            echo json_encode(["status" => "success", "message" => "Enviado"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error al guardar"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Acción inválida"]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Error DB: " . $e->getMessage()]);
}
?>