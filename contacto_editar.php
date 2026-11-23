<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

requerir_login();

$pdo        = obtener_conexion();
$categorias = obtener_categorias($pdo);

// El id puede venir por GET (al abrir el formulario) o por POST (al enviarlo)
$idCrudo = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$id = filter_var($idCrudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    flash('error', 'El contacto solicitado no existe.');
    header('Location: panel.php');
    exit;
}

// RESTRICCIÓN DE ACCESO: el contacto debe pertenecer al usuario de la sesión.
// Si es de otro usuario, la consulta no devuelve nada y se responde igual que "no existe".
$consulta = $pdo->prepare(
    'SELECT id, nombre, telefono, correo, ciudad, id_categoria, favorito
       FROM contactos WHERE id = ? AND id_usuario = ? LIMIT 1'
);
$consulta->execute([$id, $_SESSION['usuario_id']]);
$contacto = $consulta->fetch();

if (!$contacto) {
    flash('error', 'El contacto solicitado no existe.');
    header('Location: panel.php');
    exit;
}

$datos   = $contacto;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores['general'] = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
    } else {
        [$datos, $errores] = validar_contacto($_POST, $categorias);

        if (!$errores) {
            try {
                // La condición id_usuario en el UPDATE es una segunda barrera de seguridad
                $actualizar = $pdo->prepare(
                    'UPDATE contactos
                        SET nombre = ?, telefono = ?, correo = ?, ciudad = ?, id_categoria = ?, favorito = ?
                      WHERE id = ? AND id_usuario = ?'
                );
                $actualizar->execute([
                    $datos['nombre'],
                    $datos['telefono'],
                    $datos['correo'],
                    $datos['ciudad'],
                    $datos['id_categoria'],
                    $datos['favorito'],
                    $id,
                    $_SESSION['usuario_id'],
                ]);

                flash('exito', 'Contacto actualizado correctamente.');
                header('Location: panel.php');
                exit;
            } catch (PDOException $e) {
                error_log('Error al editar contacto: ' . $e->getMessage());
                $errores['general'] = 'No se pudo actualizar el contacto. Inténtalo más tarde.';
            }
        }
    }
}

$titulo = 'Editar contacto';
require __DIR__ . '/includes/header.php';
?>
<section class="tarjeta tarjeta-estrecha">
    <h1>Editar contacto</h1>

    <?php if (isset($errores['general'])): ?>
        <div class="alerta alerta-error" role="alert"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <?php
    $accion     = 'contacto_editar.php?id=' . (int) $id;
    $textoBoton = 'Guardar cambios';
    require __DIR__ . '/includes/form_contacto.php';
    ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
