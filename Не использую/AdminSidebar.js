// AdminSidebar.js
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
  
    if (!sidebar || !toggleBtn) return;
  
    // Функция переключения класса collapsed
    function toggleSidebar() {
      sidebar.classList.toggle('collapsed');
    }
  
    // Обработчик кнопки сворачивания
    toggleBtn.addEventListener('click', toggleSidebar);
  
    // Автоматическое сворачивание при загрузке, если ширина окна < 768px
    function checkWindowSize() {
      if (window.innerWidth < 768) {
        sidebar.classList.add('collapsed');
      } else {
        sidebar.classList.remove('collapsed');
      }
    }
  
    // Проверяем при загрузке страницы
    checkWindowSize();
  
    // Проверяем при изменении размера окна
    window.addEventListener('resize', checkWindowSize);
  });
  