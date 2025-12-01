<?php
session_start();
require '../includes/conexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'admin') {
    header("Location: ../public/index.php"); // Redirigir a public/index.php
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../admin/panel_admin.php");
    exit();
}

$id = $_GET['id'];

$sql = $pdo->prepare("DELETE FROM doctores WHERE id = ?");
$sql->execute([$id]);

header("Location: ../admin/panel_admin.php");
exit();
?>
