<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once dirname(__DIR__) . '/config/bootstrap.php';
$assert = static function (bool $condition, string $message): void {
    if (!$condition) { fwrite(STDERR, "FALHOU: $message\n"); exit(1); }
};
$assert(validate_destination('https://example.com/path?a=1') !== null, 'URL HTTPS');
$assert(validate_destination('http://example.com') !== null, 'URL HTTP');
$assert(validate_destination('javascript:alert(1)') === null, 'bloquear JavaScript');
$assert(validate_destination('https://user:pass@example.com') === null, 'bloquear credenciais na URL');
$assert(validate_destination("https://example.com/\r\nHeader:evil") === null, 'bloquear CRLF');
$assert(validate_destination('file:///etc/passwd') === null, 'bloquear file');
$assert(validate_alias('oferta-2026'), 'alias válido');
$assert(!validate_alias('admin'), 'alias reservado');
$assert(!validate_alias('a'), 'alias curto demais');
$assert(!validate_alias('a/b'), 'alias com barra');
$assert(strlen(new_alias()) === 10, 'alias aleatório');
$assert(e('<script>') === '&lt;script&gt;', 'escape HTML');
$assert(valid_user_email('editor@example.com'), 'e-mail válido');
$assert(!valid_user_email('invalido'), 'e-mail inválido');
$assert(valid_user_password(str_repeat('a', 12)), 'senha com 12 caracteres');
$assert(!valid_user_password('curta'), 'senha curta');
$assert(!valid_user_password(str_repeat('a', 201)), 'senha longa');
fwrite(STDOUT, "OK: 17 verificações de funções puras.\n");
