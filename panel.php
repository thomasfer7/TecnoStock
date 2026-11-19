
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

// Variables del resumen
$totalProductos = 0;
$totalUnidades = 0;
$totalStockBajo = 0;

// Consultar resumen del inventario
$sql = "SELECT
            COUNT(*) AS total_productos,
            COALESCE(SUM(stock), 0) AS total_unidades,
            COALESCE(SUM(
                CASE WHEN stock <= stock_minimo THEN 1 ELSE 0 END
            ), 0) AS stock_bajo
        FROM productos
        WHERE activo = 1";

$resultado = $conexion->query($sql);
$fila = $resultado->fetch(PDO::FETCH_ASSOC);

if ($fila) {
    $totalProductos = (int) $fila["total_productos"];
    $totalUnidades = (int) $fila["total_unidades"];
    $totalStockBajo = (int) $fila["stock_bajo"];
}

// Consultar productos que necesitan reposición
$sqlBajos = "SELECT
                p.id_producto,
                p.codigo,
                p.nombre,
                p.stock,
                p.stock_minimo,
                c.nombre AS categoria
             FROM productos p
             INNER JOIN categorias c
                ON p.id_categoria = c.id_categoria
             WHERE p.activo = 1
               AND p.stock <= p.stock_minimo
             ORDER BY p.stock ASC, p.nombre ASC
             LIMIT 8";

$resultadoBajos = $conexion->query($sqlBajos);
$productosBajos = $resultadoBajos->fetchAll(PDO::FETCH_ASSOC);

