<?php
// Подключение к базе данных
$host = 'MySQL-8.0';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
// Создаем новое соединение с базой данных MySQL
$conn = new mysqli($host, $user, $password, $dbname);
// Проверяем соединение на ошибки
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем все группы из таблицы `groups`
// Сортируем по имени, учитывая числовую часть (name+0) и алфавитную
// Получение faculty
$sqlGroups = "SELECT id, name, faculty FROM `groups` ORDER BY name+0 ASC, name ASC";
if ($result = $conn->query($sqlGroups)) {
    while ($row = $result->fetch_assoc()) {
        $groups[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'faculty' => $row['faculty']
        ];
    }
    $result->free();
}
// Получаем список факультетов из таблицы groups 
$faculties = [];
$sqlFaculties = "SELECT DISTINCT faculty FROM `groups` ORDER BY faculty ASC";
if ($result = $conn->query($sqlFaculties)) {
    while ($row = $result->fetch_assoc()) {
        $faculties[] = $row['faculty'];
    }
    $result->free();
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
        /* Основные стили страницы */
        body {
            font-family: Arial, sans-serif;
            background: #f8f5f7;
            color: #7b203a;
            padding: 20px;
        }
        /* Заголовок */
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        /* Панель поиска */
        .search-panel {
            max-width: 400px;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            border: 2px solid #a83250;
            /* бордовая рамка */
            border-radius: 8px;
            background: #fff;
            padding: 6px 12px;
            box-shadow: 0 2px 6px rgba(168, 50, 80, 0.15);
        }
        /* Поле ввода поиска */
        .search-panel input[type="text"] {
            flex-grow: 1;
            /* занимает оставшееся пространство */
            border: none;
            font-size: 1.1rem;
            color: #7b203a;
            padding: 8px 10px;
            border-radius: 6px;
            outline: none;
            transition: box-shadow 0.2s ease;
        }
        /* Подсветка поля ввода при фокусе */
        .search-panel input[type="text"]:focus {
            box-shadow: 0 0 5px #a83250;
        }
        /* Иконка поиска */
        .search-panel span {
            font-size: 1.2rem;
            color: #a83250;
            cursor: default;
            margin-left: 8px;
        }
        /* Список групп */
        .group-list {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 0 10px;
            box-sizing: border-box;
        }
        /* Кнопки групп */
        .group-btn {
            display: block;
            width: 100%;
            /* кнопка занимает всю ширину контейнера */
            margin-bottom: 16px;
            /* отступ между кнопками */
            padding: 14px 0;
            /* вертикальные отступы */
            border: 2px solid #a83250;
            /* бордовая рамка */
            border-radius: 12px;
            /* скругленные углы */
            background: linear-gradient(135deg, #fce4ec, #f8bbd0);
            /* нежный розовый градиент */
            color: #7b203a;
            /* бордовый текст */
            font-size: 1.4rem;
            /* крупный текст */
            font-weight: 700;
            /* жирный */
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(168, 50, 80, 0.15);
            transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
            user-select: none;
            /* запрет выделения текста */
        }
        /* Стили при наведении и фокусе */
        .group-btn:hover,
        .group-btn:focus {
            background: linear-gradient(135deg, #a83250, #7b203a);
            /* насыщенный бордовый градиент */
            color: #fff;
            /* белый текст */
            box-shadow: 0 6px 12px rgba(168, 50, 80, 0.4);
            transform: translateY(-2px);
            /* легкое поднятие */
            outline: none;
        }
        /* Состояние активной кнопки при клике */
        .group-btn:active {
            transform: translateY(0);
            box-shadow: 0 3px 6px rgba(168, 50, 80, 0.3);
        }
        /* Контейнер пагинации */
        .pagination {
            margin: 24px 0 0 0;
            text-align: center;
        }
        /* Кнопки пагинации */
        .pagination button {
            margin: 0 3px;
            padding: 7px 15px;
            border: 1.5px solid #a83250;
            border-radius: 6px;
            background: #fff;
            color: #7b203a;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        /* Активная страница пагинации */
        .pagination button.active {
            background: #a83250;
            color: #fff;
        }
        /* Контейнер фильтра по факультетам */
        .faculty-filter {
            max-width: 800px;
            margin: 0 auto 20px auto;
            /* Центрирование и отступ снизу */
            display: flex;
            align-items: center;
            border: 2px solid #a83250;
            /* бордовая рамка, как у панели поиска */
            border-radius: 8px;
            background: #fff;
            padding: 6px 12px;
            box-shadow: 0 2px 6px rgba(168, 50, 80, 0.15);
        }
        /* Метка (label) в фильтре */
        .faculty-filter label {
            font-weight: bold;
            color: #7b203a;
            margin-right: 10px;
            font-size: 1.1rem;
            white-space: nowrap;
        }
        /* Выпадающий список (select) */
        .faculty-filter select {
            flex-grow: 1;
            /* занимает оставшееся пространство */
            border: none;
            font-size: 1.1rem;
            color: #7b203a;
            padding: 8px 10px;
            border-radius: 6px;
            outline: none;
            cursor: pointer;
            transition: box-shadow 0.2s ease;
        }
        /* Подсветка выпадающего списка при фокусе */
        .faculty-filter select:focus {
            box-shadow: 0 0 5px #a83250;
        }
    </style>
    <!-- Иконка сайта -->
    <link rel="icon" href="img.png" type="image/jpeg" />
    <!-- Стили боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
    <!-- Стили иконок FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <!-- Верхняя панель для мобильных устройств -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Подключаем боковую панель -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <h2>Выберите группу</h2>
            <!-- Панель поиска -->
            <div class="search-panel">
                <input type="text" id="searchInput" placeholder="Поиск группы..." aria-label="Поиск группы" />
                <span>🔎</span>
            </div>
            <!-- Фильтр по факультету -->
            <div class="faculty-filter" style="margin-bottom: 20px;">
                <label for="facultySelect"><strong>Факультет:</strong></label>
                <select id="facultySelect">
                    <option value="">Все факультеты</option>
                    <?php foreach ($faculties as $faculty): ?>
                        <option value="<?= htmlspecialchars($faculty) ?>"><?= htmlspecialchars($faculty) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Список групп -->
            <div class="group-list" id="groupList"></div>
            <!-- Пагинация -->
            <div class="pagination" id="pagination"></div>
        </main>
    </div>
    <script>
        // Массив групп, полученный из PHP
        const groups = <?= json_encode($groups) ?>;
        const groupsPerPage = 20; // Количество групп на странице
        let currentPage = 1; // Текущая страница
        let filteredGroups = groups.slice(); // Копия массива для фильтрации
        // Получаем элементы DOM для списка групп и пагинации
        const groupList = document.getElementById('groupList');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');
        // Функция отрисовки списка групп на странице
        function renderGroups(page) {
            groupList.innerHTML = ''; // Очищаем список
            const start = (page - 1) * groupsPerPage;
            const end = start + groupsPerPage;
            const pageGroups = filteredGroups.slice(start, end);
            // Если групп нет — выводим сообщение
            if (pageGroups.length === 0) {
                groupList.innerHTML = '<p style="text-align:center; color:#a83250; font-weight:600;">Группы не найдены</p>';
                pagination.innerHTML = '';
                return;
            }
            // Для каждой группы создаем кнопку-ссылку
            pageGroups.forEach(group => {
                const a = document.createElement('a');
                a.className = 'group-btn';
                a.href = 'GROUP_shedule.php?group_id=' + encodeURIComponent(group.id);
                a.textContent = group.name;
                groupList.appendChild(a);
            });
            // Отрисовываем пагинацию
            renderPagination(page);
        }
        // Функция отрисовки пагинации
        function renderPagination(page) {
            pagination.innerHTML = ''; // Очищаем пагинацию
            const totalPages = Math.ceil(filteredGroups.length / groupsPerPage);
            if (totalPages <= 1) return; // Если всего одна страница — пагинация не нужна
            // Создаем кнопки для каждой страницы
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === page) btn.className = 'active'; // Активная страница выделена
                btn.onclick = () => {
                    currentPage = i;
                    renderGroups(currentPage);
                    window.scrollTo({ top: 0, behavior: 'smooth' }); // Плавный скролл вверх
                };
                pagination.appendChild(btn);
            }
        }
        // Обработчик ввода в поле поиска
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            // Фильтруем группы по названию
            filteredGroups = groups.filter(g => g.name.toLowerCase().includes(query));
            currentPage = 1; // Сбрасываем на первую страницу после фильтрации
            renderGroups(currentPage);
        });
        const facultySelect = document.getElementById('facultySelect');
        facultySelect.addEventListener('change', () => {
            filterGroups();
            currentPage = 1;
            renderGroups(currentPage);
        });
        searchInput.addEventListener('input', () => {
            filterGroups();
            currentPage = 1;
            renderGroups(currentPage);
        });
        // Функция фильтрации по факультету и поиску
        function filterGroups() {
            const faculty = facultySelect.value;
            const query = searchInput.value.trim().toLowerCase();
            filteredGroups = groups.filter(g => {
                const matchesFaculty = !faculty || g.faculty === faculty;
                const matchesQuery = g.name.toLowerCase().includes(query);
                return matchesFaculty && matchesQuery;
            });
        }
        // Инициализация — отрисовываем первую страницу при загрузке
        renderGroups(currentPage);
    </script>
    <!-- Скрипт для боковой панели -->
    <script src="sidebar.js"></script>
</body>
</html>