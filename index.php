```php
<?php
require_once __DIR__ . "/config/conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | TecnoStock</title>
    <link rel="stylesheet" href="assets/css/estilos.css">

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --verde: #a3e635;
            --verde-claro: #bef264;
            --fondo: #0b0e0b;
            --tarjeta: #111611;
            --borde: #293329;
            --texto: #f5f7f2;
            --secundario: #9ca69b;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--texto);
            background:
                radial-gradient(ellipse at 15% 15%, #1b2812 0%, transparent 40%),
                radial-gradient(ellipse at 90% 85%, #102016 0%, transparent 35%),
                var(--fondo);
        }

        .pagina-login {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(400px, 0.95fr);
            align-items: center;
            gap: clamp(35px, 6vw, 100px);
            max-width: 1500px;
            margin: 0 auto;
            padding: 55px clamp(25px, 7vw, 110px);
            position: relative;
            overflow: hidden;
        }

        .pagina-login::before {
            content: "";
            position: absolute;
            width: 450px;
            height: 450px;
            border: 1px solid rgba(163, 230, 53, 0.08);
            border-radius: 50%;
            top: -270px;
            left: -170px;
            box-shadow:
                0 0 0 45px rgba(163, 230, 53, 0.025),
                0 0 0 90px rgba(163, 230, 53, 0.02);
            pointer-events: none;
        }

        /* PANEL INFORMATIVO */

        .presentacion {
            position: relative;
            z-index: 1;
            animation: entrarIzquierda 0.7s ease both;
        }

        .marca {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 70px;
        }

        .logo-icono {
            width: 58px;
            height: 58px;
            color: var(--verde);
            flex-shrink: 0;
            filter: drop-shadow(0 0 12px rgba(163, 230, 53, 0.15));
        }

        .marca-nombre {
            margin: 0;
            font-size: clamp(2rem, 3.5vw, 3rem);
            line-height: 1;
            font-weight: 800;
            letter-spacing: -1.8px;
        }

        .marca-nombre span {
            color: var(--verde);
        }

        .marca-descripcion {
            margin: 10px 0 0;
            color: var(--secundario);
            font-size: 0.96rem;
            letter-spacing: 0.3px;
        }

        .linea-verde {
            width: 58px;
            height: 4px;
            border-radius: 10px;
            background: var(--verde);
            margin-bottom: 25px;
            box-shadow: 0 0 18px rgba(163, 230, 53, 0.25);
        }

        .presentacion h2 {
            max-width: 510px;
            margin: 0;
            font-size: clamp(2.2rem, 4.2vw, 3.8rem);
            line-height: 1.08;
            letter-spacing: -1.8px;
            font-weight: 800;
        }

        .presentacion h2 span {
            display: block;
            color: var(--verde);
        }

        .descripcion-principal {
            max-width: 430px;
            margin: 22px 0 36px;
            color: #aab3a8;
            font-size: 1.08rem;
            line-height: 1.8;
        }

        .beneficios {
            display: grid;
            gap: 22px;
        }

        .beneficio {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .beneficio-icono {
            width: 49px;
            height: 49px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            color: var(--verde);
            background: rgba(163, 230, 53, 0.055);
            border: 1px solid rgba(163, 230, 53, 0.24);
            border-radius: 15px;
        }

        .beneficio-icono svg {
            width: 23px;
            height: 23px;
        }

        .beneficio h3 {
            margin: 0 0 5px;
            font-size: 0.98rem;
            font-weight: 650;
        }

        .beneficio p {
            margin: 0;
            color: var(--secundario);
            font-size: 0.87rem;
            line-height: 1.5;
        }

        .etiqueta-sistema {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-top: 44px;
            padding: 10px 14px;
            border: 1px solid var(--borde);
            border-radius: 30px;
            color: #aeb7a8;
            background: rgba(255, 255, 255, 0.015);
            font-size: 0.8rem;
        }

        .punto-activo {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--verde);
            box-shadow: 0 0 10px rgba(163, 230, 53, 0.6);
        }

        /* TARJETA DE INICIO DE SESIÓN */

        .zona-formulario {
            position: relative;
            z-index: 1;
            animation: entrarDerecha 0.7s ease both;
        }

        .tarjeta {
            width: 100%;
            max-width: 510px;
            margin: 0 auto;
            padding: clamp(30px, 4vw, 48px);
            background: linear-gradient(
                145deg,
                rgba(20, 26, 20, 0.98),
                rgba(12, 16, 13, 0.98)
            );
            border: 1px solid #344333;
            border-radius: 24px;
            box-shadow:
                0 30px 80px rgba(0, 0, 0, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.025);
        }

        .encabezado-formulario {
            text-align: center;
            margin-bottom: 36px;
        }

        .logo-formulario {
            width: 57px;
            height: 57px;
            color: var(--verde);
            margin-bottom: 13px;
            filter: drop-shadow(0 0 15px rgba(163, 230, 53, 0.18));
        }

        .encabezado-formulario h2 {
            margin: 0;
            font-size: clamp(1.8rem, 3vw, 2.45rem);
            letter-spacing: -1.2px;
            font-weight: 800;
        }

        .encabezado-formulario h2 span {
            color: var(--verde);
        }

        .subtitulo-formulario {
            margin: 24px 0 8px;
            color: #d4dccf;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 4px;
        }

        .ayuda-formulario {
            margin: 0;
            color: var(--secundario);
            font-size: 0.91rem;
        }

        .campo {
            margin-bottom: 23px;
        }

        .campo label {
            display: block;
            margin-bottom: 10px;
            color: #dce3d8;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .entrada-contenedor {
            position: relative;
        }

        .campo input {
            display: block;
            width: 100%;
            height: 57px;
            padding: 0 16px;
            color: #ffffff;
            -webkit-text-fill-color: #ffffff;
            caret-color: var(--verde);
            background: #0c110d;
            border: 1px solid #39463a;
            border-radius: 11px;
            outline: none;
            font: inherit;
            font-size: 0.95rem;
            transition:
                border-color 0.2s,
                box-shadow 0.2s,
                background 0.2s;
        }

        .campo input::placeholder {
            color: #788477;
            -webkit-text-fill-color: #788477;
        }

        .campo input:focus {
            border-color: var(--verde);
            background: #101710;
            box-shadow: 0 0 0 4px rgba(163, 230, 53, 0.09);
        }

        .entrada-contenedor input {
            padding-right: 55px;
        }

        .boton-ojo {
            position: absolute;
            top: 50%;
            right: 13px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            padding: 0;
            border: none;
            border-radius: 8px;
            color: #aab5a8;
            background: transparent;
            cursor: pointer;
            transition: color 0.2s, background 0.2s;
        }

        .boton-ojo:hover {
            color: var(--verde);
            background: rgba(163, 230, 53, 0.08);
        }

        .boton-ojo:focus-visible {
            outline: 2px solid var(--verde);
            outline-offset: 2px;
        }

        .boton-ojo svg {
            display: block;
            width: 21px;
            height: 21px;
        }

        .boton {
            width: 100%;
            min-height: 57px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 9px;
            padding: 15px 20px;
            border: none;
            border-radius: 11px;
            background: linear-gradient(110deg, #a3e635, #b8f34e);
            color: #13190d;
            font-size: 1rem;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(163, 230, 53, 0.08);
            transition:
                transform 0.2s,
                box-shadow 0.2s,
                filter 0.2s;
        }

        .boton svg {
            width: 21px;
            height: 21px;
        }

        .boton:hover {
            transform: translateY(-2px);
            filter: brightness(1.06);
            box-shadow: 0 12px 30px rgba(163, 230, 53, 0.19);
        }

        .boton:active {
            transform: translateY(0);
        }

        .error {
            margin: 0 0 22px;
            padding: 13px 15px;
            color: #fca5a5;
            background: rgba(220, 38, 38, 0.09);
            border: 1px solid rgba(248, 113, 113, 0.3);
            border-radius: 10px;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .pie {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 13px;
            margin-top: 32px;
            padding-top: 23px;
            border-top: 1px solid #273027;
            color: #899487;
            font-size: 0.79rem;
        }

        .pie-linea {
            width: 23px;
            height: 2px;
            border-radius: 5px;
            background: var(--verde);
            opacity: 0.8;
        }

        @keyframes entrarIzquierda {
            from {
                opacity: 0;
                transform: translateX(-18px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes entrarDerecha {
            from {
                opacity: 0;
                transform: translateX(18px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 900px) {
            .pagina-login {
                grid-template-columns: 1fr;
                gap: 35px;
                padding: 35px 22px;
            }

            .presentacion {
                max-width: 510px;
                width: 100%;
                margin: 0 auto;
            }

            .marca {
                margin-bottom: 35px;
            }

            .presentacion h2 {
                font-size: clamp(2rem, 6vw, 3rem);
            }

            .descripcion-principal {
                margin: 15px 0 24px;
            }

            .beneficios {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 12px;
            }

            .beneficio {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .etiqueta-sistema {
                margin-top: 25px;
            }

            .zona-formulario {
                width: 100%;
            }
        }

        @media (max-width: 520px) {
            .pagina-login {
                padding: 25px 16px;
                gap: 27px;
            }

            .marca {
                gap: 11px;
                margin-bottom: 30px;
            }

            .logo-icono {
                width: 43px;
                height: 43px;
            }

            .marca-nombre {
                font-size: 2rem;
            }

            .marca-descripcion {
                font-size: 0.83rem;
            }

            .presentacion h2 {
                font-size: 2rem;
                letter-spacing: -1px;
            }

            .descripcion-principal {
                font-size: 0.94rem;
                line-height: 1.6;
            }

            .beneficios {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .beneficio {
                flex-direction: row;
                align-items: center;
            }

            .beneficio-icono {
                width: 42px;
                height: 42px;
                border-radius: 12px;
            }

            .beneficio h3 {
                font-size: 0.9rem;
            }

            .beneficio p {
                font-size: 0.8rem;
            }

            .etiqueta-sistema {
                margin-top: 22px;
            }

            .tarjeta {
                padding: 29px 21px 22px;
                border-radius: 19px;
            }

            .encabezado-formulario {
                margin-bottom: 28px;
            }

            .logo-formulario {
                width: 45px;
                height: 45px;
            }

            .encabezado-formulario h2 {
                font-size: 2rem;
            }

            .subtitulo-formulario {
                margin-top: 20px;
                letter-spacing: 3px;
            }

            .campo input,
            .boton {
                min-height: 53px;
                height: 53px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

    <main class="pagina-login">

        <!-- PRESENTACIÓN DE TECNOSTOCK -->
        <section class="presentacion">

            <header class="marca">
                <svg class="logo-icono"
                     xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 64 64"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="4"
                     stroke-linejoin="round"
                     stroke-linecap="round"
                     aria-hidden="true">
                    <path d="M32 4 58 18.5 32 33 6 18.5 32 4Z"/>
                    <path d="M6 18.5V47L32 61V33"/>
                    <path d="M58 18.5V47L32 61"/>
                    <path d="M19 11.5 45 26"/>
                </svg>

                <div>
                    <h1 class="marca-nombre">
                        Tecno<span>Stock</span>
                    </h1>
                    <p class="marca-descripcion">
                        Sistema de gestión de inventario
                    </p>
                </div>
            </header>

            <div class="linea-verde"></div>

            <h2>
                Tu inventario,
                <span>siempre en control.</span>
            </h2>

            <p class="descripcion-principal">
                Organiza tus productos, controla las existencias
                y administra tu inventario desde un solo lugar.
            </p>

            <div class="beneficios">

                <article class="beneficio">
                    <div class="beneficio-icono">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="m12 3 9 5-9 5-9-5 9-5Z"/>
                            <path d="M3 8v9l9 5 9-5V8"/>
                            <path d="M12 13v9"/>
                        </svg>
                    </div>
                    <div>
                        <h3>Control de productos</h3>
                        <p>Organiza y consulta tu inventario.</p>
                    </div>
                </article>

                <article class="beneficio">
                    <div class="beneficio-icono">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M3 3v18h18"/>
                            <path d="m7 14 4-4 4 3 6-7"/>
                            <path d="M17 6h4v4"/>
                        </svg>
                    </div>
                    <div>
                        <h3>Movimientos de stock</h3>
                        <p>Registra entradas y salidas.</p>
                    </div>
                </article>

                <article class="beneficio">
                    <div class="beneficio-icono">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                            <path d="m9 12 2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3>Acceso protegido</h3>
                        <p>Ingresa con tu cuenta de usuario.</p>
                    </div>
                </article>

            </div>

            <div class="etiqueta-sistema">
                <span class="punto-activo"></span>
                Plataforma de gestión de inventario
            </div>

        </section>

        <!-- FORMULARIO ORIGINAL DE INICIO DE SESIÓN -->
        <section class="zona-formulario">

            <div class="tarjeta">

                <header class="encabezado-formulario">

                    <svg class="logo-formulario"
                         xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 64 64"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="4"
                         stroke-linejoin="round"
                         stroke-linecap="round"
                         aria-hidden="true">
                        <path d="M32 4 58 18.5 32 33 6 18.5 32 4Z"/>
                        <path d="M6 18.5V47L32 61V33"/>
                        <path d="M58 18.5V47L32 61"/>
                        <path d="M19 11.5 45 26"/>
                    </svg>

                    <h2>
                        Tecno<span>Stock</span>
                    </h2>

                    <p class="subtitulo-formulario">
                        INICIAR SESIÓN
                    </p>

                    <p class="ayuda-formulario">
                        Accede a tu cuenta para continuar
                    </p>

                </header>

                <?php if (isset($_GET["error"])): ?>
                    <p role="alert" class="error">
                        Usuario o contraseña incorrectos.
                    </p>
                <?php endif; ?>

                <form action="login.php" method="POST">

                    <div class="campo">
                        <label for="usuario">
                            Nombre de usuario
                        </label>

                        <div class="entrada-contenedor">
                            <input
                                type="text"
                                id="usuario"
                                name="usuario"
                                required
                                maxlength="50"
                                autocomplete="username"
                                placeholder="Ingresa tu usuario"
                            >
                        </div>
                    </div>

                    <div class="campo">
                        <label for="contrasena">
                            Contraseña
                        </label>

                        <div class="entrada-contenedor">
                            <input
                                type="password"
                                id="contrasena"
                                name="contrasena"
                                required
                                autocomplete="current-password"
                                placeholder="Ingresa tu contraseña"
                            >

                            <button
                                type="button"
                                class="boton-ojo"
                                id="mostrarContrasena"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false"
                                title="Mostrar contraseña"
                            >
                                <svg
                                    id="iconoOjo"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    aria-hidden="true"
                                >
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="boton">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2.2"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M10 17l5-5-5-5"/>
                            <path d="M15 12H3"/>
                            <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>
                        </svg>

                        Iniciar sesión
                    </button>

                </form>

                <footer class="pie">
                    <span class="pie-linea"></span>
                    <span>TecnoStock © 2026</span>
                    <span class="pie-linea"></span>
                </footer>

            </div>

        </section>

    </main>

    <script>
        const campoContrasena = document.getElementById("contrasena");
        const botonOjo = document.getElementById("mostrarContrasena");
        const iconoOjo = document.getElementById("iconoOjo");

        botonOjo.addEventListener("click", function () {
            const mostrar = campoContrasena.type === "password";

            campoContrasena.type = mostrar ? "text" : "password";

            botonOjo.setAttribute("aria-pressed", String(mostrar));
            botonOjo.setAttribute(
                "aria-label",
                mostrar ? "Ocultar contraseña" : "Mostrar contraseña"
            );
            botonOjo.setAttribute(
                "title",
                mostrar ? "Ocultar contraseña" : "Mostrar contraseña"
            );

            if (mostrar) {
                iconoOjo.innerHTML = `
                    <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5.6 20 2 12 2 12a18.7 18.7 0 0 1 4.1-5.1"/>
                    <path d="M9.9 4.2A10.6 10.6 0 0 1 12 4c6.4 0 10 8 10 8a18.7 18.7 0 0 1-2.2 3.2"/>
                    <path d="m2 2 20 20"/>
                `;
            } else {
                iconoOjo.innerHTML = `
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                    <circle cx="12" cy="12" r="3"/>
                `;
            }
        });
    </script>

</body>
</html>
```