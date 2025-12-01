<?php
// Credenciales de conexión (asumiendo que son las mismas para ambas DBs en localhost)
$host = "localhost";
$user = "root";
$pass = "";
$port = 3306;

// ----------------------------------------------------
// 1. CONEXIÓN PARA LA BASE DE DATOS DEL SITIO WEB (corita_db_web)
// ----------------------------------------------------
$dbname_web = "corita_db_web"; 

try {
    $dsn_web = "mysql:host=$host;port=$port;dbname=$dbname_web;charset=utf8mb4";
    $pdo_web = new PDO($dsn_web, $user, $pass);
    $pdo_web->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Renombramos la variable principal a $pdo_web
    $pdo = $pdo_web; 

} catch (PDOException $e) {
    die("Error de conexión con la DB del Sitio Web: " . $e->getMessage());
}

// ----------------------------------------------------
// 2. CONEXIÓN PARA LA BASE DE DATOS DE LA APP (corita_db)
// ----------------------------------------------------
$dbname_app = "corita_db"; 
$pdo_app = null; // Inicializamos a null para manejo de errores

try {
    $dsn_app = "mysql:host=$host;port=$port;dbname=$dbname_app;charset=utf8mb4";
    $pdo_app = new PDO($dsn_app, $user, $pass);
    $pdo_app->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Es menos crítico si falla la conexión de la app, podemos registrar el error o simplemente no usar $pdo_app
    error_log("Error de conexión con la DB de la App: " . $e->getMessage());
}

// Liberar variables de credenciales de la memoria
unset($host, $user, $pass, $port, $dsn_web, $dsn_app);
?>