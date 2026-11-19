
<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/config/conexion.php";

function h($valor) {
    return htmlspecialchars((string)($valor ?? ""), ENT_QUOTES, "UTF-8");
}

$estado = $_GET["estado"] ?? "";
$errorCarga = false;
$productos = [];
$movimientos = [];

try {
    // Obtener productos activos para el formulario.
    $sqlProductos = "SELECT id_producto, codigo, nombre, stock
                     FROM productos
                     WHERE activo = 1
                     ORDER BY nombre ASC";

    $consultaProductos = $conexion->prepare($sqlProductos);
    $consultaProductos->execute();
    $productos = $consultaProductos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener los últimos 50 movimientos registrados.
    $sqlMovimientos = "SELECT
                            m.id_movimiento,
                            m.id_producto,
                            m.id_usuario,
                            m.tipo,
                            m.cantidad,
                            m.observacion,
                            p.codigo,
                            p.nombre
                       FROM movimientos m
                       INNER JOIN productos p
                           ON m.id_producto = p.id_producto
                       ORDER BY m.id_movimiento DESC
                       LIMIT 50";

    $consultaMovimientos = $conexion->prepare($sqlMovimientos);
    $consultaMovimientos->execute();
    $movimientos = $consultaMovimientos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar movimientos de TecnoStock: " . $e->getMessage());
    $errorCarga = true;
}

$mensajes = [
    "registrado" => ["Movimiento registrado correctamente.", "exito"],
    "datos_invalidos" => ["Revisa los datos. La cantidad debe ser mayor que cero.", "error"],
    "stock_insuficiente" => ["No hay stock suficiente para realizar esa salida.", "error"],
    "producto_no_disponible" => ["El producto no existe o está inactivo.", "error"],
    "error" => ["No se pudo registrar el movimiento. Inténtalo nuevamente.", "error"]
];

