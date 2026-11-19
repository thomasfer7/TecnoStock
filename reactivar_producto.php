
<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: productos.php");
    exit;
}

$idProducto = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$idProducto || $idProducto < 1) {
    header("Location: productos.php");
    exit;
}

$sql = "UPDATE productos
        SET activo = 1
        WHERE id_producto = :id AND activo = 0";

$consulta = $conexion->prepare($sql);
$consulta->execute(["id" => $idProducto]);

header("Location: productos.php");
exit;
