<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function input(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) && (is_string($source[$key]) || is_int($source[$key])) ? trim((string) $source[$key]) : $default;
}

function url(string $path = ''): string
{
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/.');
    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function query(string $sql, array $params = []): PDOStatement
{
    global $pdo;
    $statement = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $name = is_int($key) ? $key + 1 : ':' . ltrim($key, ':');
        $type = $value === null ? PDO::PARAM_NULL : (is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $statement->bindValue($name, $value, $type);
    }
    $statement->execute();
    return $statement;
}

function one(string $sql, array $params = []): ?array
{
    $row = query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function rows(string $sql, array $params = []): array
{
    return query($sql, $params)->fetchAll();
}

function count_value(string $sql, array $params = []): int
{
    return (int) query($sql, $params)->fetchColumn();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals(csrf_token(), input($_POST, 'csrf'))) {
        http_response_code(419);
        render_error('Formulário expirado', 'Atualize a página e envie o formulário novamente.');
    }
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if (!$loaded) {
        $loaded = true;
        if (isset($_SESSION['user_id'])) {
            $user = one('SELECT id, nome, email, perfil, status FROM usuarios WHERE id = :id', ['id' => (int) $_SESSION['user_id']]);
            if (!$user || (int) $user['status'] !== 1) {
                unset($_SESSION['user_id']);
                $user = null;
            }
        }
    }
    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

function is_staff(): bool
{
    $user = current_user();
    return $user && in_array($user['perfil'], ['admin', 'bibliotecario'], true);
}

function is_admin(): bool
{
    return (current_user()['perfil'] ?? '') === 'admin';
}

function require_staff(): array
{
    $user = require_login();
    if (!is_staff()) {
        http_response_code(403);
        render_error('Acesso restrito', 'Esta ação está disponível para a equipe da biblioteca.');
    }
    return $user;
}

function has_admin(): bool
{
    return count_value("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'") > 0;
}

function render_error(string $title, string $message): void
{
    ?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= e($title) ?> · Biblioteca</title><link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>"></head><body class="error-page"><main class="error-card"><span class="eyebrow">BIBLIOTECA</span><h1><?= e($title) ?></h1><p><?= e($message) ?></p><a class="button" href="<?= e(url('index.php')) ?>">Voltar ao início</a></main></body></html><?php
    exit;
}

function text_length(string $value): int
{
    return preg_match_all('/./us', $value) ?: 0;
}

function require_text(array $data, string $field, string $label, int $max): string
{
    $value = input($data, $field);
    if ($value === '' || text_length($value) > $max) {
        throw new DomainException($label . ' é obrigatório e deve ter até ' . $max . ' caracteres.');
    }
    return $value;
}

function optional_text(array $data, string $field, string $label, int $max): ?string
{
    $value = input($data, $field);
    if (text_length($value) > $max) {
        throw new DomainException($label . ' deve ter até ' . $max . ' caracteres.');
    }
    return $value === '' ? null : $value;
}

function positive_id($value): int
{
    return max(0, (int) filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]));
}

function valid_cpf(string $cpf): bool
{
    if (!preg_match('/^\d{11}$/', $cpf) || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($size = 9; $size <= 10; $size++) {
        $sum = 0;
        for ($i = 0; $i < $size; $i++) {
            $sum += (int) $cpf[$i] * ($size + 1 - $i);
        }
        $digit = ($sum * 10) % 11;
        if (($digit === 10 ? 0 : $digit) !== (int) $cpf[$size]) {
            return false;
        }
    }
    return true;
}

function validate_password(string $password): void
{
    if (text_length($password) < 8 || strlen($password) > 72 || strpos($password, "\0") !== false) {
        throw new DomainException('Use uma senha com pelo menos 8 caracteres e até 72 bytes.');
    }
}

function valid_date(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function fmt_date(?string $value): string
{
    return $value ? (new DateTimeImmutable($value))->format('d/m/Y') : '—';
}

function fmt_number($value): string
{
    return number_format((float) $value, 0, ',', '.');
}

function money($value): string
{
    return 'R$ ' . number_format((float) $value, 2, ',', '.');
}

function role_label(string $role): string
{
    return ['admin' => 'Administrador', 'bibliotecario' => 'Bibliotecário', 'leitor' => 'Leitor'][$role] ?? 'Leitor';
}

function loan_state(array $loan): array
{
    if ((int) $loan['status'] === 1) {
        return ['Devolvido', 'muted'];
    }
    return $loan['data_devolucao_prevista'] < date('Y-m-d') ? ['Atrasado', 'danger'] : ['Ativo', 'success'];
}

function badge(string $text, string $kind): string
{
    return '<span class="badge badge-' . e($kind) . '">' . e($text) . '</span>';
}

function initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name));
    $letters = '';
    foreach (array_slice($words ?: [], 0, 2) as $word) {
        preg_match('/^./us', $word, $match);
        $letters .= $match[0] ?? '';
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
}

