
<?php

$host = "localhost";
$dbname = "tecnostock_db";
$usuario = "root";
$contrasena = "";

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $contrasena
    );

    $conexion->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {
    die("Error al conectar con la base de datos.");
}
