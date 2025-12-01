<?php
session_start();
require_once(__DIR__ . '/../includes/conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = trim($_POST['correo'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($correo) || empty($password)) {
        $error = "Por favor, ingresa correo y contraseña.";
        header("Location: ../public/index.php?error=" . urlencode($error));
        exit;
    }

    /* ───────────────────────────────
       1) Verificar USUARIO GENERAL (Admin o Doctor)
    ─────────────────────────────── */
    $sqlUser = $pdo->prepare("SELECT id, nombre, correo, password, rol FROM doctores WHERE correo = ?");
    $sqlUser->execute([$correo]);
    $user = $sqlUser->fetch(PDO::FETCH_ASSOC);

    $esValido = false;

    if ($user) {
        $hashGuardado = $user['password'];

        // 1.1) Intentar con password_hash (BCRYPT)
        if (password_verify($password, $hashGuardado)) {
            $esValido = true;
        } 

        if ($esValido) {
            // UNIFICACIÓN DE SESIÓN
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];

            if ($user['rol'] === 'admin') {
                // Redirección Admin: Sale de 'auth/', va a la raíz
                header("Location: ../admin/panel_admin.php");
                exit;
            } else { // rol === 'doctor'
                // Redirección Doctor: Sale de 'auth/', va a 'doctores/'
                header("Location: ../doctores/perfil_doctor.php");
                exit;
            }
        }
    }
    
    // Si la autenticación falla (ADMIN o DOCTOR)
    $error = "Correo o contraseña incorrectos.";
    // 🚩 CORRECCIÓN CRÍTICA: La ruta debe apuntar a la carpeta 'public/'
    header("Location: ../public/index.php?error=" . urlencode($error)); 
    exit;
    } else {
    // Si se accede directamente por GET
    header("Location: ../public/index.php");
    exit;
}
?>