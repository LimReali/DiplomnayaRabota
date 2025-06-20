<?php
// Запускаем сессию для управления авторизацией и состоянием пользователя
session_start();
// Подключаем файл с функциями для работы с базой данных
require_once 'database.php';
// Инициализируем переменную для хранения возможных ошибок
$error = '';
// Создаем пустой массив для групп
$groups = [];
// Получаем соединение с базой данных
$conn = getDbConnection();
// Выполняем SQL-запрос для получения всех групп, сортируя по имени
$result = $conn->query("SELECT id, name FROM `groups` ORDER BY name+0 ASC, name ASC");
// Если запрос успешен, перебираем все строки результата
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Добавляем каждую группу в массив $groups
        $groups[] = $row;
    }
} else {
    // Если произошла ошибка запроса, сохраняем сообщение об ошибке
    $error = "Ошибка загрузки групп: " . $conn->error;
}
// Закрываем соединение с базой данных
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Выбор группы</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Сброс отступов и базовые стили */
        html, body {
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
        /* Стили для поискового поля */
        #searchGroup {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        /* Контейнер для списка групп */
        .group-list {
            max-width: 600px;
            width: 90%;
            margin: 0 auto;
            padding: 0 10px;
            box-sizing: border-box;
        }
        /* Стили для каждой кнопки-группы */
        .group-btn {
            display: block;
            width: 100%;
            margin-bottom: 16px;
            padding: 14px 0;
            font-size: 1.4rem;
            border: 2px solid rgb(20, 97, 199); /* синий бордер */
            border-radius: 12px;
            background: linear-gradient(135deg, rgb(56, 54, 175), rgb(182, 185, 236)); /* синий градиент */
            color: rgb(251, 252, 255); /* светлый текст */
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(168, 50, 80, 0.15);
            transition: background 0.3s ease, color 0.3s ease;
            user-select: none; /* запрет выделения текста */
        }
        /* Стили при наведении и фокусе */
        .group-btn:hover,
        .group-btn:focus {
            background: linear-gradient(135deg, #a83250, #7b203a); /* багровый градиент */
            color: #fff;
            box-shadow: 0 6px 12px rgba(168, 50, 80, 0.4);
            outline: none;
        }
        /* Стили для отображения ошибок */
        .error {
            color: #e74c3c;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
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
            border: 1.5px solid rgb(20, 97, 199);
            border-radius: 6px;
            background: #fff;
            color: rgb(32, 74, 123);
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        /* Активная кнопка пагинации */
        .pagination button.active {
            background: rgb(56, 54, 175);
            color: #fff;
        }
        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            header {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px 20px;
            }
            nav a,
            nav form button {
                margin-left: 0;
                margin-top: 10px;
                font-size: 14px;
            }
            .group-list {
                max-width: 100%;
                padding: 0;
            }
            .group-btn {
                font-size: 1.2rem;
                padding: 12px 0;
            }
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
        <h2>Выберите группу для редактирования</h2>
        <!-- Вывод ошибки, если есть -->
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <!-- Поле поиска по группам -->
        <input type="text" id="searchGroup" placeholder="Поиск группы..." aria-label="Поиск группы" />
        <!-- Контейнер для списка групп -->
        <div class="group-list" id="groupList" role="list" tabindex="0" aria-live="polite" aria-atomic="true">
            <!-- Кнопки групп будут добавлены JS -->
        </div>
        <!-- Контейнер для пагинации -->
        <div class="pagination" id="pagination"></div>
    </main>
    <script>
        // Массив групп из PHP
        const groups = <?= json_encode($groups) ?>;
        const groupsPerPage = 10; // Кол-во групп на страницу
        let currentPage = 1; // Текущая страница
        let filteredGroups = groups.slice(); // Копия массива для фильтрации
        const groupList = document.getElementById('groupList');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchGroup');
        // Функция отрисовки групп на странице
        function renderGroups(page) {
            groupList.innerHTML = ''; // Очищаем список
            const start = (page - 1) * groupsPerPage;
            const end = start + groupsPerPage;
            const pageGroups = filteredGroups.slice(start, end);
            // Если нет групп для отображения — выводим сообщение
            if (pageGroups.length === 0) {
                groupList.innerHTML = '<p style="text-align:center; color:#a83250; font-weight:600;">Группы не найдены</p>';
                pagination.innerHTML = '';
                return;
            }
            // Создаем кнопки-ссылки для каждой группы
            pageGroups.forEach(group => {
                const a = document.createElement('a');
                a.className = 'group-btn';
                a.href = 'groups_admin.php?group_id=' + encodeURIComponent(group.id);
                a.textContent = group.name;
                a.setAttribute('role', 'listitem');
                groupList.appendChild(a);
            });
            // Отрисовываем пагинацию
            renderPagination(page);
        }
        // Функция отрисовки пагинации
        function renderPagination(page) {
            pagination.innerHTML = ''; // Очищаем пагинацию
            const totalPages = Math.ceil(filteredGroups.length / groupsPerPage);
            if (totalPages <= 1) return; // Если одна страница — пагинация не нужна
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === page) btn.classList.add('active');
                btn.onclick = () => {
                    currentPage = i;
                    renderGroups(currentPage);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                };
                pagination.appendChild(btn);
            }
        }
        // Обработчик ввода в поле поиска
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            // Фильтруем группы по названию
            filteredGroups = groups.filter(group =>
                group.name.toLowerCase().includes(query)
            );
            currentPage = 1;
            renderGroups(currentPage);
        });
        // Инициализация — отрисовываем первую страницу
        renderGroups(currentPage);
    </script>
</body>
</html>
