<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

requerir_login();

$pdo        = obtener_conexion();
$categorias = obtener_categorias($pdo);

$datos   = ['nombre' => '', 'telefono' => '', 'correo' => '', 'ciudad' => '', 'id_categoria' => 5, 'favorito' => 0];
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores['general'] = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
    } else {
        [$datos, $errores] = validar_contacto($_POST, $categorias);

        if (!$errores) {
            try {
                // El propietario siempre es el usuario de la sesión, nunca un valor del formulario
                $pdo->prepare(
                    'INSERT INTO contactos (id_usuario, id_categoria, nombre, telefono, correo, ciudad, favorito)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                )->execute([
                    $_SESSION['usuario_id'],
                    $datos['id_categoria'],
                    $datos['nombre'],
                    $datos['telefono'],
                    $datos['correo'],
                    $datos['ciudad'],
                    $datos['favorito'],
                ]);

                flash('exito', 'Contacto registrado correctamente.');
                header('Location: panel.php');
                exit;
            } catch (PDOException $e) {
                error_log('Error al crear contacto: ' . $e->getMessage());
                $errores['general'] = 'No se pudo guardar el contacto. Inténtalo más tarde.';
            }
        }
    }
}

$titulo = 'Nuevo contacto';
require __DIR__ . '/includes/header.php';
?>
<section class="tarjeta tarjeta-estrecha">
    <h1>Nuevo contacto</h1>

    <?php if (isset($errores['general'])): ?>
        <div class="alerta alerta-error" role="alert"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <?php
    $accion     = 'contacto_nuevo.php';
    $textoBoton = 'Guardar contacto';
    require __DIR__ . '/includes/form_contacto.php';
    ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
