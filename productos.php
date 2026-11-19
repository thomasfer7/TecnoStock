
<?php
session_start();

if (!isset($_SESSION["id_usuario"])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . "/config/conexion.php";

function h($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

$mensaje = "";
$tipoMensaje = "";
$busqueda = trim($_GET["buscar"] ?? "");

// Registrar producto
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["accion"] ?? "") === "registrar"
) {
    $codigo = trim($_POST["codigo"] ?? "");
    $nombre = trim($_POST["nombre"] ?? "");
    $descripcion = trim($_POST["descripcion"] ?? "");
    $idCategoria = (int) ($_POST["id_categoria"] ?? 0);
    $stock = filter_var($_POST["stock"] ?? null, FILTER_VALIDATE_INT);
    $stockMinimo = filter_var($_POST["stock_minimo"] ?? null, FILTER_VALIDATE_INT);

    if (
        $codigo === "" || $nombre === "" || $idCategoria <= 0 ||
        $stock === false || $stockMinimo === false ||
        $stock < 0 || $stockMinimo < 0
    ) {
        $mensaje = "Completa los campos correctamente. El stock no puede ser negativo.";
        $tipoMensaje = "error";
    } else {
        try {
            $sql = "INSERT INTO productos
                    (codigo, nombre, descripcion, id_categoria, stock, stock_minimo, activo)
                    VALUES
                    (:codigo, :nombre, :descripcion, :id_categoria, :stock, :stock_minimo, 1)";

            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ":codigo" => $codigo,
                ":nombre" => $nombre,
                ":descripcion" => $descripcion,
                ":id_categoria" => $idCategoria,
                ":stock" => $stock,
                ":stock_minimo" => $stockMinimo
            ]);

            header("Location: productos.php?mensaje=registrado");
            exit;
        } catch (PDOException $e) {
            $mensaje = $e->getCode() === "23000"
                ? "El código ya existe o la categoría no es válida."
                : "No se pudo registrar el producto. Revisa los datos e inténtalo nuevamente.";
            $tipoMensaje = "error";
        }
    }
}

if (($_GET["mensaje"] ?? "") === "registrado") {
    $mensaje = "¡Producto registrado correctamente!";
    $tipoMensaje = "exito";
}

// Categorías
$stmtCategorias = $conexion->query(
    "SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC"
);
$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

// Productos activos y búsqueda
$sql = "SELECT
            p.id_producto,
            p.codigo,
            p.nombre,
            p.descripcion,
            p.stock,
            p.stock_minimo,
            c.nombre AS categoria
        FROM productos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.activo = 1";

$params = [];

if ($busqueda !== "") {
    $sql .= " AND (p.codigo LIKE :codigo OR p.nombre LIKE :nombre)";
    $params[":codigo"] = "%" . $busqueda . "%";
    $params[":nombre"] = "%" . $busqueda . "%";
}

$sql .= " ORDER BY p.nombre ASC";

