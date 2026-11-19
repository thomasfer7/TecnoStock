
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

$id_producto = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id_producto || $id_producto < 1) {
    header("Location: productos.php");
    exit;
}

$error = "";
$producto = null;
$categorias = [];

try {
    // Buscar el producto.
    $stmt = $conexion->prepare(
        "SELECT id_producto, codigo, nombre, descripcion,
                id_categoria, stock_minimo
         FROM productos
         WHERE id_producto = :id AND activo = 1"
    );

    $stmt->execute(["id" => $id_producto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        header("Location: productos.php");
        exit;
    }

    // Procesar el formulario.
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $codigo = trim($_POST["codigo"] ?? "");
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $id_categoria_nuevo = filter_input(
            INPUT_POST,
            "id_categoria",
            FILTER_VALIDATE_INT
        );
        $stock_minimo = filter_input(
            INPUT_POST,
            "stock_minimo",
            FILTER_VALIDATE_INT
        );

        if ($codigo === "" || strlen($codigo) > 30) {
            $error = "Ingresa un código válido de hasta 30 caracteres.";
        } elseif ($nombre === "" || strlen($nombre) > 120) {
            $error = "Ingresa un nombre válido de hasta 120 caracteres.";
        } elseif (
            $id_categoria_nuevo === false ||
            $id_categoria_nuevo === null ||
            $id_categoria_nuevo < 1
        ) {
            $error = "Selecciona una categoría válida.";
        } elseif (
            $stock_minimo === false ||
            $stock_minimo === null ||
            $stock_minimo < 0
        ) {
            $error = "El stock mínimo debe ser igual o mayor que cero.";
        } elseif (strlen($descripcion) > 65535) {
            $error = "La descripción es demasiado larga.";
        } else {
            // Comprobar que la categoría exista.
            $stmtCategoria = $conexion->prepare(
                "SELECT id_categoria
                 FROM categorias
                 WHERE id_categoria = :id"
            );

            $stmtCategoria->execute([
                "id" => $id_categoria_nuevo
            ]);

            if (!$stmtCategoria->fetch(PDO::FETCH_ASSOC)) {
                $error = "La categoría seleccionada no existe.";
            } else {
                // Actualizar el producto con PDO.
                try {
                    $stmtActualizar = $conexion->prepare(
                        "UPDATE productos
                         SET codigo = :codigo,
                             nombre = :nombre,
                             descripcion = :descripcion,
                             id_categoria = :id_categoria,
                             stock_minimo = :stock_minimo
                         WHERE id_producto = :id_producto
                           AND activo = 1"
                    );

                    $stmtActualizar->execute([
                        "codigo" => $codigo,
                        "nombre" => $nombre,
                        "descripcion" => $descripcion,
                        "id_categoria" => $id_categoria_nuevo,
                        "stock_minimo" => $stock_minimo,
                        "id_producto" => $id_producto
                    ]);

                    header(
                        "Location: productos.php?estado=actualizado#lista-productos"
                    );
                    exit;

                } catch (PDOException $e) {
                    // SQLSTATE 23000 suele indicar una restricción
                    // de integridad, por ejemplo, un código duplicado.
                    if (
                        $e->getCode() === "23000" &&
                        strpos(strtolower($e->getMessage()), "duplicate") !== false
                    ) {
                        $error = "Ya existe otro producto con ese código.";
                    } else {
                        error_log(
                            "Error al editar producto en TecnoStock: " .
                            $e->getMessage()
                        );
                        $error = "No se pudieron guardar los cambios. Revisa los datos.";
                    }
                }
            }
        }

        // Mantener lo escrito si hay un error de validación.
        $producto["codigo"] = $codigo;
        $producto["nombre"] = $nombre;
        $producto["descripcion"] = $descripcion;
        $producto["id_categoria"] = $id_categoria_nuevo ?: $producto["id_categoria"];
        $producto["stock_minimo"] = $stock_minimo !== false &&
            $stock_minimo !== null
            ? $stock_minimo
            : $producto["stock_minimo"];
    }

    // Obtener las categorías disponibles.
    $stmtCategorias = $conexion->prepare(
        "SELECT id_categoria, nombre
         FROM categorias
         ORDER BY nombre ASC"
    );

    $stmtCategorias->execute();
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en editar_producto.php: " . $e->getMessage());

    if ($producto === null) {
        header("Location: productos.php");
        exit;
    }

    $error = "Ocurrió un error al cargar los datos. Inténtalo nuevamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar producto | TecnoStock</title>

    <style>
        :root {
            --fondo: #101210;
            --tarjeta: #171A17;
            --lima: #A3E635;
            --texto: #F5F7F2;
            --secundario: #9BA497;
            --borde: #30372D;
            --rojo: #F87171;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 30px 18px;
            background: var(--fondo);
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
        }

        .contenedor {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
        }

        .marca {
            margin-bottom: 10px;
            color: var(--lima);
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .encabezado {
            margin-bottom: 25px;
        }

        h1 {
            margin: 0;
            font-size: clamp(27px, 4vw, 35px);
        }

        .subtitulo {
            margin-top: 10px;
            color: var(--secundario);
            line-height: 1.6;
        }

        .tarjeta {
            padding: 28px;
            background: var(--tarjeta);
            border: 1px solid var(--borde);
            border-radius: 15px;
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

        label {
            display: block;
            margin-bottom: 9px;
            color: #DCE3D7;
            font-size: 14px;
            font-weight: bold;
        }

        input,
        select,
        textarea {
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

        textarea {
            min-height: 110px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--lima);
            box-shadow: 0 0 0 3px rgba(163, 230, 53, .12);
        }

        input::placeholder,
        textarea::placeholder {
            color: #849080 !important;
            -webkit-text-fill-color: #849080 !important;
        }

        .error {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid rgba(248, 113, 113, .35);
            border-radius: 9px;
            background: rgba(248, 113, 113, .08);
            color: var(--rojo);
            line-height: 1.5;
        }

        .acciones {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 5px;
        }

        .boton {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 46px;
            padding: 12px 20px;
            border: 1px solid transparent;
            border-radius: 9px;
            background: var(--lima);
            color: #151910;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: .2s ease;
        }

        .boton:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .boton-secundario {
            border-color: var(--borde);
            background: #20241F;
            color: var(--texto);
        }

        .nota {
            margin-top: 20px;
            color: var(--secundario);
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 600px) {
            body {
                padding: 20px 12px;
            }

            .tarjeta {
                padding: 20px 15px;
            }

            .formulario {
                grid-template-columns: 1fr;
            }

            .campo-completo {
                grid-column: auto;
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
        <div class="marca">TecnoStock / Inventario</div>
        <h1>Editar producto</h1>
        <p class="subtitulo">
            Modifica los datos del producto seleccionado.
            El stock actual se mantiene sin cambios.
        </p>
    </header>

    <main class="tarjeta">

        <?php if ($error !== ""): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST"
              action="editar_producto.php?id=<?= h($id_producto) ?>"
              class="formulario">

            <div class="campo">
                <label for="codigo">Código</label>
                <input
                    type="text"
                    id="codigo"
                    name="codigo"
                    maxlength="30"
                    value="<?= h($producto["codigo"]) ?>"
                    placeholder="Ej.: TEC-001"
                    required
                >
            </div>

            <div class="campo">
                <label for="nombre">Nombre del producto</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    maxlength="120"
                    value="<?= h($producto["nombre"]) ?>"
                    placeholder="Nombre del producto"
                    required
                >
            </div>

            <div class="campo">
                <label for="id_categoria">Categoría</label>
                <select id="id_categoria" name="id_categoria" required>
                    <option value="">Selecciona una categoría</option>

                    <?php foreach ($categorias as $categoria): ?>
                        <option
                            value="<?= h($categoria["id_categoria"]) ?>"
                            <?= (int)$producto["id_categoria"] === (int)$categoria["id_categoria"] ? "selected" : "" ?>
                        >
                            <?= h($categoria["nombre"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="stock_minimo">Stock mínimo</label>
                <input
                    type="number"
                    id="stock_minimo"
                    name="stock_minimo"
                    min="0"
                    step="1"
                    value="<?= h($producto["stock_minimo"]) ?>"
                    required
                >
            </div>

            <div class="campo campo-completo">
                <label for="descripcion">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    maxlength="65535"
                    placeholder="Describe el producto (opcional)"
                ><?= h($producto["descripcion"]) ?></textarea>
            </div>

            <div class="campo campo-completo acciones">
                <button type="submit" class="boton">
                    Guardar cambios
                </button>

                <a href="productos.php#lista-productos"
                   class="boton boton-secundario">
                    Cancelar
                </a>
            </div>
        </form>

        <p class="nota">
            Los cambios se guardan en la base de datos. El stock actual no
            se modifica desde esta pantalla; para cambiarlo, utiliza el módulo
            de movimientos.
        </p>

    </main>
</div>
</body>
</html>
