<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registro Médico - Corita's Doctor</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    body {
        font-family: "Poppins", sans-serif;
        background: linear-gradient(135deg, #3498DB, #5DADE2);
        min-height: 100vh;
        margin: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px 20px;
        box-sizing: border-box;
    }

    .register-card {
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(5px);
        padding: 45px;
        border-radius: 20px;
        width: 520px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.18);
        animation: fadeUp .6s ease;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .brand {
        font-size: 28px;
        font-weight: 700;
        color: #0b5db7;
        letter-spacing: -0.5px;
    }

    .form-control {
        border-radius: 10px;
        padding: 14px;
        font-size: 15px;
    }

    .mb-3 {
        margin-bottom: 1.2rem !important;
    }

    .btn-primary {
        background-color: #0b5db7;
        border: none;
        padding: 14px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 17px;
        transition: .28s;
    }

    .btn-primary:hover {
        background-color: #094e99;
        transform: translateY(-2px);
    }

    a {
        color: #0b5db7;
        font-weight: 600;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    /* Responsive optimizado para móviles */
    @media(max-width: 600px) {
        .register-card {
            width: 100%;
            padding: 30px 20px;
        }
    }
</style>
</head>

<body>

<div class="register-card">

    <h4 class="text-center mb-1">Registro Médico</h4>
    <div class="brand text-center mb-4">Corita’s Doctor</div>

   <form action="../doctores/registro_doctor.php" method="POST"> <div class="mb-3">
        <label class="form-label">Nombre completo</label>
        <input type="text" name="nombre" class="form-control" placeholder="Dr. Juan Pérez" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Correo</label>
        <input type="email" name="correo" class="form-control" placeholder="ejemplo@correo.com" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Contraseña</label>
        <input type="password" name="password" class="form-control" placeholder="********" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Confirmar contraseña</label>
        <input type="password" name="password2" class="form-control" placeholder="********" required>
    </div>

    <input type="hidden" name="especialidad" value="">
    <input type="hidden" name="telefono" value="">

    <div class="mb-3">
        <label class="form-label d-flex justify-content-between align-items-center">
            Cédula profesional
            <a href="../doctores/subir_cedula.php" class="btn btn-secondary btn-sm">Subir</a>
        </label>
    </div>

    <div class="form-check mb-2">
        <input class="form-check-input" type="checkbox" id="seg" required>
        <label class="form-check-label" for="seg">
            Acepto los términos de seguridad
        </label>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="term" required>
        <label class="form-check-label" for="term">
            Acepto los términos y condiciones
        </label>
    </div>

    <button type="submit" class="btn btn-primary w-100">Registrar médico</button>

</form>


    <div class="mt-3 text-center">
        <a href="index.php">Volver al inicio</a>
    </div>

</div>

</body>
</html>