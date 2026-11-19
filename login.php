
<?php
session_start();
require_once __DIR__ . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$usuario = trim($_POST["usuario"] ?? "");
$contrasena = $_POST["contrasena"] ?? "";

$sql = "SELECT id_usuario, nombre, usuario, password_hash
        FROM usuarios
        WHERE usuario = :usuario AND activo = 1
        LIMIT 1";

$consulta = $conexion->prepare($sql);
$consulta->execute(["usuario" => $usuario]);

$datos = $consulta->fetch(PDO::FETCH_ASSOC);

if ($datos && password_verify($contrasena, $datos["password_hash"])) {
    session_regenerate_id(true);

    $_SESSION["id_usuario"] = $datos["id_usuario"];
    $_SESSION["nombre"] = $datos["nombre"];
    $_SESSION["usuario"] = $datos["usuario"];

    header("Location: panel.php");
    exit;
}

header("Location: index.php?error=1");
exit;