$stmtProductos = $conexion->prepare($sql);
$stmtProductos->execute($params);
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Productos desactivados
$stmtInactivos = $conexion->query(
    "SELECT id_producto, codigo, nombre
     FROM productos
     WHERE activo = 0
     ORDER BY nombre ASC"
);
$productosInactivos = $stmtInactivos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | TecnoStock</title>
    <link rel="stylesheet" href="assets/css/estilos.css">

    <style>
        :root {
            --lima: #A3E635;
            --lima-hover: #BEF264;
            --fondo: #101210;
            --superficie: #171A17;
            --superficie-hover: #20251E;
            --texto: #FFFFFF;
            --secundario: #A5AEA2;
            --borde: #363D34;
            --rojo: #FCA5A5;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            background: var(--fondo) !important;
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
        }

        .contenedor {
            width: 100%;
            max-width: none;
            margin: 0 auto;
            padding: 30px 24px;
        }

        .productos-pagina {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .productos-cabecera {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 28px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--borde);
        }

        .logo {
            color: var(--lima) !important;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        h1, h2, h3 { color: var(--texto) !important; }

        .productos-cabecera h1 {
            margin: 8px 0;
            font-size: 30px;
        }

        .productos-cabecera p,
        .descripcion-seccion {
            color: var(--secundario) !important;
        }

        .acciones-cabecera {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .boton {
            display: inline-block;
            padding: 11px 16px;
            border: 1px solid var(--lima);
            border-radius: 8px;
            background: var(--lima);
            color: #151810 !important;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
            transition: background .2s ease;
        }

        .boton:hover { background: var(--lima-hover); }

        .boton-secundario {
            background: transparent;
            color: var(--lima) !important;
        }

        .boton-secundario:hover { background: var(--superficie-hover); }

        .boton-peligro {
            background: transparent;
            border-color: #7F3434;
            color: var(--rojo) !important;
        }

        .boton-peligro:hover { background: #321E1E; }

        .boton-pequeno {
            padding: 8px 11px;
            font-size: 12px;
        }

        .tarjeta-productos {
            margin-bottom: 24px;
            padding: 24px;
            background: var(--superficie) !important;
            border: 1px solid var(--borde);
            border-radius: 14px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .15);
        }

        .tarjeta-productos h2 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .formulario-producto {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .campo-completo { grid-column: 1 / -1; }

        .campo label {
            color: var(--texto);
            font-size: 14px;
            font-weight: 700;
        }

        .campo input,
        .campo select,
        .campo textarea,
        .buscador input {
            display: block;
            width: 100%;
            padding: 13px 15px;
            background: #101310 !important;
            color: #FFFFFF !important;
            -webkit-text-fill-color: #FFFFFF !important;
            border: 1px solid var(--borde);
            border-radius: 9px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15px;
            outline: none;
            caret-color: var(--lima);
            color-scheme: dark;
            opacity: 1;
        }

        .campo input:focus,
        .campo select:focus,
        .campo textarea:focus,
        .buscador input:focus {
            background: #101310 !important;
            color: #FFFFFF !important;
            -webkit-text-fill-color: #FFFFFF !important;
            border-color: var(--lima);
            box-shadow: 0 0 0 3px rgba(163, 230, 53, .15);
        }

        .campo input::placeholder,
        .campo textarea::placeholder,
        .buscador input::placeholder {
            color: #849080 !important;
            -webkit-text-fill-color: #849080 !important;
            opacity: 1;
        }

        .campo input:-webkit-autofill,
        .campo input:-webkit-autofill:hover,
        .campo input:-webkit-autofill:focus {
            -webkit-text-fill-color: #FFFFFF !important;
            caret-color: var(--lima);
            box-shadow: 0 0 0 1000px #101310 inset !important;
        }

        .campo textarea {
            min-height: 90px;
            resize: vertical;
        }

        .formulario-acciones {
            grid-column: 1 / -1;
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }

        .mensaje {
            margin-bottom: 20px;
            padding: 13px 16px;
            border-radius: 9px;
            font-weight: 700;
        }

        .mensaje.exito {
            background: #1D3020;
            border: 1px solid #426D32;
            color: var(--lima);
        }

        .mensaje.error {
            background: #321E1E;
            border: 1px solid #7F3434;
            color: var(--rojo);
        }

        .buscador {
            display: flex;
            align-items: stretch;
            gap: 12px;
            width: 100%;
            margin: 22px 0;
        }

        .buscador input {
            flex: 1;
            min-width: 0;
            min-height: 56px;
            padding: 16px 19px;
            font-size: 17px;
        }

        .buscador .boton {
            min-width: 120px;
            min-height: 56px;
            padding: 14px 24px;
            font-size: 15px;
        }

        .tabla-contenedor {
            width: 100%;
            overflow-x: auto;
            margin-top: 16px;
        }

        .tabla-productos {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        .tabla-productos th,
        .tabla-productos td {
            padding: 13px 12px;
            border-bottom: 1px solid var(--borde);
            text-align: left;
            vertical-align: middle;
        }

        .tabla-productos th {
            background: var(--superficie-hover);
            color: var(--lima) !important;
            font-size: 12px;
            text-transform: uppercase;
        }

        .tabla-productos td { color: var(--texto) !important; }

        .tabla-productos tbody tr:hover {
            background: var(--superficie-hover);
        }

        .acciones-tabla {
            display: flex;
            gap: 7px;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-inline {
            display: inline;
            margin: 0;
        }

        .estado-stock {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .estado-normal {
            background: #1D3020;
            color: var(--lima);
        }

        .estado-bajo {
            background: #3B241D;
            color: #FDBA74;
        }

        .estado-agotado {
            background: #321E1E;
            color: var(--rojo);
        }

        .tabla-vacia {
            padding: 25px !important;
            text-align: center !important;
            color: var(--secundario) !important;
        }

        .producto-inactivo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid var(--borde);
        }

        .producto-inactivo:last-child { border-bottom: none; }

        .producto-inactivo p {
            margin: 4px 0 0;
            color: var(--secundario);
            font-size: 13px;
        }

        @media (max-width: 750px) {
            .contenedor { padding: 20px 12px; }

            .productos-cabecera {
                align-items: flex-start;
                flex-direction: column;
            }

            .formulario-producto { grid-template-columns: 1fr; }

            .campo-completo,
            .formulario-acciones { grid-column: auto; }

            .tarjeta-productos { padding: 17px; }

            .buscador { flex-direction: column; }

            .buscador input,
            .buscador .boton {
                width: 100%;
                min-height: 52px;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">
<main class="productos-pagina">

    <header class="productos-cabecera">
        <div>
            <div class="logo">TecnoStock</div>
            <h1>Gestión de productos</h1>
            <p>Registra, consulta y administra tu inventario.</p>
        </div>

        <nav class="acciones-cabecera">
            <a class="boton boton-secundario" href="panel.php">Volver al panel</a>
            <a class="boton boton-secundario" href="movimientos.php">Movimientos</a>
            <a class="boton boton-peligro" href="logout.php">Cerrar sesión</a>
        </nav>
    </header>

    <?php if ($mensaje !== ""): ?>
        <div class="mensaje <?= h($tipoMensaje) ?>">
            <?= h($mensaje) ?>
        </div>
    <?php endif; ?>

    <section class="tarjeta-productos">
        <h2>Registrar producto</h2>
        <p class="descripcion-seccion">
            Completa los datos para agregar un producto al inventario.
        </p>

        <form class="formulario-producto" method="POST" action="productos.php">
            <input type="hidden" name="accion" value="registrar">

            <div class="campo">
                <label for="codigo">Código del producto *</label>
                <input type="text" id="codigo" name="codigo"
                       placeholder="Ej.: TEC-001" maxlength="100" required>
            </div>

            <div class="campo">
                <label for="nombre">Nombre del producto *</label>
                <input type="text" id="nombre" name="nombre"
                       placeholder="Ej.: Teclado mecánico" maxlength="150" required>
            </div>

            <div class="campo campo-completo">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion"
                          placeholder="Detalles del producto..."></textarea>
            </div>

            <div class="campo">
                <label for="id_categoria">Categoría *</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?= h($categoria["id_categoria"]) ?>">
                            <?= h($categoria["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="stock">Stock inicial *</label>
                <input type="number" id="stock" name="stock"
                       min="0" step="1" value="0" required>
            </div>

            <div class="campo">
                <label for="stock_minimo">Stock mínimo *</label>
                <input type="number" id="stock_minimo" name="stock_minimo"
                       min="0" step="1" value="5" required>
            </div>

            <div class="formulario-acciones">
                <button type="submit" class="boton">Registrar producto</button>
            </div>
        </form>
    </section>

    <section class="tarjeta-productos">
        <h2>Productos activos</h2>
        <p class="descripcion-seccion">
            Busca por código o nombre para encontrar rápidamente un producto.
        </p>

        <form class="buscador" method="GET" action="productos.php">
            <input type="search" name="buscar"
                   placeholder="Buscar por código o nombre..."
                   value="<?= h($busqueda) ?>">

            <button type="submit" class="boton">Buscar</button>

            <?php if ($busqueda !== ""): ?>
                <a class="boton boton-secundario" href="productos.php">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="tabla-contenedor">
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Stock</th>
                        <th>Stock mínimo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (count($productos) > 0): ?>
                    <?php foreach ($productos as $producto): ?>
                        <?php
                        $stockActual = (int) $producto["stock"];
                        $stockMinimoActual = (int) $producto["stock_minimo"];

                        if ($stockActual === 0) {
                            $claseEstado = "estado-agotado";
                            $textoEstado = "Agotado";
                        } elseif ($stockActual <= $stockMinimoActual) {
                            $claseEstado = "estado-bajo";
                            $textoEstado = "Stock bajo";
                        } else {
                            $claseEstado = "estado-normal";
                            $textoEstado = "Disponible";
                        }
                        ?>

                        <tr>
                            <td><?= h($producto["codigo"]) ?></td>
                            <td>
                                <strong><?= h($producto["nombre"]) ?></strong>
                                <?php if (!empty($producto["descripcion"])): ?>
                                    <br>
                                    <small style="color: #A5AEA2;">
                                        <?= h($producto["descripcion"]) ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?= h($producto["categoria"]) ?></td>
                            <td><?= h($stockActual) ?></td>
                            <td><?= h($stockMinimoActual) ?></td>
                            <td>
                                <span class="estado-stock <?= h($claseEstado) ?>">
                                    <?= h($textoEstado) ?>
                                </span>
                            </td>
                            <td>
                                <div class="acciones-tabla">
                                    <a class="boton boton-pequeno"
                                       href="editar_producto.php?id=<?= h($producto["id_producto"]) ?>">
                                        Editar
                                    </a>

                                    <form class="form-inline"
                                          method="POST"
                                          action="desactivar_producto.php"
                                          onsubmit="return confirm('¿Seguro que deseas desactivar este producto?');">
                                        <input type="hidden" name="id"
                                               value="<?= h($producto["id_producto"]) ?>">
                                        <button type="submit"
                                                class="boton boton-peligro boton-pequeno">
                                            Desactivar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="tabla-vacia">
                            <?php if ($busqueda !== ""): ?>
                                No se encontraron productos para
                                "<?= h($busqueda) ?>".
                            <?php else: ?>
                                Todavía no hay productos activos registrados.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <?php if (count($productosInactivos) > 0): ?>
        <section class="tarjeta-productos">
            <h2>Productos desactivados</h2>
            <p class="descripcion-seccion">
                Puedes reactivar un producto para que vuelva a aparecer
                en la lista de productos activos.
            </p>

            <?php foreach ($productosInactivos as $productoInactivo): ?>
                <div class="producto-inactivo">
                    <div>
                        <strong><?= h($productoInactivo["nombre"]) ?></strong>
                        <p>Código: <?= h($productoInactivo["codigo"]) ?></p>
                    </div>

                    <form method="POST" action="reactivar_producto.php"
                          onsubmit="return confirm('¿Deseas reactivar este producto?');">
                        <input type="hidden" name="id"
                               value="<?= h($productoInactivo["id_producto"]) ?>">
                        <button type="submit" class="boton boton-pequeno">
                            Reactivar
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

</main>
</div>
</body>
</html>
