<?php
// Запускаем сессию для управления авторизацией и состоянием пользователя
session_start();
// Подключаем файл с функциями для работы с базой данных
require_once 'database.php';
// Проверяем, авторизован ли пользователь (администратор)
if (!isset($_SESSION['user_id'])) {
    // Если нет, перенаправляем на страницу входа
    header('Location: login.php');
    exit();
}
// Получаем соединение с базой данных
$conn = getDbConnection();
// Инициализируем массив для хранения преподавателей
$teachers = [];
// SQL-запрос для получения списка преподавателей, сортируем по полному имени
$sql = "SELECT id, full_name FROM teachers ORDER BY full_name";
// Выполняем запрос
$result = $conn->query($sql);
// Если запрос выполнен успешно
if ($result) {
    // Перебираем все строки результата
    while ($row = $result->fetch_assoc()) {
        // Добавляем каждую запись в массив $teachers
        $teachers[] = $row;
    }
} else {
    // Если произошла ошибка, выводим сообщение и прекращаем выполнение скрипта
    die("Ошибка запроса преподавателей: " . $conn->error);
}
// Закрываем соединение с базой данных
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Выбор преподавателя</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Сброс отступов и базовые стили */
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: #f8f5f7; /* светлый фон страницы */
            color: rgb(32, 74, 123); /* основной цвет текста — синий */
        }
        /* Стили для шапки сайта */
        header {
            background-color: #34495e; /* темно-синий фон */
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between; /* распределяем элементы по краям */
            align-items: center;
            box-sizing: border-box;
            margin: 0;
        }
        /* Заголовок в шапке */
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        /* Стили для ссылок и кнопок в навигации */
        nav a,
        nav form button {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: bold;
            font-size: 16px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
        }
        /* Эффект при наведении на ссылки и кнопки */
        nav form button:hover,
        nav a:hover {
            text-decoration: underline;
        }
        /* Основной контент страницы */
        main {
            max-width: 600px; /* ограничиваем ширину */
            margin: 40px auto; /* центрируем по горизонтали и отступ сверху */
            padding: 0 20px 40px; /* внутренние отступы */
        }
        /* Заголовок страницы */
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: rgb(32, 74, 123);
        }
        /* Стили для поля поиска */
        #searchTeacher {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 1.5px solid rgb(20, 97, 199); /* синий бордер */
            box-sizing: border-box;
            margin-bottom: 20px;
            transition: border-color 0.3s ease;
        }
        /* Стили при фокусе на поле поиска */
        #searchTeacher:focus {
            border-color: rgb(56, 54, 175); /* насыщенный синий при фокусе */
            outline: none;
            box-shadow: 0 0 5px rgba(56, 54, 175, 0.5);
        }
        /* Стили для кнопок-преподавателей */
        .teacher-btn {
            display: block;
            width: 100%;
            margin-bottom: 16px;
            padding: 14px 0;
            border: 2px solid rgb(20, 97, 199); /* синий бордер */
            border-radius: 12px;
            background: linear-gradient(135deg, rgb(56, 54, 175), rgb(182, 185, 236)); /* синий градиент */
            color: rgb(251, 252, 255); /* светлый текст */
            font-size: 1.4rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(20, 97, 199, 0.15);
            transition: background 0.3s ease, color 0.3s ease;
            user-select: none; /* запрет выделения текста */
        }
        /* Стили при наведении и фокусе на кнопках */
        .teacher-btn:hover,
        .teacher-btn:focus {
            background: linear-gradient(135deg, rgb(94, 117, 216), rgb(32, 74, 123)); /* тёмно-синий градиент при наведении */
            color: #fff;
            box-shadow: 0 6px 12px rgba(20, 97, 199, 0.4);
            outline: none;
        }
        /* Контейнер для пагинации */
        .pagination {
            text-align: center;
            margin-top: 20px;
        }
        /* Кнопки пагинации */
        .pagination button {
            margin: 0 4px;
            padding: 8px 14px;
            border: 1.5px solid rgb(20, 97, 199); /* синий бордер */
            border-radius: 6px;
            background: #fff;
            color: rgb(32, 74, 123); /* синий текст */
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        /* Активная кнопка пагинации */
        .pagination button.active {
            background: rgb(20, 97, 199); /* насыщенный синий фон */
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Шапка сайта с навигацией -->
    <header role="banner">
        <h1>Панель администратора</h1>
        <nav role="navigation" aria-label="Главное меню администратора">
            <a href="add_lesson.php">Добавить занятие</a>
            <a href="groups_admin.php">Управление группами</a>
            <a href="teachers_admin.php">Управление преподавателями</a>
            <a href="db_history.php">История изменений базы</a>
            <a href="admin_rooms_select.php">Управление кабинетами</a>
            <form id="logoutForm" action="logout.php" method="post" style="display:inline;">
                <button type="button" aria-label="Выйти из системы" title="Выйти из системы"
                    onclick="document.getElementById('logoutForm').submit();">
                    Выйти
                </button>
            </form>
        </nav>
    </header>
    <main role="main">
        <h2>Выберите преподавателя</h2>
        <!-- Поле для поиска преподавателей -->
        <input type="text" id="searchTeacher" placeholder="Поиск преподавателя..." aria-label="Поиск преподавателя" />
        <!-- Контейнер для списка преподавателей -->
        <div id="teacherList" class="teacher-list" tabindex="0" aria-live="polite" aria-atomic="true"></div>
        <!-- Контейнер для пагинации -->
        <div class="pagination" id="pagination"></div>
    </main>
    <script>
        // Массив преподавателей, переданный из PHP
        const teachers = <?= json_encode($teachers) ?>;
        const teachersPerPage = 10; // Количество преподавателей на странице
        let currentPage = 1; // Текущая страница пагинации
        let filteredTeachers = teachers.slice(); // Копия массива для фильтрации
        // Получаем элементы DOM для списка и пагинации
        const teacherList = document.getElementById('teacherList');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchTeacher');
        // Функция отрисовки преподавателей на текущей странице
        function renderTeachers(page) {
            teacherList.innerHTML = ''; // Очищаем список
            const start = (page - 1) * teachersPerPage;
            const end = start + teachersPerPage;
            const pageTeachers = filteredTeachers.slice(start, end);
            // Если нет преподавателей для отображения — показываем сообщение
            if (pageTeachers.length === 0) {
                teacherList.innerHTML = '<p style="text-align:center; color:#a83250; font-weight:600;">Преподаватели не найдены</p>';
                pagination.innerHTML = '';
                return;
            }
            // Создаем кнопки-ссылки для каждого преподавателя
            pageTeachers.forEach(teacher => {
                const a = document.createElement('a');
                a.className = 'teacher-btn';
                a.href = 'teachers_admin.php?teacher_id=' + encodeURIComponent(teacher.id);
                a.textContent = teacher.full_name;
                teacherList.appendChild(a);
            });
            // Отрисовываем пагинацию
            renderPagination(page);
        }
        // Функция отрисовки кнопок пагинации
        function renderPagination(page) {
            pagination.innerHTML = ''; // Очищаем пагинацию
            const totalPages = Math.ceil(filteredTeachers.length / teachersPerPage);
            if (totalPages <= 1) return; // Если одна страница — пагинация не нужна
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === page) btn.classList.add('active'); // Активная страница выделена
                btn.onclick = () => {
                    currentPage = i;
                    renderTeachers(currentPage);
                    window.scrollTo({ top: 0, behavior: 'smooth' }); // Плавный скролл вверх
                };
                pagination.appendChild(btn);
            }
        }
        // Обработчик ввода в поле поиска
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            // Фильтруем преподавателей по полному имени
            filteredTeachers = teachers.filter(teacher =>
                teacher.full_name.toLowerCase().includes(query)
            );
            currentPage = 1; // Сбрасываем на первую страницу после фильтрации
            renderTeachers(currentPage);
        });
        // Инициализация — отрисовываем первую страницу при загрузке
        renderTeachers(currentPage);
    </script>
</body>
</html>
