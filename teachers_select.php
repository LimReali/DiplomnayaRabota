<?php
// Подключение к базе данных MySQL с помощью mysqli
$host = 'MySQL-8.0';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
// Создаем соединение
$conn = new mysqli($host, $user, $password, $dbname);
// Проверяем соединение на ошибки
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем всех преподавателей из таблицы teachers, сортируя по имени
$teachers = [];
$sqlTeachers = "SELECT id, full_name FROM `teachers` ORDER BY full_name ASC";
if ($result = $conn->query($sqlTeachers)) {
    while ($row = $result->fetch_assoc()) {
        $teachers[] = ['id' => $row['id'], 'name' => $row['full_name']];
    }
    $result->free(); // освобождаем память результата
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <!-- Иконка сайта -->
    <link rel="icon" href="img.png" type="image/jpeg" />
    <title>Выбор преподавателя</title>
    <!-- Мета-тег для адаптивности -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Встроенные стили страницы -->
    <style>
        /* Основные стили для body */
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
            border-radius: 8px;
            background: #fff;
            padding: 6px 12px;
            box-shadow: 0 2px 6px rgba(168, 50, 80, 0.15);
        }
        /* Поле ввода поиска */
        .search-panel input[type="text"] {
            flex-grow: 1;
            border: none;
            font-size: 1.1rem;
            color: #7b203a;
            padding: 8px 10px;
            border-radius: 6px;
            outline: none;
            transition: box-shadow 0.2s ease;
        }
        /* Подсветка поля при фокусе */
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
        /* Контейнер для списка преподавателей */
        .teacher-list {
            max-width: 600px;
            width: 100%;
            margin: 0 auto;
            padding: 0 10px;
            box-sizing: border-box;
        }
        /* Кнопки преподавателей */
        .teacher-btn {
            display: block;
            width: 100%;
            margin-bottom: 16px;
            padding: 14px 0;
            border: 2px solid #a83250;
            border-radius: 12px;
            background: linear-gradient(135deg, #fce4ec, #f8bbd0);
            color: #7b203a;
            font-size: 1.4rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(168, 50, 80, 0.15);
            transition: background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
            user-select: none;
        }
        /* Эффекты при наведении и фокусе */
        .teacher-btn:hover,
        .teacher-btn:focus {
            background: linear-gradient(135deg, #a83250, #7b203a);
            color: #fff;
            box-shadow: 0 6px 12px rgba(168, 50, 80, 0.4);
            transform: translateY(-2px);
            outline: none;
        }
        /* Эффект при нажатии */
        .teacher-btn:active {
            transform: translateY(0);
            box-shadow: 0 3px 6px rgba(168, 50, 80, 0.3);
        }
        /* Пагинация */
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
    </style>
    <!-- Подключение боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
    <!-- Подключение стилей Select2 (если понадобится) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Подключение иконок Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <!-- Мобильная верхняя панель -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Вставка боковой панели -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <h2>Выберите преподавателя</h2>
            <!-- Панель поиска -->
            <div class="search-panel">
                <input type="text" id="searchInput" placeholder="Поиск преподавателя..." aria-label="Поиск преподавателя" />
                <span>🔎</span>
            </div>
            <!-- Список преподавателей -->
            <div class="teacher-list" id="teacherList"></div>
            <!-- Пагинация -->
            <div class="pagination" id="pagination"></div>
        </main>
    </div>
    <!-- Скрипт для боковой панели -->
    <script src="sidebar.js"></script>
    <script>
        // Массив преподавателей из PHP
        const teachers = <?= json_encode($teachers) ?>;
        const itemsPerPage = 20; // Количество элементов на странице
        let currentPage = 1; // Текущая страница
        let filteredTeachers = teachers.slice(); // Копия массива для фильтрации
        // DOM элементы
        const teacherList = document.getElementById('teacherList');
        const pagination = document.getElementById('pagination');
        const searchInput = document.getElementById('searchInput');
        // Функция для отрисовки преподавателей на странице
        function renderTeachers(page) {
            teacherList.innerHTML = ''; // Очищаем список
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const pageTeachers = filteredTeachers.slice(start, end);
            // Если преподаватели не найдены, выводим сообщение
            if (pageTeachers.length === 0) {
                teacherList.innerHTML = '<p style="text-align:center; color:#a83250; font-weight:600;">Преподаватели не найдены</p>';
                pagination.innerHTML = '';
                return;
            }
            // Создаем ссылки-кнопки для каждого преподавателя
            pageTeachers.forEach(teacher => {
                const a = document.createElement('a');
                a.className = 'teacher-btn';
                a.href = 'TEACHER_schedule.php?teacher_id=' + encodeURIComponent(teacher.id);
                a.textContent = teacher.name;
                teacherList.appendChild(a);
            });
            // Отрисовываем пагинацию
            renderPagination(page);
        }
        // Функция для отрисовки пагинации
        function renderPagination(page) {
            pagination.innerHTML = '';
            const totalPages = Math.ceil(filteredTeachers.length / itemsPerPage);
            if (totalPages <= 1) return; // Если всего одна страница, пагинация не нужна
            // Создаем кнопки для каждой страницы
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                if (i === page) btn.className = 'active'; // Активная страница выделена
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
            // Фильтруем преподавателей по имени
            filteredTeachers = teachers.filter(t => t.name.toLowerCase().includes(query));
            currentPage = 1; // Сбрасываем на первую страницу после фильтрации
            renderTeachers(currentPage);
        });
        // Первая отрисовка при загрузке страницы
        renderTeachers(currentPage);
    </script>
</body>
</html>
