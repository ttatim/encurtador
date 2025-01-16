<?php

    $host = 'localhost';
    $dbname = 'seu_banco_de_dados';
    $username = 'nome_de_usuario_do_banco';
    $dbpassword = 'senha_do_usuario_do_banco_de_dados';

    try {
        $pdo = new PDO("mysql:host=$host,dbname=$dbname",$username,$dbpassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch {
        echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
        exit;
    }
?>