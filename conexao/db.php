<?php

    $host = 'localhost';
    $dbname = 'pay_encurta';
    $username = 'pay_encurta';
    $dbpassword = 'Th1@g0t4t1m#2025!!';

    try {
        $pdo = new PDO("mysql:host=$host,dbname=$dbname",$username,$dbpassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch {
        echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
        exit;
    }
?>