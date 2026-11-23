<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

requerir_login();

// Solo por POST y con token CSRF
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    flash('error', 'La solicitud no es válida. Inténtalo de nuevo.');
    header('Location: panel.php');
    exit;
}

// Reconstruye el query string del listado usando solo claves permitidas
$permitidas = ['q', 'campo', 'categoria', 'favoritos', 'orden', 'dir'];
parse_str((string) ($_POST['volver'] ?? ''), $previo);
$filtros = [];
foreach ($permitidas as $clave) {
    if (isset($previo[$clave]) && is_string($previo[$clave]) && $previo[$clave] !== '') {
        $filtros[$clave] = mb_substr($previo[$clave], 0, 100, 'UTF-8');
    }
}
$destino = 'panel.php' . ($filtros ? '?' . http_build_query($filtros) : '');

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    flash('error', 'El contacto solicitado no existe.');
    header('Location: ' . $destino);
    exit;
}

try {
    // Invierte el valor solo si el contacto pertenece al usuario de la sesión
    $actualizar = obtener_conexion()->prepare(
        'UPDATE contactos SET favorito = 1 - favorito WHERE id = ? AND id_usuario = ?'
    );
    $actualizar->execute([$id, $_SESSION['usuario_id']]);

    if ($actualizar->rowCount() === 0) {
        flash('error', 'El contacto solicitado no existe.');
    }
} catch (PDOException $e) {
    error_log('Error al cambiar favorito: ' . $e->getMessage());
    flash('error', 'No se pudo actualizar el contacto. Inténtalo más tarde.');
}

header('Location: ' . $destino);
exit;
