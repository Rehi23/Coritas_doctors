<?php
session_start();
// RUTA DE CONEXIÓN: desde la raíz
require '../includes/conexion.php';

// VALIDACIÓN Y AUTORIZACIÓN (Correcta)
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../public/index.php"); // Redirigir a public/index.php
    exit();
}

// 1. CAPTURAR Y PREPARAR LA BÚSQUEDA
$search_query = trim($_GET['q'] ?? '');
$params = [];

try {
    // 2. CONSTRUIR LA CONSULTA BASE Y EL FILTRO DE BÚSQUEDA (Lógica correcta)
    $sql = "SELECT id, nombre, especialidad, telefono, correo, rol FROM doctores";
    $where_clauses = [];

    if (!empty($search_query)) {
        $where_clauses[] = "(nombre LIKE ? OR correo LIKE ? OR especialidad LIKE ?)";
        $search_term = "%" . $search_query . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " ORDER BY nombre ASC";

    // 3. EJECUTAR LA CONSULTA
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_db = "Error al cargar la lista de doctores: " . $e->getMessage();
    $doctores = [];
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Doctores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background-color: #F2F6FC;
            margin: 0;
        }

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

        .topbar {
            margin-left: 250px;
            height: 60px;
            background-color: #1B76D1;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            font-weight: 600;
            justify-content: space-between;
        }

        .search-wrapper {
            margin-left: 250px;
            padding: 15px 20px;
            background: #F2F6FC;
        }

        .content {
            margin-left: 250px;
            padding: 10px 20px;
        }

        .doctor-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: .2s;
        }

        .doctor-tag {
            background: #D8EDFF;
            color: #004d82;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }

        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-250px);
            }

            .topbar,
            .content,
            .search-wrapper {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <span>Administración de Doctores</span>
        <button class="btn btn-light d-lg-none" onclick="toggleMenu()"><i class="bi bi-list"></i></button>
    </div>
    <div class="sidebar" id="sidebar">
        <h4>Corita’s Doctor</h4>
        <h1 class="visually-hidden">Bienvenido Administrador</h1> <a class="active" href="../admin/panel_admin.php"><i class="bi bi-person-badge-fill"></i> Administrar Doctores</a>
        <a href="../admin/crear_doctor.php"><i class="bi bi-person-plus-fill"></i> Agregar Doctor</a>
        <a href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
    </div>
    <div class="search-wrapper">
        <div class="d-flex justify-content-between align-items-center">
            <form method="GET" action="panel_admin.php" class="w-50 d-flex gap-2">
                <input type="text" name="q" id="searchInput" class="form-control" placeholder="Buscar doctor..." value="<?= htmlspecialchars($search_query) ?>">
                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                <?php if (!empty($search_query)): ?>
                    <a href="../admin/panel_admin.php" class="btn btn-secondary" title="Limpiar búsqueda"><i class="bi bi-x"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>
    <div class="content">
        <?php if (isset($error_db)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_db) ?></div>
        <?php endif; ?>
        <?php if (empty($doctores)): ?>
            <div class="alert alert-info">No se encontraron doctores.</div>
        <?php else: ?>
            <?php foreach ($doctores as $doc): ?>
                <div class="doctor-card">
                    <div>
                        <h6 class="m-0"><?= htmlspecialchars($doc['nombre']) ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($doc['especialidad']) ?> / <?= htmlspecialchars($doc['correo']) ?></small>
                        <div class="mt-1"><span class="doctor-tag"><?= htmlspecialchars(ucfirst($doc['rol'])) ?></span></div>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="../doctores/editar_doctor.php?id=<?= $doc['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <a href="../admin/eliminar_doctor.php?id=<?= $doc['id'] ?>"
                            onclick="return confirm('¿Eliminar doctor?');"
                            class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleMenu() {
            document.getElementById("sidebar").classList.toggle("show");
        }
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('searchInput').value) {
                document.getElementById('searchInput').focus();
            }
        });
    </script>
</body>
</html>