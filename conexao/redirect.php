<?php

    include 'conexao/db.php';

    if (isset($_GET['link']))
        {
            $linkCurto = $_GET['link'];

            // Busca o link original no banco de dados

            $stmt = $pdo->prepare("SELECT * FROM links_curto WHERE link_curto = ?");
            $stmt->execute([$linkCurto]);
            $row = $stmt->fetch();

            if ($row)
                {
                    // Redireciona para url original

                    header("Location: " . $row['url_original']);
                    exit;
                } else {
                    echo "Link não encontrado";

                }
        } else {
            echo "Link Inválido";
        }
?>