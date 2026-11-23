<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

redirigir_si_autenticado();

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valido()) {
        $error = 'La solicitud no es válida. Recarga la página e inténtalo de nuevo.';
    } else {
        $correo   = trim((string) ($_POST['correo'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($correo === '' || $password === '') {
            $error = 'Ingresa tu correo electrónico y tu contraseña.';
        } else {
            $pdo = obtener_conexion();
            $consulta = $pdo->prepare('SELECT id, nombre, correo, password FROM usuarios WHERE correo = ? LIMIT 1');
            $consulta->execute([$correo]);
            $usuario = $consulta->fetch();

            // Hash ficticio para que el tiempo de respuesta sea similar exista o no el correo
            $hash = $usuario['password'] ?? '$2y$10$usesomesillystringforsalt.abcdefghijklmnopqrstuv12345';

            if (password_verify($password, $hash) && $usuario) {
                // Actualiza el hash si el algoritmo por defecto de PHP cambió
                if (password_needs_rehash($usuario['password'], PASSWORD_DEFAULT)) {
                    $pdo->prepare('UPDATE usuarios SET password = ? WHERE id = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $usuario['id']]);
                }
                iniciar_sesion_usuario($usuario);
                header('Location: panel.php');
                exit;
            }

            // Mensaje genérico: no revela si el correo existe o no
            $error = 'Correo electrónico o contraseña incorrectos.';
        }
    }
}

$titulo = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<section class="tarjeta tarjeta-estrecha">
    <h1>Iniciar sesión</h1>

    <?php if ($error !== ''): ?>
        <div class="alerta alerta-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" novalidate>
        <?= csrf_campo() ?>

        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo" name="correo" value="<?= e($correo) ?>" maxlength="150" required autofocus>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>

        <button type="submit" class="btn">Entrar</button>
    </form>

    <p class="enlace-aparte">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