function page_data(int $total, int $perPage = 12): array
{
    $last = max(1, (int) ceil($total / $perPage));
    $page = min($last, max(1, positive_id(input($_GET, 'pagina', '1'))));
    return ['total' => $total, 'page' => $page, 'last' => $last, 'limit' => $perPage, 'offset' => ($page - 1) * $perPage];
}

function pagination(array $data, string $path): void
{
    if ($data['last'] <= 1) {
        return;
    }
    $params = array_filter($_GET, 'is_string');
    unset($params['pagina']);
    ?><nav class="pagination" aria-label="Paginação"><span>Página <?= (int) $data['page'] ?> de <?= (int) $data['last'] ?></span><div><?php
    foreach (['Anterior' => -1, 'Próxima' => 1] as $label => $delta) {
        $target = $data['page'] + $delta;
        if ($target >= 1 && $target <= $data['last']) {
            $params['pagina'] = (string) $target;
            ?><a class="button button-secondary button-small" href="<?= e(url($path . '?' . http_build_query($params))) ?>"><?= e($label) ?></a><?php
        }
    }
    ?></div></nav><?php
}

function friendly_error(Throwable $error): string
{
    if ($error instanceof DomainException) {
        return $error->getMessage();
    }
    error_log((string) $error);
    if ($error instanceof PDOException && (string) $error->getCode() === '23000') {
        return 'Este registro já existe ou está associado a outros dados. Confira CPF, e-mail e ISBN.';
    }
    return 'Não foi possível concluir a operação. Tente novamente.';
}

function icon(string $name): string
{
    $paths = [
        'book' => '<path d="M12 6c-2-2-6-2-9-1v14c3-1 7-1 9 1 2-2 6-2 9-1V5c-3-1-7-1-9 1Z"/><path d="M12 6v14"/>',
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 5a3 3 0 0 1 0 6M21 21v-3a6 6 0 0 0-3-5"/>',
        'loan' => '<path d="M4 7h15m-4-4 4 4-4 4M20 17H5m4-4-4 4 4 4"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M10 4H4v16h6M9 12h12m-4-4 4 4-4 4"/>',
        'arrow' => '<path d="M4 12h16m-6-6 6 6-6 6"/>',
        'check' => '<path d="m5 12 4 4 10-10"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'coin' => '<circle cx="12" cy="12" r="9"/><path d="M12 6v12m3-9c-1-2-6-2-6 1s6 1 6 4-5 3-6 1"/>',
        'edit' => '<path d="m14 4 6 6M4 20l5-1L21 7a2 2 0 0 0-4-4L5 15l-1 5Z"/>',
        'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/>',
    ];
    return '<svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['book']) . '</svg>';
}

function book_cover(array $book, bool $small = false): string
{
    $color = (int) $book['id'] % 6;
    return '<div class="book-cover cover-' . $color . ($small ? ' cover-small' : '') . '" aria-hidden="true"><span class="cover-mark">B</span><span class="cover-title">' . e($book['titulo']) . '</span><span class="cover-line"></span></div>';
}
