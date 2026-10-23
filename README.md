# Encurtador de links · MVP 2026

Encurtador gratuito em **PHP 8.3 procedural**, sem framework ou orientação a objetos, com **Bootstrap 5** e **MySQL 8.0+/MariaDB 10.6+**. O sistema oferece painel autenticado, aliases personalizados, imagem/título/descrição, identidade visual, redirecionamento HTTP e estatísticas básicas.

## Instalação (Apache / cPanel)

1. Configure o domínio com HTTPS e PHP 8.3, extensões **pdo_mysql**, **mbstring**, **fileinfo** e **session**. Habilite mod_rewrite e AllowOverride para o .htaccess. Banco MySQL 8.0+ ou MariaDB 10.6+ com utf8mb4.
2. Crie um banco e um usuário com permissões somente para o banco da aplicação. Importe database/schema.sql no phpMyAdmin ou no cliente MySQL. **Não importe o SQL antigo** em instalações novas.
3. Copie .env.example para .env; ajuste APP_URL (endereço HTTPS sem barra final), DB_HOST, DB_PORT, DB_NAME, DB_USER e DB_PASS. Não publique .env; configure permissão 600 e, idealmente, mantenha as credenciais fora do diretório público usando variáveis de ambiente do servidor.
4. Certifique-se de que a pasta uploads/ pode ser escrita pelo usuário do PHP, sem permitir execução de scripts. Verifique que os diretórios config/, database/, includes/, scripts/, tests/ e conexao/ não são acessíveis via HTTP.
5. Pelo terminal do servidor, execute: php scripts/create_admin.php admin@dominio.com "Administrador". Informe uma senha com pelo menos 12 caracteres quando solicitado. **Não passe a senha como argumento de linha de comando**.
6. Abra /login.php, entre e crie o primeiro link em /link-form.php. Em /settings.php, personalize nome, descrição, logo e favicon.
7. Teste a rota https://seu-dominio/alias, a página de compartilhamento https://seu-dominio/p/alias, a contagem de acessos, as rotas inexistentes (HTTP 404) e a criação de aliases duplicados.

### Rotas

- /: landing page pública, com acesso ao painel.
- /login.php: autenticação. Cadastro público desativado por padrão para prevenir abuso.
- /dashboard.php: links do usuário, total de acessos e ações.
- /link-form.php: criar/editar link, escolher alias, imagem, título, descrição, status e redirecionamento 301/302.
- /stats.php?id=123: estatísticas diárias e principais origens do link pertencente ao usuário.
- /settings.php: identidade visual (somente administradores).
- /alias: redirecionamento direto, com registro de acesso.
- /p/alias: página opcional de pré-visualização com Open Graph; **o redirecionamento direto não fornece cartões Open Graph a redes sociais**.

### Segurança e privacidade

- Senhas armazenadas com password_hash, sessões HttpOnly/SameSite, rotação de ID após login, CSRF, PDO preparado, validação HTTP(S), restrição de aliases e uploads por MIME.
- Bloqueio de tentativas de login por combinação de IP e e-mail (hash, janela de 15 minutos); sem armazenar IP em claro. Recomenda-se proteção adicional no proxy/CDN contra abuso distribuído.
- Estatísticas armazenam dia e domínio de referência, sem IP ou User-Agent completo. Revise a política de privacidade/LGPD antes da operação pública.
- Imagens ficam em uploads/ com nomes aleatórios. O servidor deve impedir execução de arquivos nesse diretório. A plataforma depende de configuração correta do Apache.
- **ALERTA CRÍTICO:** o protótipo de 2025 continha uma credencial de banco em conexao/db.php, publicamente versionada. A versão atual removeu a credencial do arquivo, mas **ela permanece no histórico Git**. Troque imediatamente a senha no provedor de banco, revogue a credencial anterior e revise acessos. Não basta alterar o código.
- Evite 301 para links cujo destino pode mudar: navegadores e intermediários podem armazenar redirecionamentos permanentes e reduzir a precisão das métricas.

### Testes

Execute php tests/smoke.php e find . -name '*.php' -print0 | xargs -0 -n1 php -l. Testes de integração (login, upload, MySQL, Apache e HTTP) exigem um ambiente configurado e **não** são cobertos pelo smoke test.

### Migração do protótipo 2025

A tabela antiga links_curto não é compatível diretamente com o esquema novo. Antes de importar dados, faça backup, crie o administrador e mapeie url_original → destination_url, link_curto → alias, descricao → title, associando os links ao user_id correto. Verifique duplicidade, caracteres inválidos e aliases reservados. Não apague a tabela antiga sem confirmar a migração.

### Próximas melhorias

Autenticação com recuperação de senha e 2FA; gerenciamento de usuários e permissões; relatórios gráficos/exportação CSV; QR Codes; expiração e limites de cliques; proteção anti-phishing; links com domínio customizado; API com tokens e rate limit; integração de testes HTTP/CI; paginação avançada; política de retenção de métricas e exclusão de uploads órfãos.

## Estrutura

- config/bootstrap.php — configuração, sessão e PDO.
- includes/functions.php — utilitários procedurais, autenticação, CSRF, uploads e validação.
- includes/layout.php — cabeçalho/rodapé Bootstrap.
- database/schema.sql — esquema limpo, sem dados de exemplo.
- scripts/create_admin.php — provisionamento do administrador por CLI.
- assets/ — CSS e JavaScript do layout.

**Sem cobrança, sem dados de demonstração e sem frameworks PHP.**
