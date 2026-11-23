<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

requerir_login();

$pdo        = obtener_conexion();
$categorias = obtener_categorias($pdo);

/* ------------------------------------------------------------
 * Parámetros de búsqueda, filtro y orden (todos validados con listas blancas)
 * ------------------------------------------------------------ */
$camposBusqueda = ['todos' => 'Todos los campos', 'nombre' => 'Nombre', 'correo' => 'Correo', 'ciudad' => 'Ciudad'];
$opcionesOrden  = ['nombre' => 'Nombre', 'ciudad' => 'Ciudad', 'fecha' => 'Fecha de creación'];
$columnasOrden  = ['nombre' => 'c.nombre', 'ciudad' => 'c.ciudad', 'fecha' => 'c.fecha_creacion'];

$q = trim((string) ($_GET['q'] ?? ''));
$q = mb_substr($q, 0, 100, 'UTF-8');

$campo = (string) ($_GET['campo'] ?? 'todos');
if (!isset($camposBusqueda[$campo])) {
    $campo = 'todos';
}

$idCategoria = (int) ($_GET['categoria'] ?? 0);
if (!isset($categorias[$idCategoria])) {
    $idCategoria = 0; // 0 = todas
}

$soloFavoritos = ($_GET['favoritos'] ?? '') === '1';

$orden = (string) ($_GET['orden'] ?? 'nombre');
if (!isset($opcionesOrden[$orden])) {
    $orden = 'nombre';
}
$direccion = (($_GET['dir'] ?? 'asc') === 'desc') ? 'desc' : 'asc';

/* ------------------------------------------------------------
 * Consulta: siempre limitada al usuario de la sesión
 * ------------------------------------------------------------ */
$sql = 'SELECT c.id, c.nombre, c.telefono, c.correo, c.ciudad, c.favorito, c.fecha_creacion,
               cat.nombre AS categoria
          FROM contactos c
          JOIN categorias cat ON cat.id = c.id_categoria
         WHERE c.id_usuario = ?';
$params = [$_SESSION['usuario_id']];

if ($q !== '') {
    // Se escapan los comodines de LIKE para que el texto se busque literalmente
    $patron = '%' . str_replace(['|', '%', '_'], ['||', '|%', '|_'], $q) . '%';
    if ($campo === 'todos') {
        $sql .= " AND (c.nombre LIKE ? ESCAPE '|' OR c.correo LIKE ? ESCAPE '|' OR c.ciudad LIKE ? ESCAPE '|')";
        array_push($params, $patron, $patron, $patron);
    } else {
        // $campo viene de una lista blanca, nunca directamente del usuario
        $sql .= " AND c.$campo LIKE ? ESCAPE '|'";
        $params[] = $patron;
    }
}
if ($idCategoria > 0) {
    $sql .= ' AND c.id_categoria = ?';
    $params[] = $idCategoria;
}
if ($soloFavoritos) {
    $sql .= ' AND c.favorito = 1';
}

$sql .= ' ORDER BY ' . $columnasOrden[$orden] . ' ' . strtoupper($direccion) . ', c.id ASC';

$consulta = $pdo->prepare($sql);
$consulta->execute($params);
$contactos = $consulta->fetchAll();

// Total de contactos del usuario (sin filtros), para mostrar "x de y"
$total = $pdo->prepare('SELECT COUNT(*) FROM contactos WHERE id_usuario = ?');
$total->execute([$_SESSION['usuario_id']]);
$totalContactos = (int) $total->fetchColumn();

$hayFiltros = $q !== '' || $idCategoria > 0 || $soloFavoritos;

// Query string con los filtros actuales, para volver al mismo listado tras marcar un favorito
$volver = http_build_query(array_filter([
    'q'         => $q,
    'campo'     => $campo !== 'todos' ? $campo : '',
    'categoria' => $idCategoria ?: '',
    'favoritos' => $soloFavoritos ? '1' : '',
    'orden'     => $orden !== 'nombre' ? $orden : '',
    'dir'       => $direccion !== 'asc' ? $direccion : '',
], static fn($v) => $v !== '' && $v !== null));

