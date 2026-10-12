<?php
declare(strict_types=1);

function attempt_login(string $email, string $password): bool
{
    global $config;
    // Limite persistente por conta + IP. O arquivo fica fora da pasta pública.
    $key = hash('sha256', $config['db_name'] . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'local') . '|' . strtolower($email));
    $handle = fopen(sys_get_temp_dir() . '/biblioteca-login-' . $key . '.json', 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if ($handle) { fclose($handle); }
        throw new DomainException('O login está temporariamente indisponível. Tente novamente.');
    }
    try {
        $state = json_decode(stream_get_contents($handle), true) ?: ['since' => time(), 'count' => 0];
        if (time() - (int) $state['since'] >= 900) {
            $state = ['since' => time(), 'count' => 0];
        }
        if ((int) $state['count'] >= 8) {
            throw new DomainException('Muitas tentativas de login. Aguarde até 15 minutos e tente novamente.');
        }
        $user = one('SELECT id,senha,status FROM usuarios WHERE email=:email', ['email' => $email]);
        // Hash de comparação para manter um custo semelhante quando a conta não existe.
        $fallback = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';
        $hash = $user['senha'] ?? '';
        $matches = password_verify($password, $hash !== '' ? $hash : $fallback);
        $valid = $user && $hash !== '' && $matches && (int) $user['status'] === 1;
        $state['count'] = $valid ? 0 : (int) $state['count'] + 1;
        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($state));
        fflush($handle);
        if ($valid) {
            if (password_needs_rehash($user['senha'], PASSWORD_DEFAULT)) {
                query('UPDATE usuarios SET senha=:senha WHERE id=:id', ['senha' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int) $user['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return (bool) $valid;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
