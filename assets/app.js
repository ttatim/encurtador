document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-copy]');
  if (!button) return;
  const text = button.dataset.copy || '';
  try {
    await navigator.clipboard.writeText(text);
    const original = button.textContent;
    button.textContent = 'Copiado!';
    window.setTimeout(() => { button.textContent = original; }, 1800);
  } catch {
    window.prompt('Copie o endereço:', text);
  }
});