$nombreUsuario = $_SESSION["nombre"] ?? $_SESSION["usuario"] ?? "Usuario";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel | TecnoStock</title>

    <link rel="stylesheet" href="assets/css/estilos.css">

    <style>
        :root {
            --lima: #A3E635;
            --lima-hover: #BEF264;
            --fondo: #101210;
            --superficie: #171A17;
            --superficie-hover: #20251E;
            --texto: #FFFFFF;
            --texto-secundario: #A5AEA2;
            --borde: #363D34;
        }

        * {
            box-sizing: border-box;
        }

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

        .panel-pagina {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .panel-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            padding-bottom: 22px;
            border-bottom: 1px solid var(--borde);
        }

        .logo {
            color: var(--lima) !important;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .panel-cabecera h1 {
            margin: 8px 0;
            color: var(--texto) !important;
            text-align: left;
            font-size: 30px;
        }

        .panel-cabecera p {
            margin: 0;
            color: var(--texto-secundario) !important;
        }

        .panel-cabecera-acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .panel-cabecera-acciones .boton {
            width: auto;
            display: inline-block;
            padding: 10px 16px;
            border: 1px solid var(--lima);
            border-radius: 8px;
            background: var(--lima);
            color: #151810 !important;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .panel-cabecera-acciones .boton:hover {
            background: var(--lima-hover);
            transform: translateY(-2px);
        }

        .panel-cabecera-acciones .boton-secundario {
            background: transparent;
            color: var(--lima) !important;
        }

        .panel-cabecera-acciones .boton-secundario:hover {
            background: #252D20;
        }

        .panel-cabecera-acciones .boton-peligro {
            background: transparent;
            border-color: #7F3434;
            color: #FCA5A5 !important;
        }

        .panel-cabecera-acciones .boton-peligro:hover {
            background: #321E1E;
        }

        .panel-resumen {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .panel-resumen .tarjeta {
            width: auto;
            max-width: none;
            margin: 0;
            padding: 24px;
            background: var(--superficie) !important;
            border: 1px solid var(--borde);
            border-radius: 14px;
            text-align: left;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15);
        }

        .panel-resumen .tarjeta:hover {
            border-color: var(--lima);
        }

        .panel-etiqueta {
            color: var(--texto-secundario) !important;
            font-size: 14px;
        }

        .panel-numero {
            margin: 12px 0 5px;
            color: var(--lima) !important;
            font-size: 36px;
            font-weight: 800;
            line-height: 1.2;
        }

        .panel-resumen .tarjeta p {
            margin-bottom: 0;
        }

        .panel-seccion {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 25px;
            background: var(--superficie) !important;
            border: 1px solid var(--borde);
            border-radius: 14px;
            text-align: left;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.15);
        }

        .panel-seccion h2 {
            margin-top: 0;
            color: var(--texto) !important;
            text-align: left;
        }

        .panel-seccion > p {
            color: var(--texto-secundario);
        }

        .panel-tabla-wrap {
            width: 100%;
            overflow-x: auto;
            margin-top: 20px;
        }

        .panel-tabla {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
        }

        .panel-tabla th,
        .panel-tabla td {
            padding: 14px 12px;
            border-bottom: 1px solid var(--borde);
            text-align: left;
        }

        .panel-tabla th {
            background: var(--superficie-hover);
            color: var(--lima) !important;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .panel-tabla td {
            color: var(--texto) !important;
        }

        .panel-tabla tbody tr:hover {
            background: #20251E;
        }

        .panel-alerta {
            display: inline-block;
            padding: 5px 10px;
            border: 1px solid #704126;
            border-radius: 7px;
            background: #3B241D;
            color: #FDBA74 !important;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .panel-boton-inferior {
            margin-top: 22px;
        }

        .panel-boton-inferior .boton {
            display: inline-block;
            padding: 11px 18px;
            border: 1px solid var(--lima);
            border-radius: 8px;
            background: var(--lima);
            color: #151810 !important;
            font-weight: 700;
            text-decoration: none;
        }

        .panel-boton-inferior .boton:hover {
            background: var(--lima-hover);
        }

        .panel-vacio {
            padding: 24px 12px !important;
            color: var(--texto-secundario) !important;
            text-align: center !important;
        }

        @media (max-width: 850px) {
            .panel-cabecera {
                align-items: flex-start;
                flex-direction: column;
            }

            .panel-resumen {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 500px) {
            .contenedor {
                padding: 20px 12px;
            }

            .panel-cabecera h1 {
                font-size: 25px;
            }

            .panel-cabecera-acciones {
                width: 100%;
            }

            .panel-cabecera-acciones .boton {
                flex: 1;
            }

            .panel-seccion {
                padding: 16px;
            }
        }
    </style>
</head>

<body>
<div class="contenedor">
    <main class="panel-pagina">

        <header class="panel-cabecera">
            <div>
                <div class="logo">TecnoStock</div>

                <h1>Panel de control</h1>

                <p>
                    Bienvenido, <?= h($nombreUsuario) ?>.
                    Aquí tienes el resumen de tu inventario.
                </p>
            </div>

            <nav class="panel-cabecera-acciones">
                <a class="boton" href="productos.php">Productos</a>

                <a class="boton boton-secundario" href="movimientos.php">
                    Movimientos
                </a>

                <a class="boton boton-peligro" href="logout.php">
                    Cerrar sesión
                </a>
            </nav>
        </header>

        <section class="panel-resumen">

            <article class="tarjeta">
                <div class="panel-etiqueta">Productos activos</div>

                <div class="panel-numero">
                    <?= $totalProductos ?>
                </div>

                <p class="panel-etiqueta">
                    Productos registrados y activos.
                </p>
            </article>

            <article class="tarjeta">
                <div class="panel-etiqueta">Unidades disponibles</div>

                <div class="panel-numero">
                    <?= $totalUnidades ?>
                </div>

                <p class="panel-etiqueta">
                    Suma de las unidades en inventario.
                </p>
            </article>

            <article class="tarjeta">
                <div class="panel-etiqueta">Productos con stock bajo</div>

                <div class="panel-numero">
                    <?= $totalStockBajo ?>
                </div>

                <p class="panel-etiqueta">
                    Productos en su stock mínimo o por debajo.
                </p>
            </article>

        </section>

        <section class="tarjeta panel-seccion">

            <h2>Productos que necesitan reposición</h2>

            <p class="panel-etiqueta">
                Se muestran hasta ocho productos cuyo stock está
                en el mínimo o por debajo.
            </p>

            <div class="panel-tabla-wrap">
                <table class="panel-tabla">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock actual</th>
                            <th>Stock mínimo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($productosBajos) > 0): ?>

                        <?php foreach ($productosBajos as $producto): ?>
                            <tr>
                                <td><?= h($producto["codigo"]) ?></td>

                                <td><?= h($producto["nombre"]) ?></td>

                                <td><?= h($producto["categoria"]) ?></td>

                                <td><?= h($producto["stock"]) ?></td>

                                <td><?= h($producto["stock_minimo"]) ?></td>

                                <td>
                                    <span class="panel-alerta">
                                        Stock bajo
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="panel-vacio">
                                No hay productos con stock bajo en este momento.
                            </td>
                        </tr>

                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <p class="panel-boton-inferior">
                <a class="boton" href="productos.php">
                    Administrar productos
                </a>
            </p>

        </section>

    </main>
</div>
</body>
</html>
