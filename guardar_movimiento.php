
<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once "config/conexion.php";

// Solo aceptar solicitudes del formulario.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: movimientos.php");
    exit;
}

// Recibir los datos enviados.
$idProducto = filter_input(INPUT_POST, "id_producto", FILTER_VALIDATE_INT);
$tipo = $_POST["tipo"] ?? "";
$cantidad = filter_input(INPUT_POST, "cantidad", FILTER_VALIDATE_INT);
$observacion = trim($_POST["observacion"] ?? "");

// Validar los datos.
if (
    !$idProducto ||
    !in_array($tipo, ["entrada", "salida"], true) ||
    $cantidad === false ||
    $cantidad === null ||
    $cantidad <= 0 ||
    strlen($observacion) > 255
) {
    header("Location: movimientos.php?estado=datos_invalidos");
    exit;
}

try {
    // Iniciar una transacción para mantener los datos consistentes.
    $conexion->beginTransaction();

    // Consultar y bloquear el producto mientras se procesa el movimiento.
    $sqlProducto = "SELECT stock
                    FROM productos
                    WHERE id_producto = :id_producto
                      AND activo = 1
                    FOR UPDATE";

    $consultaProducto = $conexion->prepare($sqlProducto);
    $consultaProducto->execute([
        "id_producto" => $idProducto
    ]);

    $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new RuntimeException("producto_no_disponible");
    }

    $stockActual = (int) $producto["stock"];

    // Calcular el nuevo stock.
    if ($tipo === "entrada") {
        $nuevoStock = $stockActual + $cantidad;
    } else {
        if ($cantidad > $stockActual) {
            throw new RuntimeException("stock_insuficiente");
        }

        $nuevoStock = $stockActual - $cantidad;
    }

    // Actualizar el stock del producto.
    $sqlActualizar = "UPDATE productos
                      SET stock = :stock
                      WHERE id_producto = :id_producto
                        AND activo = 1";

    $consultaActualizar = $conexion->prepare($sqlActualizar);
    $consultaActualizar->execute([
        "stock" => $nuevoStock,
        "id_producto" => $idProducto
    ]);

    // Guardar el movimiento y el usuario que lo realizó.
    $sqlMovimiento = "INSERT INTO movimientos
                      (id_producto, id_usuario, tipo, cantidad, observacion)
                      VALUES
                      (:id_producto, :id_usuario, :tipo, :cantidad, :observacion)";

    $consultaMovimiento = $conexion->prepare($sqlMovimiento);
    $consultaMovimiento->execute([
        "id_producto" => $idProducto,
        "id_usuario" => $_SESSION["id_usuario"],
        "tipo" => $tipo,
        "cantidad" => $cantidad,
        "observacion" => $observacion !== "" ? $observacion : null
    ]);

    // Confirmar ambas operaciones.
    $conexion->commit();

    header("Location: movimientos.php?estado=registrado");
    exit;

} catch (Throwable $e) {
    // Si algo falla, deshacer los cambios.
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    if ($e instanceof RuntimeException) {
        if ($e->getMessage() === "stock_insuficiente") {
            header("Location: movimientos.php?estado=stock_insuficiente");
            exit;
        }

        if ($e->getMessage() === "producto_no_disponible") {
            header("Location: movimientos.php?estado=producto_no_disponible");
            exit;
        }
    }

    // No mostrar detalles internos de la base de datos.
    error_log("Error al guardar movimiento en TecnoStock: " . $e->getMessage());

    header("Location: movimientos.php?estado=error");
    exit;
}
