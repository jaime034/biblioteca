<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';
$config = require __DIR__ . '/../config/config.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'none'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");
header('Cache-Control: no-store');

ini_set('session.use_strict_mode', '1');
session_name('biblioteca_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => rtrim(url(), '/') . '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

try {
    $dsn = 'mysql:host=' . $config['db_host'] . ';port=' . $config['db_port'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
    // Verifica se os dois campos adicionais já foram importados.
    $pdo->query('SELECT perfil FROM usuarios LIMIT 0');
    $pdo->query('SELECT data_devolucao_real FROM emprestimos LIMIT 0');
} catch (PDOException $error) {
    error_log((string) $error);
    http_response_code(503);
    render_error('Vamos conectar o banco?', 'Inicie o MySQL, confira config/config.php e importe database/schema.sql pelo phpMyAdmin. Se você já importou o banco original, execute database/migracao.sql. O LEIA-ME.md explica o passo a passo.');
}

require_once __DIR__ . '/services.php';