$mensaje = $mensajes[$estado] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimientos | TecnoStock</title>

    <style>
        :root {
            --fondo: #101210;
            --tarjeta: #171A17;
            --tarjeta-secundaria: #1D211C;
            --lima: #A3E635;
            --texto: #F5F7F2;
            --texto-secundario: #9BA497;
            --borde: #30372D;
            --rojo: #F87171;
            --verde: #4ADE80;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 30px;
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
        }

        .contenedor {
            width: 100%;
            max-width: 1250px;
            margin: 0 auto;
        }

        .encabezado {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .marca {
            color: var(--lima);
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        h1 {
            margin: 0;
            font-size: clamp(28px, 4vw, 36px);
        }

        .subtitulo {
            margin: 10px 0 0;
            color: var(--texto-secundario);
            line-height: 1.5;
        }

        .boton {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            min-height: 44px;
            padding: 12px 18px;
            border: 1px solid transparent;
            border-radius: 9px;
            background: var(--lima);
            color: #151910;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: .2s ease;
        }

        .boton:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .boton-secundario {
            background: var(--tarjeta);
            color: var(--texto);
            border-color: var(--borde);
        }

        .tarjeta {
            padding: 25px;
            margin-bottom: 25px;
            background: var(--tarjeta);
            border: 1px solid var(--borde);
            border-radius: 15px;
        }

        .titulo-seccion {
            margin: 0 0 8px;
            font-size: 21px;
        }

        .descripcion {
            margin: 0 0 24px;
            color: var(--texto-secundario);
            font-size: 14px;
            line-height: 1.5;
        }

        .formulario {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .campo {
            min-width: 0;
        }

        .campo-completo {
            grid-column: 1 / -1;
        }

        .campo label {
            display: block;
            margin-bottom: 9px;
            color: #DCE3D7;
            font-size: 14px;
            font-weight: bold;
        }

        .campo input,
        .campo select,
        .campo textarea {
            display: block;
            width: 100%;
            min-height: 48px;
            padding: 13px 14px;
            border: 1px solid var(--borde);
            border-radius: 9px;
            background: #101310 !important;
            color: #FFFFFF !important;
            -webkit-text-fill-color: #FFFFFF !important;
            font-family: inherit;
            font-size: 15px;
            outline: none;
            color-scheme: dark;
        }

        .campo textarea {
            min-height: 100px;
            resize: vertical;
        }

        .campo input:focus,
        .campo select:focus,
        .campo textarea:focus {
            border-color: var(--lima);
            box-shadow: 0 0 0 3px rgba(163, 230, 53, .12);
        }

        .campo input::placeholder,
        .campo textarea::placeholder {
            color: #849080 !important;
            -webkit-text-fill-color: #849080 !important;
        }

        .ayuda {
            display: block;
            margin-top: 8px;
            color: var(--texto-secundario);
            font-size: 12px;
            line-height: 1.5;
        }

        .acciones {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 5px;
        }

        .alerta {
            padding: 15px 18px;
            margin-bottom: 22px;
            border: 1px solid var(--borde);
            border-radius: 10px;
            background: var(--tarjeta);
            line-height: 1.5;
        }

        .alerta.exito {
            color: var(--verde);
            border-color: rgba(74, 222, 128, .35);
        }

        .alerta.error {
            color: var(--rojo);
            border-color: rgba(248, 113, 113, .35);
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            white-space: nowrap;
        }

        th {
            padding: 15px 14px;
            color: var(--texto-secundario);
            background: var(--tarjeta-secundaria);
            text-align: left;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        td {
            padding: 16px 14px;
            border-bottom: 1px solid var(--borde);
            font-size: 14px;
        }

        tbody tr:hover {
            background: rgba(163, 230, 53, .035);
        }

        .tipo {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tipo-entrada {
            background: rgba(74, 222, 128, .12);
            color: var(--verde);
        }

        .tipo-salida {
            background: rgba(248, 113, 113, .12);
            color: var(--rojo);
        }

        .cantidad-entrada {
            color: var(--verde);
            font-weight: bold;
        }

        .cantidad-salida {
            color: var(--rojo);
            font-weight: bold;
        }

        .vacio {
            padding: 35px 15px;
            color: var(--texto-secundario);
            text-align: center;
            line-height: 1.6;
        }

        .aviso {
            margin-top: 15px;
            padding: 14px;
            border-radius: 9px;
            background: rgba(163, 230, 53, .06);
            color: var(--texto-secundario);
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            body {
                padding: 18px 12px;
            }

            .tarjeta {
                padding: 18px 14px;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo-completo {
                grid-column: auto;
            }

            .encabezado .boton {
                width: 100%;
            }

            .acciones .boton {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">

    <header class="encabezado">
        <div>
            <div class="marca">TecnoStock / Inventario</div>
            <h1>Movimientos de stock</h1>
            <p class="subtitulo">
                Registra entradas y salidas y consulta el historial de productos.
            </p>
        </div>

        <a href="panel.php" class="boton boton-secundario">
            ← Volver al panel
        </a>
    </header>

    <?php if ($mensaje): ?>
        <div class="alerta <?= h($mensaje[1]) ?>">
            <?= h($mensaje[0]) ?>
        </div>
    <?php endif; ?>

    <?php if ($errorCarga): ?>
        <div class="alerta error">
            No se pudieron cargar los datos. Revisa la estructura de la tabla
            movimientos y los registros del error de PHP.
        </div>
    <?php else: ?>

        <section class="tarjeta">
            <h2 class="titulo-seccion">Registrar movimiento</h2>
            <p class="descripcion">
                Selecciona el producto, el tipo de operación y la cantidad.
                El stock se actualizará al registrar el movimiento.
            </p>

            <?php if (count($productos) > 0): ?>
                <form action="guardar_movimiento.php" method="POST" class="formulario">

                    <div class="campo campo-completo">
                        <label for="id_producto">Producto</label>
                        <select id="id_producto" name="id_producto" required>
                            <option value="">Selecciona un producto</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?= h($producto["id_producto"]) ?>">
                                    <?= h($producto["codigo"]) ?>
                                    — <?= h($producto["nombre"]) ?>
                                    (Stock: <?= h($producto["stock"]) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="tipo">Tipo de movimiento</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecciona una opción</option>
                            <option value="entrada">Entrada — aumenta el stock</option>
                            <option value="salida">Salida — disminuye el stock</option>
                        </select>
                    </div>

                    <div class="campo">
                        <label for="cantidad">Cantidad</label>
                        <input
                            type="number"
                            id="cantidad"
                            name="cantidad"
                            min="1"
                            step="1"
                            placeholder="Ej.: 5"
                            required
                        >
                    </div>

                    <div class="campo campo-completo">
                        <label for="observacion">Observación (opcional)</label>
                        <textarea
                            id="observacion"
                            name="observacion"
                            maxlength="255"
                            placeholder="Ej.: Compra de nuevos productos o entrega de materiales..."
                        ></textarea>
                        <span class="ayuda">
                            Máximo 255 caracteres.
                        </span>
                    </div>

                    <div class="campo campo-completo acciones">
                        <button type="submit" class="boton">
                            + Registrar movimiento
                        </button>
                        <button type="reset" class="boton boton-secundario">
                            Limpiar formulario
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="vacio">
                    No hay productos activos para registrar movimientos.
                    Primero registra o activa un producto.
                </div>
            <?php endif; ?>
        </section>

        <section class="tarjeta">
            <h2 class="titulo-seccion">Historial de movimientos</h2>
            <p class="descripcion">
                Últimos 50 movimientos registrados en el sistema.
            </p>

            <?php if (count($movimientos) > 0): ?>
                <div class="tabla-contenedor">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Usuario ID</th>
                                <th>Observación</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($movimientos as $movimiento): ?>
                            <?php
                            $esEntrada = $movimiento["tipo"] === "entrada";
                            ?>
                            <tr>
                                <td>#<?= h($movimiento["id_movimiento"]) ?></td>

                                <td>
                                    <strong><?= h($movimiento["nombre"]) ?></strong><br>
                                    <span style="color: var(--texto-secundario); font-size: 12px;">
                                        <?= h($movimiento["codigo"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="tipo <?= $esEntrada ? 'tipo-entrada' : 'tipo-salida' ?>">
                                        <?= $esEntrada ? 'Entrada' : 'Salida' ?>
                                    </span>
                                </td>

                                <td class="<?= $esEntrada ? 'cantidad-entrada' : 'cantidad-salida' ?>">
                                    <?= $esEntrada ? '+' : '-' ?><?= h($movimiento["cantidad"]) ?>
                                </td>

                                <td><?= h($movimiento["id_usuario"]) ?></td>

                                <td>
                                    <?= h($movimiento["observacion"] ?: "Sin observación") ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="vacio">
                    Todavía no hay movimientos registrados.<br>
                    Cuando registres una entrada o salida, aparecerá aquí.
                </div>
            <?php endif; ?>
        </section>

    <?php endif; ?>

    <div class="aviso">
        <strong style="color: var(--lima);">Importante:</strong>
        las salidas no deben superar el stock disponible. Registra cada operación
        correctamente para mantener actualizado el inventario.
    </div>

</div>
</body>
</html>
