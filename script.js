(function(){
  // aplica tema salvo
  const applyTheme = () => {
    const theme = localStorage.getItem('theme') || 'light';
    document.body.classList.toggle('dark', theme === 'dark');
  };
  applyTheme();

  // toggle ao clicar
  window.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('toggle-dark');
    if (btn) {
      btn.addEventListener('click', () => {
        const dark = !document.body.classList.contains('dark');
        document.body.classList.toggle('dark', dark);
        localStorage.setItem('theme', dark ? 'dark' : 'light');
      });
    }
  });
})();