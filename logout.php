<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

cerrar_sesion();

// Nueva sesión solo para poder mostrar el mensaje de despedida
session_start();
flash('exito', 'Cerraste sesión correctamente.');

header('Location: login.php');
exit;
