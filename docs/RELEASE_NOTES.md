# Informações da versão — 23/10/2026

## Entrega 0.1.0 (MVP inicial)

- Substituição do protótipo 2025 por interface Bootstrap 5 responsiva.
- Login de administradores provisionados via CLI, painel de links e identidade visual.
- Criação, edição, exclusão e pausa de URLs curtas, aliases personalizados e geração aleatória.
- Upload controlado de imagem por link, logo e favicon do site.
- Redirecionamentos 301/302, contagem de acessos, estatísticas diárias e domínio de referência.
- Pré-visualização Open Graph separada em /p/alias.
- PDO preparado, CSRF, sessões protegidas, throttling de login e remoção de senha do código atual.
- Banco sem registros de demonstração; documentação de implantação e pipeline de lint/smoke.

## Atenções críticas

1. **Rotacione a senha de banco que foi publicada no histórico Git em 2025.** O histórico antigo ainda contém o segredo.
2. A versão requer criação de banco, importação do schema e configuração .env no servidor. Não houve deploy em hospedagem de produção.
3. O código passou por lint PHP e testes unitários simples locais, mas não por testes end-to-end com MySQL e Apache.
4. Não foi feita migração automática da tabela antiga links_curto.
5. A página de preview com metadados Open Graph é /p/alias; o link curto padrão redireciona diretamente.
6. O uso de CDN Bootstrap requer conectividade do navegador. Os redirecionamentos continuam funcionando sem a CDN.

## Servidor

Apache 2.4+ com mod_rewrite, PHP 8.3 (pdo_mysql, mbstring, fileinfo, session), MySQL 8+ ou MariaDB 10.6+, HTTPS e diretório uploads gravável. Não requer Node.js, Next.js, TypeScript ou Composer.