$titulo = 'Panel principal';
require __DIR__ . '/includes/header.php';
?>
<section class="tarjeta">
    <h1>Bienvenido, <?= e($_SESSION['usuario_nombre']) ?></h1>
    <p class="datos-usuario">
        <strong>Nombre:</strong> <?= e($_SESSION['usuario_nombre']) ?><br>
        <strong>Correo electrónico:</strong> <?= e($_SESSION['usuario_correo']) ?>
    </p>
</section>

<section class="tarjeta">
    <div class="encabezado-seccion">
        <h2>
            Mis contactos
            (<?= $hayFiltros ? count($contactos) . ' de ' . $totalContactos : $totalContactos ?>)
        </h2>
        <a class="btn" href="contacto_nuevo.php">+ Nuevo contacto</a>
    </div>

    <!-- Búsqueda, filtros y orden -->
    <form method="get" action="panel.php" class="filtros">
        <div class="filtro filtro-grande">
            <label for="q">Buscar</label>
            <input type="search" id="q" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Escribe para buscar…">
        </div>

        <div class="filtro">
            <label for="campo">Buscar en</label>
            <select id="campo" name="campo">
                <?php foreach ($camposBusqueda as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>" <?= $campo === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro">
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria">
                <option value="0">Todas</option>
                <?php foreach ($categorias as $idCat => $nombreCat): ?>
                    <option value="<?= (int) $idCat ?>" <?= $idCategoria === (int) $idCat ? 'selected' : '' ?>><?= e($nombreCat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro">
            <label for="orden">Ordenar por</label>
            <select id="orden" name="orden">
                <?php foreach ($opcionesOrden as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>" <?= $orden === $valor ? 'selected' : '' ?>><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro">
            <label for="dir">Sentido</label>
            <select id="dir" name="dir">
                <option value="asc"  <?= $direccion === 'asc'  ? 'selected' : '' ?>>Ascendente</option>
                <option value="desc" <?= $direccion === 'desc' ? 'selected' : '' ?>>Descendente</option>
            </select>
        </div>

        <label class="casilla filtro-favoritos">
            <input type="checkbox" name="favoritos" value="1" <?= $soloFavoritos ? 'checked' : '' ?>>
            Solo favoritos ★
        </label>

        <div class="filtro-botones">
            <button type="submit" class="btn">Aplicar</button>
            <?php if ($hayFiltros || $orden !== 'nombre' || $direccion !== 'asc'): ?>
                <a class="btn btn-secundario" href="panel.php">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!$contactos): ?>
        <p class="vacio">
            <?= $hayFiltros
                ? 'Ningún contacto coincide con la búsqueda o los filtros aplicados.'
                : 'Aún no tienes contactos registrados.' ?>
        </p>
    <?php else: ?>
        <div class="tabla-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="col-fav" title="Favorito">★</th>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Ciudad</th>
                        <th>Categoría</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contactos as $c): ?>
                    <tr>
                        <td class="col-fav">
                            <form method="post" action="contacto_favorito.php" class="form-estrella">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <input type="hidden" name="volver" value="<?= e($volver) ?>">
                                <button type="submit"
                                        class="estrella <?= $c['favorito'] ? 'activa' : '' ?>"
                                        title="<?= $c['favorito'] ? 'Quitar de favoritos' : 'Marcar como favorito' ?>"
                                        aria-label="<?= $c['favorito'] ? 'Quitar de favoritos' : 'Marcar como favorito' ?>">
                                    <?= $c['favorito'] ? '★' : '☆' ?>
                                </button>
                            </form>
                        </td>
                        <td><?= e($c['nombre']) ?></td>
                        <td><?= e($c['telefono']) ?></td>
                        <td><?= e($c['correo']) ?></td>
                        <td><?= e($c['ciudad']) ?></td>
                        <td><span class="etiqueta"><?= e($c['categoria']) ?></span></td>
                        <td><?= e(date('d/m/Y', strtotime($c['fecha_creacion']))) ?></td>
                        <td class="acciones">
                            <a class="btn btn-secundario" href="contacto_editar.php?id=<?= (int) $c['id'] ?>">Editar</a>
                            <a class="btn btn-peligro" href="contacto_eliminar.php?id=<?= (int) $c['id'] ?>">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
