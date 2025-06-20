<?php
// Подключение к базе данных MySQL с использованием mysqli
$host = 'MySQL-8.0';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
// Создаем новое соединение
$conn = new mysqli($host, $user, $password, $dbname);
// Проверяем соединение на ошибки
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Инициализируем массив для хранения кабинетов
$rooms = [];
// Формируем SQL-запрос для получения всех кабинетов с номерами и корпусами
// Сортируем сначала по номеру корпуса (числовая часть), затем по номеру кабинета (числовая часть)
$sqlRooms = "SELECT id, number, building FROM rooms ORDER BY building+0 ASC, number+0 ASC";
// Выполняем запрос
if ($result = $conn->query($sqlRooms)) {
    // Проходим по всем результатам и формируем массив с id и меткой для отображения
    while ($row = $result->fetch_assoc()) {
        $rooms[] = [
            'id' => $row['id'],
            'label' => $row['number'] . '/' . $row['building'] // Формат отображения: номер/корпус
        ];
    }
    $result->free(); // Освобождаем память результата
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
        /* Стили для тела страницы */
        body {
            font-family: Arial, sans-serif;
            /* Шрифт без засечек */
            background: #f8f5f7;
            /* Светло-розовый фон */
            color: #7b203a;
            /* Бордовый цвет текста */
            padding: 20px;
            /* Отступы вокруг содержимого */
        }
        /* Заголовок второго уровня */
        h2 {
            text-align: center;
            /* Выравнивание по центру */
            margin-bottom: 20px;
            /* Отступ снизу */
        }
        /* Панель поиска */
        .search-panel {
            max-width: 400px;
            /* Максимальная ширина */
            margin: 0 auto 20px auto;
            /* Центрирование и отступ снизу */
            display: flex;
            /* Горизонтальное расположение элементов */
            align-items: center;
            /* Вертикальное выравнивание */
            border: 2px solid #a83250;
            /* Бордовая рамка */
            border-radius: 8px;
            /* Скругление углов */
            background: #fff;
            /* Белый фон */
            padding: 6px 12px;
            /* Внутренние отступы */
            box-shadow: 0 2px 6px rgba(168, 50, 80, 0.15);
            /* Тень */
        }
        /* Поле ввода в панели поиска */
        .search-panel input[type="text"] {
            flex-grow: 1;
            /* Занимает всю доступную ширину */
            border: none;
            /* Без рамки */
            font-size: 1.1rem;
            /* Размер шрифта */
            color: #7b203a;
            /* Бордовый цвет текста */
            padding: 8px 10px;
            /* Внутренние отступы */
            border-radius: 6px;
            /* Скругление углов */
            outline: none;
            /* Без обводки при фокусе */
            transition: box-shadow 0.2s ease;
            /* Плавный переход тени */
        }
        /* Подсветка поля ввода при фокусе */
        .search-panel input[type="text"]:focus {
            box-shadow: 0 0 5px #a83250;
            /* Бордовая тень */
        }
        /* Иконка поиска или символ рядом с полем */
        .search-panel span {
            font-size: 1.2rem;
            color: #a83250;
            cursor: default;
            /* Курсор по умолчанию */
            margin-left: 8px;
            /* Отступ слева */
        }
        /* Контейнер для списка кабинетов */
        .room-list {
            max-width: 600px;
            /* Максимальная ширина */
            width: 100%;
            /* Ширина 100% от контейнера */
            margin: 0 auto;
            /* Центрирование */
            padding: 0 10px;
            /* Внутренние отступы */
            box-sizing: border-box;
            /* Включаем padding и border в ширину */
        }
        /* Кнопки для выбора кабинетов */
        .room-btn {
            display: block;
            /* Блочный элемент */
            width: 100%;
            /* Занимает всю ширину контейнера */
            margin-bottom: 16px;
            /* Отступ снизу между кнопками */
            padding: 14px 0;
            /* Вертикальные отступы */
            border: 2px solid #a83250;
            /* Бордовая рамка */
            border-radius: 12px;
            /* Скругленные углы */
            background: linear-gradient(135deg, #fce4ec, #f8bbd0);
            /* Нежный розовый градиент */
            color: #7b203a;
            /* Бордовый цвет текста */
            font-size: 1.4rem;
            /* Крупный шрифт */
            font-weight: 700;
            /* Жирный текст */
            cursor: pointer;
            /* Курсор в виде руки */
            text-align: center;
            /* Выравнивание текста по центру */
            text-decoration: none;
            /* Без подчеркивания */
            box-shadow: 0 4px 8px rgba(168, 50, 80, 0.15);
            /* Тень */
            transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
            user-select: none;
            /* Запрет выделения текста */
        }
        /* Эффекты при наведении и фокусе на кнопках кабинетов */
        .room-btn:hover,
        .room-btn:focus {
            background: linear-gradient(135deg, #a83250, #7b203a);
            /* Темный бордовый градиент */
            color: #fff;
            /* Белый цвет текста */
            box-shadow: 0 6px 12px rgba(168, 50, 80, 0.4);
            /* Яркая тень */
            transform: translateY(-2px);
            /* Легкое поднятие */
            outline: none;
            /* Без обводки */
        }
        /* Состояние кнопки при клике */
        .room-btn:active {
            transform: translateY(0);
            /* Возвращение в исходное положение */
            box-shadow: 0 3px 6px rgba(168, 50, 80, 0.3);
            /* Мягкая тень */
        }
        /* Контейнер пагинации */
        .pagination {
            margin: 24px 0 0 0;
            /* Отступ сверху */
            text-align: center;
            /* Центрирование */
        }
        /* Кнопки пагинации */
        .pagination button {
            margin: 0 3px;
            /* Отступы по бокам */
            padding: 7px 15px;
            /* Внутренние отступы */
            border: 1.5px solid #a83250;
            /* Бордовая рамка */
            border-radius: 6px;
            /* Скругленные углы */
            background: #fff;
            /* Белый фон */
            color: #7b203a;
            /* Бордовый цвет текста */
            font-weight: 600;
            /* Жирный шрифт */
            cursor: pointer;
            /* Курсор в виде руки */
            transition: background 0.2s, color 0.2s;
            /* Плавные переходы */
        }
        /* Активная страница пагинации */
        .pagination button.active {
            background: #a83250;
            /* Бордовый фон */
            color: #fff;
            /* Белый текст */
        }
    </style>
    <!-- Подключение иконки сайта -->
    <link rel="icon" href="img.png" type="image/jpeg" />
    <!-- Подключение стилей боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
    <!-- Подключение стилей Select2 для выпадающих списков (если потребуется) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Подключение иконок Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <!-- Мобильная верхняя панель для маленьких экранов -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <!-- Кнопка для открытия меню -->
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Вставка боковой панели через PHP -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <!-- Заголовок страницы -->
            <h2>Выберите кабинет</h2>
            <!-- Панель поиска кабинета -->
            <div class="search-panel">
                <input type="text" id="searchInput" placeholder="Поиск кабинета..." aria-label="Поиск кабинета" />
                <span>🔎</span>
            </div>
            <!-- Список кабинетов (будет заполняться через JS) -->
            <div class="room-list" id="roomList"></div>
            <!-- Пагинация (будет заполняться через JS) -->
            <div class="pagination" id="pagination"></div>
        </main>
    </div>
    <script>
        // Получаем массив кабинетов из PHP (JSON)
        const rooms = <?= json_encode($rooms) ?>;
        const roomsPerPage = 20; // Количество кабинетов на странице
        let currentPage = 1; // Текущая страница
        let filteredRooms = rooms.slice(); // Копия массива для фильтрации
        // Получаем элементы DOM для списка кабинетов и пагинации
        const roomList = document.getElementById('roomList');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');
        // Функция отрисовки кабинетов на странице
        function renderRooms(page) {
            roomList.innerHTML = ''; // Очищаем список
            const start = (page - 1) * roomsPerPage;
            const end = start + roomsPerPage;
            const pageRooms = filteredRooms.slice(start, end);
            // Если кабинетов нет — выводим сообщение
            if (pageRooms.length === 0) {
                roomList.innerHTML = '<p style="text-align:center; color:#a83250; font-weight:600;">Кабинеты не найдены</p>';
                pagination.innerHTML = '';
                return;
            }
            // Для каждого кабинета создаём ссылку-кнопку
            pageRooms.forEach(room => {
                const a = document.createElement('a');
                a.className = 'room-btn';
                a.href = 'ROOM_schedule.php?room_id=' + encodeURIComponent(room.id); // Переход к расписанию кабинета
                a.textContent = room.label; // Отображаемый текст (номер/корпус)
                roomList.appendChild(a);
            });
            // Отрисовываем пагинацию
            renderPagination(page);
        }
        // Функция отрисовки пагинации
        function renderPagination(page) {
            pagination.innerHTML = '';
            const totalPages = Math.ceil(filteredRooms.length / roomsPerPage);
            if (totalPages <= 1) return; // Если всего одна страница — пагинация не нужна
            // Создаём кнопки для каждой страницы
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === page) btn.className = 'active'; // Активная страница выделена
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
            // Фильтруем кабинеты по номеру/корпусу
            filteredRooms = rooms.filter(r => r.label.toLowerCase().includes(query));
            currentPage = 1; // Сбрасываем на первую страницу после фильтрации
            renderRooms(currentPage);
        });
        // Первая отрисовка при загрузке страницы
        renderRooms(currentPage);
    </script>
    <!-- Скрипт для боковой панели (адаптивное меню) -->
    <script src="sidebar.js"></script>
</body>
</html>