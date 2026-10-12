<?php
// Configuração para XAMPP. Você também pode usar variáveis de ambiente.
return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: 'biblioteca',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    'timezone' => 'America/Sao_Paulo',
    'prazo_emprestimo_dias' => 14,
    'multa_por_dia_centavos' => 100,
];
