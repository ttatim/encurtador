<?php
include 'conexao/db.php';

// Entradas para Recaptcha
$secret = 'CHAVE_RECAPTCHA';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $url_original = trim($_POST['url_original']);
    $descricao = trim($_POST['descricao']);
    $recaptchaResponse = $_POST['g-recaptcha-response'];

    // Verifica se o recaptcha foi preenchido corretamente
    $recaptchaVerifyUrl = "https://www.google.com/recaptcha/api/siteverify";
    $recaptchaResponseVerify = file_get_contents($recaptchaVerifyUrl . "?secret=" . $secret . "&response=" . $recaptchaResponse);
    $recaptchaResult = json_decode($recaptchaResponseVerify);

    if ($recaptchaResult->success) {
        $linkCurto = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($descricao));

        // Verifica se o link já existe
        $stmt = $pdo->prepare("SELECT * FROM links_curto WHERE link_curto = ?");
        $stmt->execute([$linkCurto]);
        $existingLink = $stmt->fetch();

        // Caso já exista um link, gerar um link único
        if ($existingLink) {
            $linkCurto .= '-' . uniqid();
        }

        // Inserir no banco de dados
        $stmt = $pdo->prepare("INSERT INTO links_curto (url_original, descricao, link_curto) VALUES (?, ?, ?)");
        $stmt->execute([$url_original, $descricao, $linkCurto]);

        // Exibir link gerado
        $linkCurtoUrl = "https://sitepay.com.br/$linkCurto";
    } else {
        $errorMessage = "Por favor, confirme que você não é um robô";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dica Shop - Encurtador de URL</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
<header>
    <img src="URL_DA_LOGO" alt="Logo DICASHOP">
</header>
<div class="container">
    <div class="form-container">
        <h1>Encurtador de URL</h1>
        <form action="" method="POST">
            <label for="url_original">URL Original:</label>
            <input type="text" id="url_original" name="url_original" required><br>

            <label for="descricao">Insira uma informação de referência para o link:</label>
            <input type="text" id="descricao" name="descricao" required><br>

            <!-- RECAPTCHA -->
            <div class="g-recaptcha" data-sitekey="SUA_CHAVE_DO_SITE"></div>
            <br>
            <button type="submit">Encurtar URL</button>
        </form>
        <?php if (isset($linkCurtoUrl)): ?>
            <div class="link-container">
                <p>Link curto gerado:</p>
                <input type="text" value="<?php echo $linkCurtoUrl; ?>" id="short-link" readonly>
                <button class="copy-btn" onclick="copyLink()">Copiar</button>
            </div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="message"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
    </div>
</div>
<script>
    function copyLink() {
        var copyText = document.getElementById("short-link");
        copyText.select();
        document.execCommand("copy");
        alert("Link Copiado: " + copyText.value);
    }
</script>
</body>
</html>