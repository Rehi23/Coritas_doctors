<?php
session_start();

// 1. OBTENER MENSAJES DE ÉXITO O ERROR
$mensaje_exito = $_SESSION['msg'] ?? '';
$mensaje_error = $_GET['error'] ?? '';

// 2. LIMPIAR SESIÓN después de mostrar el mensaje de éxito
if (isset($_SESSION['msg'])) {
    unset($_SESSION['msg']);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corita's Doctor - Iniciar sesión</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            min-height: 100vh;
            background: #3498DB;
            overflow: hidden;
        }

        .left-section {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding: 30px;
            background: #3498DB;
            position: relative;
            z-index: 2;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(4px);
            padding: 35px;
            border-radius: 18px;
            width: 360px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.25);
        }

        .brand {
            font-size: 26px;
            font-weight: 700;
            color: #0b5db7;
        }

        .btn-primary {
            background-color: #0b5db7;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            padding: 12px;
            transition: .3s;
        }

        .btn-primary:hover {
            background-color: #094e99;
            transform: translateY(-2px);
        }

        .right-section {
            width: 50%;
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            background: url("assets/imagenes/doc2.jpg") center/cover no-repeat;
        }

        .right-section::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to left, rgba(52, 152, 219, 0.85)0%, rgba(52, 152, 219, 0.6)40%, rgba(52, 152, 219, 0.1)100%);
        }

        .right-section::after {
            content: "";
            position: absolute;
            left: -80px;
            width: 80px;
            height: 100%;
            background: linear-gradient(to left, rgba(52, 152, 219, 0.7), rgba(52, 152, 219, 0));
            filter: blur(18px);
        }

        @media (max-width: 991px) {
            .right-section {
                display: none;
            }

            body {
                background: #3498DB;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid">
        <div class="row g-0">
            <div class="col-lg-6 col-md-12 left-section">
                <div class="login-card">
                    <?php if ($mensaje_exito): ?>
                        <div class="alert alert-success text-center mt-3" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje_exito) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mensaje_error): ?>
                        <div class="alert alert-danger text-center mt-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($mensaje_error) ?>
                        </div>
                    <?php endif; ?>
                    <h5 class="text-center mb-2">Inicia sesión</h5>
                    <p class="text-center text-secondary mb-1">Bienvenido a</p>
                    <div class="brand text-center mb-4">Corita’s Doctor</div>

                    <form action="../auth/login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Correo</label>
                            <input type="email" class="form-control" name="correo" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Iniciar sesión</button>
                    </form>

                    <div class="text-center mt-3">
                        ¿No tienes cuenta? <a href="./registro.php">Registrarse</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 right-section"></div>
        </div>
    </div>

</body>

</html>