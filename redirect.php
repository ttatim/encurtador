<?php
include 'conexao/db.php';

if (!$pdo) {
    die("Erro ao conectar com o banco de dados.");
}

// Sanitiza a entrada do parâmetro 'link'
$linkCurto = filter_input(INPUT_GET, 'link', FILTER_SANITIZE_STRING);

if ($linkCurto) {
    // Busca o link original no banco de dados
    $stmt = $pdo->prepare("SELECT url_original FROM links_curto WHERE link_curto = ?");
    $stmt->execute([$linkCurto]);
    $row = $stmt->fetch();

    if ($row) {
        // Redireciona para a URL original
        header("Location: " . $row['url_original']);
        exit;
    } else {
        // Redireciona para uma página de erro personalizada
        header("Location: /pagina-de-erro.php");
        exit;
    }
} else {
    // Redireciona para uma página de erro em caso de link inválido
    header("Location: /pagina-de-erro.php");
    exit;
}