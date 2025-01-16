<?php

    include 'conexao/db.php';

    // Entradas para Recaptcha

    $secret = 'CHAVE_RECAPTCHA';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' )
        {
            $url_original = trim($_POST['url_original']);
            $descricaco = trim($_POST['descricao']);
            $recaptchaResponse = $_POST['g-recaptcha-response'];

            // Verifica se o recaptcha foi preenchido corretamente

            $recaptchaVerifyUrl = "https://www.google.com/recaptcha/api/siteverify";
            $recaptchaResponseVerify = file_get_contents($recaptchaVerifyUrl . "?secret =" . $secret . "&response =" . $recaptchaResponse);
            $recaptchaResult = json_decode($recaptchaResponseVerify);

            if ($recaptchaResult->success)
                {
                    $linkCurto = preg_replace ('/[^a-zA-Z0-9]/', '-', strolover($descricao));

                    // Verifica se o link já existe

                    $stmt = $pdo->prepare("SELECT * FROM links_curto = ?");
                    $stmt->execute([$linkCurto]);
                    $existingLink = $stmt->fetch();

                    // Caso já exista um link, gerar um link único

                    if ($existingLink)
                        {
                            $linkCurto .= '-' . uniqid();
                        }

                    // Inserir no banco de dados

                    $stmt = $pdo->prepare("INSERT INTO links_curto (url_original, descricao, link_curto) VALUES (?, ?, ?)");
                    $stmt->execute([$url_original, $descricao, $linkCurto]);

                    // Exibir link gerado

                    $linkCurtoUrl = "https://dicashop.online/$linkCurto";

                } else {
                    $errorMessage = "Por favor, confirme que você não é um robô";
                }
        }
?>

<!DOCTYPE html>
        <html lang = "pt-br">
            <head>
                <meta charset = "UTF-8" >
                <meta name = "viewport" content = "width=device-width, initial-scale=1.0">
                <title>Dica Shop - Encurtador de URL</title>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 0;
                        padding: 0;
                    }

                    header {
                        background: linear-gradient(to right, #003366, #1a5276);
                        padding: 20px;
                        text-align: left;
                    }

                    header img {
                        width: 250px;
                        height: 180px;
                        display: inline-block;
                    }

                    .container {
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 80vh;
                        background-color: #f0f0f0;
                        padding: 20px;
                    }

                    .form-container {
                        background: $ffffff;
                        padding: 20px;
                        border-radius: 8px;
                        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                        width: 100%;
                        max-width: 600px;
                    }

                    h1 {
                        text-align: center;

                    }

                    label {
                        font-size: 14px;
                        display: block;
                        margin-bottom: 5px;
                    }

                    input[type = "text"] {
                        width: 100%;
                        padding: 10px;
                        margin-bottom: 15px;
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        font-size: 14px;
                    }

                    button {
                        background-color: #0056b3;
                        color: white;
                        padding: 10px 15px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 16px;
                    }

                    button:hover {
                        background-color: #003366;
                    }

                    .copy-btn{
                        background-color: #28a745;
                        color: white;
                        border-radius: 4px;
                        padding: 10px 15px;
                        cursor: pointer;
                        font-size: 16px;
                        margin-left: 10px;
                    }

                    .copy-btn:hover{
                        background-color: #218838;

                    }

                    .message {
                        color: red;
                        text-align: center;
                        margin-top: 10px;

                    }
                </style>
            </head>
            <body>
                <header>
                    <img src="URL_DA_LOGO" alt="Logo DICASHOP">
                </header>

                <div class "container">
                    <div class="form-container">
                        <h1>Encurtador de URL</h1>
                        <form action = "" method = "POST">
                            <label for="url_original">URL Original:</label>
                            <iput type="text" id="url_original" name="url_original" required><br>

                            <label for="descricao">Insira uma informação de referencia para o link:</label>
                            <input  type="text" id="descricao" name="descricao" required><br>

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
            <div>
                <script>
                    function copyLink()
                        {
                            var copyText = document.getElementById("short-link");
                            copyText.select();
                            copyText.setSelectionRange(0, 99999);

                            // Copia o texto para area de transferencia

                            document.execCommand("copy");

                            // Alerta que o link foi copiado

                            alert("Link Copiado: " + copyText.value);
                        }
                </script>
            </body>
        </html>


