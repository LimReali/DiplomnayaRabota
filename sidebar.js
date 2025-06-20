// Получаем элементы DOM по их ID
const sidebar = document.getElementById('sidebar');           // Сайдбар
const toggleBtn = document.getElementById('toggleBtn');       // Кнопка сворачивания/разворачивания сайдбара на десктопе
const mobileMenuBtn = document.getElementById('mobileMenuBtn'); // Кнопка открытия мобильного меню (гамбургер)
const mobileTopbar = document.getElementById('mobileTopbar'); // Верхняя панель на мобильных устройствах
// Обработчик клика по кнопке toggleBtn (десктоп)
// Переключает класс 'collapsed' у сайдбара, сворачивая или разворачивая его
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
});
// Функция для обработки изменения размера окна браузера
function handleResize() {
    if (window.innerWidth <= 768) {
        // Для экранов шириной 768px и меньше (мобильные устройства)
        mobileTopbar.style.display = 'flex';     // Показываем мобильную верхнюю панель
        sidebar.classList.remove('collapsed');   // Убираем свёрнутое состояние сайдбара
        sidebar.classList.remove('open');        // Закрываем сайдбар (по умолчанию скрыт)
    } else {
        // Для экранов больше 768px (десктоп)
        mobileTopbar.style.display = 'none';     // Скрываем мобильную верхнюю панель
        sidebar.classList.remove('open');        // Убираем класс 'open' (на всякий случай)
    }
}
// Добавляем обработчики событий на изменение размера окна и загрузку страницы
// Чтобы сразу применить правильное состояние сайдбара и верхней панели
window.addEventListener('resize', handleResize);
window.addEventListener('load', handleResize);
// Обработчик клика по кнопке мобильного меню (гамбургер)
// Переключает класс 'open' у сайдбара, показывая или скрывая его
mobileMenuBtn.addEventListener('click', () => {
    sidebar.classList.toggle('open');
});
// Обработчик клика по документу для закрытия сайдбара на мобильных устройствах при клике вне его
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        // Если клик был вне сайдбара и кнопки меню
        if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
            sidebar.classList.remove('open'); // Закрываем сайдбар
        }
    }
});
