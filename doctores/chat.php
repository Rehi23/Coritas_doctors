<?php
session_start();
require '../includes/conexion.php';

// Validación de sesión (Igual que en tus otros archivos)
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'doctor' && $_SESSION['user_rol'] !== 'admin')) {
    header("Location: ../public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$rol_usuario = $_SESSION['user_rol'];

// Obtener pacientes con chats activos para la lista lateral
$sql_pacientes = "SELECT DISTINCT p.id, p.nombre, p.diagnostico_principal 
                  FROM pacientes p
                  JOIN mensajes m ON p.id = m.id_paciente
                  WHERE p.id_doctor = ?
                  ORDER BY m.fecha_envio DESC";
$stmt = $pdo->prepare($sql_pacientes);
$stmt->execute([$user_id]);
$lista_pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - Corita's Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #F2F6FC;
            margin: 0;
            overflow: hidden;
        }

        /* Estilos del Sidebar (Copiados de tu dashboard) */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: white;
            padding-top: 25px;
            border-right: 1px solid #c5d4e3;
            position: fixed;
        }

        .sidebar h4 {
            color: #1B76D1;
            font-weight: 700;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            font-size: 15px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
            gap: 10px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #1B76D1;
            color: white;
            border-radius: 10px;
        }

        .main-area {
            margin-left: 250px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* CHAT */
        .chat-container {
            display: flex;
            flex: 1;
            margin: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .chat-list {
            width: 300px;
            border-right: 1px solid #eee;
            overflow-y: auto;
        }

        .chat-item {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }

        .chat-item:hover {
            background: #e3f2fd;
        }

        .chat-item.active {
            background: #1B76D1;
            color: white;
        }

        .chat-item.active small {
            color: #e0e0e0;
        }

        .chat-box {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #fff;
        }

        .chat-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            color: #1B76D1;
        }

        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .msg {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 15px;
            font-size: 14px;
            position: relative;
        }

        .msg.paciente {
            align-self: flex-start;
            background: white;
            border: 1px solid #ddd;
            border-top-left-radius: 0;
        }

        .msg.doctor {
            align-self: flex-end;
            background: #1B76D1;
            color: white;
            border-top-right-radius: 0;
        }

        .input-area {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h4>Corita’s Doctor</h4>
        <a href="dashboard.php"><i class="bi bi-house-door-fill"></i> Inicio</a>
        <a href="perfil_doctor.php"><i class="bi bi-person-circle"></i> Perfil</a>
        <a href="codigo.php"><i class="bi bi-qr-code-scan"></i> Código</a>
        <a href="../pacientes/pacientes_view.php"><i class="bi bi-people-fill"></i> Pacientes</a>
        <a href="citas_view.php"><i class="bi bi-calendar-event"></i> Citas</a>
        <a href="chat.php" class="active"><i class="bi bi-chat-dots-fill"></i> Mensajes</a> <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Salir</a>
    </div>

    <div class="main-area">
        <div class="chat-container">
            <div class="chat-list">
                <div class="p-3 fw-bold border-bottom bg-light">Pacientes</div>
                <?php foreach ($lista_pacientes as $p): ?>
                    <div class="chat-item" onclick="loadChat(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>', this)">
                        <div class="fw-bold"><?= htmlspecialchars($p['nombre']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($p['diagnostico_principal']) ?></small>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($lista_pacientes)) echo '<div class="p-3 text-muted small text-center">No hay mensajes.</div>'; ?>
            </div>

            <div class="chat-box">
                <div class="chat-header" id="chatHeader">Seleccione un chat</div>
                <div class="messages" id="msgArea"></div>

                <div class="input-area" id="inputArea" style="display:none;">
                    <input type="text" id="msgInput" class="form-control" placeholder="Escriba una respuesta...">
                    <button class="btn btn-primary" onclick="sendMessage()"><i class="bi bi-send"></i></button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentChatId = null;

        function loadChat(id, nombre, element) {
            currentChatId = id;
            document.getElementById('chatHeader').innerText = nombre;
            document.getElementById('inputArea').style.display = 'flex';

            // Estilos de selección
            document.querySelectorAll('.chat-item').forEach(e => e.classList.remove('active'));
            element.classList.add('active');

            fetchMessages();
        }

        async function fetchMessages() {
            if (!currentChatId) return;
            // Usamos la misma API que la app (acción read)
            const res = await fetch(`../api/chat_movil.php?action=read&id_paciente=${currentChatId}`);
            const data = await res.json();

            const area = document.getElementById('msgArea');
            area.innerHTML = '';

            data.forEach(m => {
                const div = document.createElement('div');
                // Si is_from_patient es true, es 'paciente', si no 'doctor'
                div.className = `msg ${m.is_from_patient ? 'paciente' : 'doctor'}`;
                div.innerText = m.content;
                area.appendChild(div);
            });
            area.scrollTop = area.scrollHeight;
        }

        async function sendMessage() {
            const input = document.getElementById('msgInput');
            const text = input.value.trim();
            if (!text || !currentChatId) return;

            const formData = new FormData();
            formData.append('id_paciente', currentChatId);
            formData.append('mensaje', text);

            await fetch('enviar_mensaje_doctor.php', {
                method: 'POST',
                body: formData
            });
            input.value = '';
            fetchMessages();
        }

        // Auto-recarga
        setInterval(() => {
            if (currentChatId) fetchMessages();
        }, 3000);
    </script>
</body>

</html>