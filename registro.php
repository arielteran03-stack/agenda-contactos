<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

redirigir_si_autenticado();

$errores = [];
$nombre  = '';
$correo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $errores['general'] = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
    } else {
        $nombre    = trim((string) ($_POST['nombre'] ?? ''));
        $correo    = trim((string) ($_POST['correo'] ?? ''));
        $password  = (string) ($_POST['password'] ?? '');
        $confirmar = (string) ($_POST['confirmar'] ?? '');

        // ---- Validación en el servidor ----
        $largoNombre = mb_strlen($nombre, 'UTF-8');
        if ($largoNombre < 3 || $largoNombre > 100) {
            $errores['nombre'] = 'El nombre completo es obligatorio (entre 3 y 100 caracteres).';
        }
        if ($correo === '' || mb_strlen($correo, 'UTF-8') > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $errores['correo'] = 'Ingresa un correo electrónico válido.';
        }
        if (strlen($password) < 8) {
            $errores['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errores['password'] = 'La contraseña debe incluir letras y números.';
        } elseif (strlen($password) > 72) {
            // bcrypt solo considera los primeros 72 bytes
            $errores['password'] = 'La contraseña no puede superar los 72 caracteres.';
        }
        if ($password !== $confirmar) {
            $errores['confirmar'] = 'Las contraseñas no coinciden.';
        }

        // ---- Correo no existente ----
        if (!$errores) {
            $pdo = obtener_conexion();
            $consulta = $pdo->prepare('SELECT id FROM usuarios WHERE correo = ? LIMIT 1');
            $consulta->execute([$correo]);

            if ($consulta->fetch()) {
                $errores['correo'] = 'Este correo electrónico ya está registrado.';
            } else {
                try {
                    $insertar = $pdo->prepare(
                        'INSERT INTO usuarios (nombre, correo, password) VALUES (?, ?, ?)'
                    );
                    // La contraseña NUNCA se guarda en texto plano
                    $insertar->execute([$nombre, $correo, password_hash($password, PASSWORD_DEFAULT)]);

                    flash('exito', 'Registro exitoso. Ahora puedes iniciar sesión.');
                    header('Location: login.php');
                    exit;
                } catch (PDOException $e) {
                    if ($e->getCode() === '23000') { // clave única duplicada (condición de carrera)
                        $errores['correo'] = 'Este correo electrónico ya está registrado.';
                    } else {
                        error_log('Error al registrar usuario: ' . $e->getMessage());
                        $errores['general'] = 'No se pudo completar el registro. Inténtalo más tarde.';
                    }
                }
            }
        }
    }
}

$titulo = 'Registro';
require __DIR__ . '/includes/header.php';
?>
<section class="tarjeta tarjeta-estrecha">
    <h1>Crear cuenta</h1>

    <?php if (isset($errores['general'])): ?>
        <div class="alerta alerta-error" role="alert"><?= e($errores['general']) ?></div>
    <?php endif; ?>

    <form method="post" action="registro.php" novalidate>
        <?= csrf_campo() ?>

        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" value="<?= e($nombre) ?>" maxlength="100" required>
        <?php if (isset($errores['nombre'])): ?><p class="error"><?= e($errores['nombre']) ?></p><?php endif; ?>

        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" maxlength="150" required>
        <?php if (isset($errores['correo'])): ?><p class="error"><?= e($errores['correo']) ?></p><?php endif; ?>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required>
        <small>Mínimo 8 caracteres, con letras y números.</small>
        <?php if (isset($errores['password'])): ?><p class="error"><?= e($errores['password']) ?></p><?php endif; ?>

        <label for="confirmar">Confirmar contraseña</label>
        <input type="password" id="confirmar" name="confirmar" autocomplete="new-password" required>
        <?php if (isset($errores['confirmar'])): ?><p class="error"><?= e($errores['confirmar']) ?></p><?php endif; ?>

        <button type="submit" class="btn">Registrarme</button>
    </form>

    <p class="enlace-aparte">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
