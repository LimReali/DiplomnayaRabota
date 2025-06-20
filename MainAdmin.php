<?php
// Запускаем сессию для управления авторизацией и состоянием пользователя
session_start();
// Проверяем, авторизован ли пользователь (администратор)
if (!isset($_SESSION['user_id'])) {
    // Если нет, перенаправляем на страницу входа
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Главная страница администрирования</title>
    <style>
        /* Сброс отступов и базовые стили */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9fafb;
            /* светлый фон страницы */
            margin: 0;
            padding: 0;
            color: #333;
            /* основной цвет текста */
        }
        /* Стили для шапки */
        header {
            background-color: #34495e;
            /* темно-синий фон */
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            /* элементы по краям */
            align-items: center;
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
            max-width: 900px;
            /* ограничиваем ширину */
            margin: 40px auto;
            /* центрируем по горизонтали и отступ сверху */
            padding: 0 20px;
        }
        /* Заголовок второго уровня */
        h2 {
            margin-top: 0;
        }
        /* Описание приветствия */
        p {
            font-size: 1.1rem;
            line-height: 1.5;
        }
        /* Контейнер с ссылками на разделы админ-панели */
        .admin-links {
            display: flex;
            flex-wrap: wrap;
            /* перенос строк при необходимости */
            gap: 20px;
            /* расстояние между ссылками */
        }
        /* Стили для каждой ссылки-карточки */
        .admin-link {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            flex: 1 1 250px;
            /* гибкая ширина, минимум 250px */
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.3s ease;
            text-align: center;
            font-size: 18px;
            color: #34495e;
            text-decoration: none;
            font-weight: 600;
        }
        /* Эффект при наведении на ссылку-карточку */
        .admin-link:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background-color: #f0f4f8;
        }
        /* Стили для подвала страницы */
        footer {
            text-align: center;
            padding: 20px;
            color: #777;
            font-size: 14px;
            border-top: 1px solid #ddd;
            margin-top: 60px;
        }
    </style>
</head>
<body>
    <!-- Шапка сайта с названием и навигацией -->
    <header>
        <h1>Панель администратора</h1>
        <nav>
            <!-- Ссылки на основные разделы админ-панели -->
            <a href="add_lesson.php">Добавить занятие</a>
            <a href="groups_admin.php">Управление группами</a>
            <a href="teachers_admin.php">Управление преподавателями</a>
            <a href="db_history.php">История изменений базы</a>
            <a href="admin_rooms_select.php">Управление кабинетами</a>
            <!-- Форма выхода из системы -->
            <form action="logout.php" method="post" style="display:inline;">
                <button type="submit" title="Выйти из системы">Выйти</button>
            </form>
        </nav>
    </header>
    <!-- Основной контент -->
    <main>
        <!-- Приветствие пользователя -->
        <h2>Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <p>Здесь вы можете управлять расписанием, группами, преподавателями и просматривать историю изменений базы
            данных.</p>
        <!-- Ссылки-карточки для быстрого перехода к разделам -->
        <div class="admin-links">
            <a href="add_lesson.php" class="admin-link">Добавить новое занятие</a>
            <a href="groups_select.php" class="admin-link">Редактировать по группам</a>
            <a href="teachers_admin.php" class="admin-link">Редактировать по преподавателям</a>
            <a href="admin_rooms_select.php" class="admin-link">Редактировать по кабинетам</a>
            <a href="db_history.php" class="admin-link">Просмотр истории изменений</a>
        </div>
    </main>
    <!-- Подвал сайта -->
    <footer>
        &copy; <?= date('Y') ?>
    </footer>
</body>
</html>