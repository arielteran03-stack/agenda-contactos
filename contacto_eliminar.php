<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

requerir_login();

$pdo = obtener_conexion();

$idCrudo = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? '') : ($_GET['id'] ?? '');
$id = filter_var($idCrudo, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    flash('error', 'El contacto solicitado no existe.');
    header('Location: panel.php');
    exit;
}

// RESTRICCIÓN DE ACCESO: solo contactos del usuario autenticado
$consulta = $pdo->prepare(
    'SELECT id, nombre, telefono, correo, ciudad FROM contactos WHERE id = ? AND id_usuario = ? LIMIT 1'
);
$consulta->execute([$id, $_SESSION['usuario_id']]);
$contacto = $consulta->fetch();

if (!$contacto) {
    flash('error', 'El contacto solicitado no existe.');
    header('Location: panel.php');
    exit;
}

// La eliminación solo se ejecuta por POST (tras la confirmación) y con token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        flash('error', 'La solicitud no es válida. Inténtalo de nuevo.');
        header('Location: panel.php');
        exit;
    }

    try {
        $pdo->prepare('DELETE FROM contactos WHERE id = ? AND id_usuario = ?')
            ->execute([$id, $_SESSION['usuario_id']]);
        flash('exito', 'Contacto eliminado correctamente.');
    } catch (PDOException $e) {
        error_log('Error al eliminar contacto: ' . $e->getMessage());
        flash('error', 'No se pudo eliminar el contacto. Inténtalo más tarde.');
    }
    header('Location: panel.php');
    exit;
}

// GET: página de confirmación
$titulo = 'Eliminar contacto';
require __DIR__ . '/includes/header.php';
?>
<section class="tarjeta tarjeta-estrecha">
    <h1>Eliminar contacto</h1>
    <p>¿Seguro que deseas eliminar el siguiente contacto? Esta acción no se puede deshacer.</p>

    <ul class="detalle-contacto">
        <li><strong>Nombre:</strong> <?= e($contacto['nombre']) ?></li>
        <li><strong>Teléfono:</strong> <?= e($contacto['telefono']) ?></li>
        <li><strong>Correo:</strong> <?= e($contacto['correo']) ?></li>
        <li><strong>Ciudad:</strong> <?= e($contacto['ciudad']) ?></li>
    </ul>

    <form method="post" action="contacto_eliminar.php" class="botones-form">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int) $contacto['id'] ?>">
        <button type="submit" class="btn btn-peligro">Sí, eliminar</button>
        <a class="btn btn-secundario" href="panel.php">Cancelar</a>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
