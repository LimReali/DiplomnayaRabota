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
// Инициализируем массив для хранения кабинетов
$rooms = [];
// SQL-запрос для получения списка кабинетов с номерами и корпусами, сортируем по номеру и корпусу
$sql = "SELECT id, number, building FROM rooms ORDER BY number, building";
// Выполняем запрос
$result = $conn->query($sql);
// Если запрос выполнен успешно
if ($result) {
    // Перебираем все строки результата
    while ($row = $result->fetch_assoc()) {
        // Добавляем каждую запись в массив $rooms
        $rooms[] = $row;
    }
} else {
    // Если произошла ошибка, выводим сообщение и прекращаем выполнение скрипта
    die("Ошибка запроса кабинетов: " . $conn->error);
}
// Закрываем соединение с базой данных
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Выбор кабинета</title>
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
        }
        /* Стили для поля поиска */
        #searchRoom {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        /* Контейнер для списка кабинетов */
        .room-list {
            max-width: 600px;
            width: 90%;
            margin: 0 auto;
            padding: 0 10px;
            box-sizing: border-box;
        }
        /* Стили для каждой кнопки кабинета */
        .room-btn {
            display: block;
            width: 100%;
            margin-bottom: 16px;
            padding: 14px 0;
            font-size: 1.4rem;
            /* Исправлено: добавлен пробел между "solid" и цветом */
            border: 2px solid rgb(20, 97, 199);
            border-radius: 12px;
            background: linear-gradient(135deg, rgb(56, 54, 175), rgb(182, 185, 236));
            color: rgb(251, 252, 255);
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(168, 50, 80, 0.15);
            transition: background 0.3s ease, color 0.3s ease;
            user-select: none; /* запрет выделения текста */
        }
        /* Стили при наведении и фокусе */
        .room-btn:hover,
        .room-btn:focus {
            background: linear-gradient(135deg, #a83250, #7b203a);
            color: #fff;
            box-shadow: 0 6px 12px rgba(168, 50, 80, 0.4);
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
            /* Исправлено: добавлен пробел между "solid" и цветом */
            border: 1.5px solid rgb(50, 115, 168);
            border-radius: 6px;
            background: #fff;
            color: rgb(38, 32, 123);
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        /* Активная кнопка пагинации */
        .pagination button.active {
            background: rgb(94, 117, 216);
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
            .room-list {
                max-width: 100%;
                padding: 0;
            }
            .room-btn {
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
        <h2>Выберите кабинет</h2>
        <!-- Поле для поиска кабинетов -->
        <input type="text" id="searchRoom" placeholder="Поиск кабинета или корпуса..."
            aria-label="Поиск кабинета или корпуса" />
        <!-- Контейнер для списка кабинетов -->
        <div id="roomList" class="room-list" tabindex="0" aria-live="polite" aria-atomic="true"></div>
        <!-- Контейнер для пагинации -->
        <div class="pagination" id="pagination"></div>
    </main>
    <script>
        // Массив кабинетов, переданный из PHP
        const rooms = <?= json_encode($rooms) ?>;
        const roomsPerPage = 10; // Количество кабинетов на странице
        let currentPage = 1; // Текущая страница пагинации
        let filteredRooms = rooms.slice(); // Копия массива для фильтрации
        // Получаем элементы DOM для списка и пагинации
        const roomList = document.getElementById('roomList');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchRoom');
        // Функция отрисовки кабинетов на текущей странице
        function renderRooms(page) {
            roomList.innerHTML = ''; // Очищаем список
            const start = (page - 1) * roomsPerPage;
            const end = start + roomsPerPage;
            const pageRooms = filteredRooms.slice(start, end);
            // Если нет кабинетов для отображения — показываем сообщение
            if (pageRooms.length === 0) {
                roomList.innerHTML = '<p style="text-align:center; color:#a83250; font-weight:600;">Кабинеты не найдены</p>';
                pagination.innerHTML = '';
                return;
            }
            // Создаем кнопки-ссылки для каждого кабинета
            pageRooms.forEach(room => {
                const a = document.createElement('a');
                a.className = 'room-btn';
                a.href = 'rooms_admin.php?room_id=' + encodeURIComponent(room.id);
                a.textContent = `Кабинет ${room.number} / корпус ${room.building}`;
                roomList.appendChild(a);
            });
            // Отрисовываем пагинацию
            renderPagination(page);
        }
        // Функция отрисовки кнопок пагинации
        function renderPagination(page) {
            pagination.innerHTML = ''; // Очищаем пагинацию
            const totalPages = Math.ceil(filteredRooms.length / roomsPerPage);
            if (totalPages <= 1) return; // Если одна страница — пагинация не нужна
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === page) btn.classList.add('active'); // Активная страница выделена
                btn.onclick = () => {
                    currentPage = i;
                    renderRooms(currentPage);
                    window.scrollTo({ top: 0, behavior: 'smooth' }); // Плавный скролл вверх
                };
                pagination.appendChild(btn);
            }
        }
        // Обработчик ввода в поле поиска
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            // Фильтруем кабинеты по номеру или корпусу
            filteredRooms = rooms.filter(room =>
                room.number.toString().toLowerCase().includes(query) ||
                room.building.toLowerCase().includes(query)
            );
            currentPage = 1; // Сбрасываем на первую страницу после фильтрации
            renderRooms(currentPage);
        });
        // Инициализация — отрисовываем первую страницу при загрузке
        renderRooms(currentPage);
    </script>
</body>
</html>
