<?php
// Запускаем сессию для управления авторизацией пользователя
session_start();
// Подключаем функции для работы с базой данных
require_once 'database.php';
// Проверяем, авторизован ли пользователь (администратор)
if (!isset($_SESSION['user_id'])) {
    // Если нет — перенаправляем на страницу входа
    header('Location: login.php');
    exit();
}
// Получаем соединение с базой данных
$conn = getDbConnection();
// Параметры пагинации
$perPage = 20; // Количество записей на странице
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Текущая страница, минимум 1
$offset = ($page - 1) * $perPage; // Смещение для SQL-запроса
// Массив для отображения типа изменения (код => описание)
$changeTypes = [
    'C' => 'Создание',
    'U' => 'Обновление',
    'D' => 'Удаление',
];
// Обработка фильтров из GET-параметров
$whereClauses = []; // Условия WHERE
$params = [];       // Параметры для подготовленного запроса
$paramTypes = '';   // Типы параметров для bind_param
// Фильтр по названию таблицы
if (!empty($_GET['table_name'])) {
    $whereClauses[] = 'h.table_name = ?';
    $params[] = $_GET['table_name'];
    $paramTypes .= 's';
}
// Фильтр по типу изменения
if (!empty($_GET['change_type'])) {
    $whereClauses[] = 'h.change_type = ?';
    $params[] = $_GET['change_type'];
    $paramTypes .= 's';
}
// Фильтр по дате "от"
if (!empty($_GET['date_from'])) {
    $whereClauses[] = 'h.changed_at >= ?';
    $params[] = $_GET['date_from'] . ' 00:00:00';
    $paramTypes .= 's';
}
// Фильтр по дате "до"
if (!empty($_GET['date_to'])) {
    $whereClauses[] = 'h.changed_at <= ?';
    $params[] = $_GET['date_to'] . ' 23:59:59';
    $paramTypes .= 's';
}
// Формируем строку WHERE, если есть условия
$whereSql = '';
if ($whereClauses) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}
// Запрос для подсчёта общего количества записей с учётом фильтров
$countSql = "SELECT COUNT(*) AS total FROM history h $whereSql";
$countStmt = $conn->prepare($countSql);
if ($params) {
    // Привязываем параметры, если есть
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRow = $countResult->fetch_assoc();
$totalRecords = $totalRow['total']; // Общее число записей
$totalPages = ceil($totalRecords / $perPage); // Количество страниц
// Запрос для получения данных с фильтрами и пагинацией
$dataSql = "
    SELECT h.id, h.table_name, h.change_type, h.old_value, h.new_value, h.changed_at, u.login AS changed_by
    FROM history h
    LEFT JOIN users u ON h.user_id = u.id
    $whereSql
    ORDER BY h.changed_at DESC
    LIMIT ? OFFSET ?
";
$dataStmt = $conn->prepare($dataSql);
if ($params) {
    // Добавляем параметры для LIMIT и OFFSET
    $fullParamTypes = $paramTypes . 'ii';
    $fullParams = array_merge($params, [$perPage, $offset]);
    $dataStmt->bind_param($fullParamTypes, ...$fullParams);
} else {
    // Если фильтров нет — только LIMIT и OFFSET
    $dataStmt->bind_param('ii', $perPage, $offset);
}
$dataStmt->execute();
$dataResult = $dataStmt->get_result();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>История изменений базы данных</title>
    <style>
        /* Сброс отступов и базовые стили */
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        body {
            padding-top: 60px;
            /* Отступ для фиксированной шапки */
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 30px auto;
            background: #f9f9f9;
            padding: 20px;
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
        }
        th,
        td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #34495e;
            color: white;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            margin: 0 5px;
            padding: 8px 12px;
            background-color: #2980b9;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .pagination a.disabled {
            background-color: #bdc3c7;
            pointer-events: none;
            cursor: default;
        }
        .filter-form {
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        label {
            margin-right: 15px;
            font-weight: 600;
        }
        select,
        input[type=date] {
            padding: 5px 8px;
            font-size: 14px;
            margin-right: 10px;
        }
        button {
            padding: 6px 15px;
            font-size: 14px;
            cursor: pointer;
            background-color: #2980b9;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
        }
        /* Фиксированная верхняя панель */
        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            margin: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        nav a,
        nav form button {
            color: #ecf0f1;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 600;
            font-size: 16px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        nav a:hover,
        nav form button:hover,
        nav a:focus,
        nav form button:focus {
            background-color: #2980b9;
            color: white;
            outline: none;
        }
        nav form {
            display: inline;
        }
        nav form button {
            padding: 0;
        }
        .admin-header {
            color: white;
        }
        .page-title {
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <!-- Фиксированная шапка с навигацией -->
    <header role="banner">
        <h1 class="admin-header">Панель администратора</h1>
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
    <!-- Заголовок страницы -->
    <h1 class="page-title">История изменений базы данных</h1>
    <!-- Форма фильтрации -->
    <form method="GET" class="filter-form" aria-label="Фильтр истории изменений">
        <label>
            Таблица:
            <select name="table_name">
                <option value="">Все</option>
                <?php
                // Получаем список уникальных таблиц из истории для фильтра
                $tablesResult = $conn->query("SELECT DISTINCT table_name FROM history ORDER BY table_name");
                $selectedTable = $_GET['table_name'] ?? '';
                while ($tbl = $tablesResult->fetch_assoc()) {
                    $sel = ($tbl['table_name'] === $selectedTable) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($tbl['table_name']) . '" ' . $sel . '>' . htmlspecialchars($tbl['table_name']) . '</option>';
                }
                ?>
            </select>
        </label>
        <label>
            Тип изменения:
            <select name="change_type">
                <option value="">Все</option>
                <option value="C" <?= (isset($_GET['change_type']) && $_GET['change_type'] === 'C') ? 'selected' : '' ?>>
                    Создание</option>
                <option value="U" <?= (isset($_GET['change_type']) && $_GET['change_type'] === 'U') ? 'selected' : '' ?>>
                    Обновление</option>
                <option value="D" <?= (isset($_GET['change_type']) && $_GET['change_type'] === 'D') ? 'selected' : '' ?>>
                    Удаление</option>
            </select>
        </label>
        <label>
            Дата от:
            <input type="date" name="date_from" value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>" />
        </label>
        <label>
            Дата до:
            <input type="date" name="date_to" value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>" />
        </label>
        <button type="submit">Фильтровать</button>
    </form>
    <!-- Таблица с историей изменений -->
    <table aria-label="Таблица истории изменений базы данных">
        <thead>
            <tr>
                <th>Дата и время</th>
                <th>Пользователь</th>
                <th>Таблица</th>
                <th>Тип изменения</th>
                <th>Старое значение</th>
                <th>Новое значение</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($dataResult->num_rows === 0): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">Нет записей, соответствующих фильтрам.</td>
                </tr>
            <?php else: ?>
                <?php while ($row = $dataResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['changed_at']) ?></td>
                        <td><?= htmlspecialchars($row['changed_by'] ?? 'Неизвестно') ?></td>
                        <td><?= htmlspecialchars($row['table_name']) ?></td>
                        <td><?= htmlspecialchars($changeTypes[$row['change_type']] ?? $row['change_type']) ?></td>
                        <td>
                            <pre
                                style="white-space: pre-wrap; max-width: 200px;"><?= htmlspecialchars($row['old_value'] ?? '') ?></pre>
                        </td>
                        <td>
                            <pre
                                style="white-space: pre-wrap; max-width: 200px;"><?= htmlspecialchars($row['new_value'] ?? '') ?></pre>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <!-- Пагинация -->
    <div class="pagination" role="navigation" aria-label="Пагинация истории изменений">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Назад</a>
        <?php else: ?>
            <a class="disabled" aria-disabled="true">&laquo; Назад</a>
        <?php endif; ?>
        <span>Страница <?= $page ?> из <?= $totalPages ?></span>
        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Вперёд &raquo;</a>
        <?php else: ?>
            <a class="disabled" aria-disabled="true">Вперёд &raquo;</a>
        <?php endif; ?>
    </div>
</body>
</html>