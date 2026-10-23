<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/layout.php';
render_header('Encurte, compartilhe e acompanhe');
?>
<main>
  <section class="hero"><div class="container"><div class="row align-items-center g-5">
    <div class="col-lg-7">
      <span class="eyebrow mb-4">✦ Simples, rápido e gratuito</span>
      <h1>Seus links.<br><span class="gradient-text">Mais impacto.</span></h1>
      <p class="lead mt-4 fs-5">Transforme endereços longos em links memoráveis, personalizados e fáceis de compartilhar. Tudo em um painel que você controla.</p>
      <div class="d-flex gap-3 mt-4 flex-wrap"><a href="<?= logged_in() ? '/link-form.php' : '/login.php' ?>" class="btn btn-primary btn-lg px-4">Criar meu link →</a><a href="#recursos" class="btn btn-outline-secondary btn-lg px-4">Conhecer recursos</a></div>
      <p class="text-secondary small mt-4 mb-0">✓ URLs personalizadas &nbsp; ✓ Estatísticas &nbsp; ✓ Sem mensalidade</p>
    </div>
    <div class="col-lg-5"><div class="hero-card">
      <div class="d-flex align-items-center gap-2 mb-4"><span class="feature-icon">↗</span><div><strong>Encurte em segundos</strong><div class="small text-secondary">Links profissionais, sem complicação</div></div></div>
      <label class="form-label fw-semibold" for="example-url">Seu link original</label>
      <input id="example-url" class="form-control mb-3" value="https://exemplo.com.br/uma-pagina-com-endereco-muito-longo" readonly>
      <label class="form-label fw-semibold" for="example-short">Seu novo link</label>
      <div class="input-group mb-4"><span class="input-group-text bg-light">↗</span><input id="example-short" class="form-control" value="<?= e(app_url('minha-marca')) ?>" readonly></div>
      <a href="<?= logged_in() ? '/link-form.php' : '/login.php' ?>" class="btn btn-primary w-100 py-3">Começar gratuitamente</a>
      <p class="small text-secondary text-center mb-0 mt-3">Gerenciamento disponível após login.</p>
    </div></div>
  </div></div></section>
  <section class="section-pad" id="recursos"><div class="container"><div class="text-center mb-5"><span class="eyebrow">FEITO PARA VOCÊ</span><h2 class="fw-bold mt-3">Tudo o que seus links precisam</h2><p class="text-secondary">Mais controle, clareza e praticidade para cada campanha.</p></div>
    <div class="row g-4"><div class="col-md-4"><div class="p-4 h-100"><div class="feature-icon mb-3">✎</div><h3 class="h5 fw-bold">Personalize</h3><p class="text-secondary">Escolha um nome exclusivo para seus links e configure informações de compartilhamento.</p></div></div><div class="col-md-4"><div class="p-4 h-100"><div class="feature-icon mb-3">◫</div><h3 class="h5 fw-bold">Organize</h3><p class="text-secondary">Ative, pause e edite seus endereços em um painel intuitivo.</p></div></div><div class="col-md-4"><div class="p-4 h-100"><div class="feature-icon mb-3">↗</div><h3 class="h5 fw-bold">Acompanhe</h3><p class="text-secondary">Visualize acessos e descubra quais links despertam mais interesse.</p></div></div></div>
  </div></section>
</main>
<?php render_footer(); ?>
