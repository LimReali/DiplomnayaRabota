<?php
// Запускаем сессию для управления авторизацией пользователя
session_start();
// Подключаем файл с функцией для получения соединения с базой данных
require_once 'database.php';
// Проверяем, авторизован ли пользователь (администратор)
if (!isset($_SESSION['user_id'])) {
    // Если нет, перенаправляем на страницу входа
    header('Location: login.php');
    exit();
}
// Получаем соединение с базой данных
$conn = getDbConnection();
// Получаем ID выбранного кабинета из GET-параметров, если нет — перенаправляем на выбор кабинета
$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
if (!$roomId) {
    header('Location: admin_rooms_select.php');
    exit();
}
// Инициализируем переменные для сообщений об ошибках и успехах
$error = '';
$success = '';
// Обработка удаления записи из расписания (POST-запрос с параметром delete_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    // Получаем id текущего пользователя из сессии
    $userId = intval($_SESSION['user_id']);
    // Устанавливаем MySQL-переменную для триггеров, чтобы зафиксировать пользователя, который удаляет запись
    $conn->query("SET @current_user_id = $userId");
    // Получаем id записи для удаления из POST
    $deleteId = intval($_POST['delete_id']);
    // Подготавливаем запрос на удаление записи с указанным id и кабинетом
    $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ? AND room_id = ?");
    if (!$stmt) {
        $error = "Ошибка подготовки запроса удаления: " . $conn->error;
    } else {
        // Привязываем параметры (два целых числа: id записи и id кабинета)
        $stmt->bind_param("ii", $deleteId, $roomId);
        // Выполняем запрос и проверяем результат
        if ($stmt->execute()) {
            $success = "Запись успешно удалена.";
        } else {
            $error = "Ошибка удаления: " . $stmt->error;
        }
        $stmt->close();
    }
}
// Обработка обновления записи через AJAX (POST-запрос с параметром edit_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    // Получаем id текущего пользователя из сессии
    $userId = intval($_SESSION['user_id']);
    // Устанавливаем MySQL-переменную для триггеров, чтобы зафиксировать пользователя, который обновляет запись
    $conn->query("SET @current_user_id = $userId");
    // Устанавливаем заголовок для JSON-ответа
    header('Content-Type: application/json; charset=utf-8');
    // Получаем параметры из POST
    $editId = intval($_POST['edit_id']);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    // Разрешённые поля для обновления (защита от SQL-инъекций)
    $allowedFields = ['date', 'group_id', 'subject_id', 'teacher_id', 'lesson_type_id', 'time_slot_id'];
    if (!in_array($field, $allowedFields)) {
        echo json_encode(['status' => 'error', 'message' => 'Недопустимое поле']);
        exit();
    }
    // Формируем запрос на обновление указанного поля для записи с нужным id и кабинетом
    $sql = "UPDATE schedule SET $field = ? WHERE id = ? AND room_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка подготовки запроса']);
        exit();
    }
    // Привязываем параметры: новое значение (строка), id записи и id кабинета (целые числа)
    $stmt->bind_param("sii", $value, $editId, $roomId);
    // Выполняем запрос и возвращаем JSON с результатом
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка обновления: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}
// Получаем номер кабинета и корпус для отображения
$stmt = $conn->prepare("SELECT number, building FROM rooms WHERE id = ?");
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param("i", $roomId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $roomNumber = $row['number'];
    $roomBuilding = $row['building']; // Корпус кабинета
} else {
    die("Кабинет не найден");
}
$stmt->close();
// Получаем фильтры из GET-параметров
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$filterGroupId = isset($_GET['group_id']) && is_numeric($_GET['group_id']) ? intval($_GET['group_id']) : null;
$filterTeacherId = isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;
// Формируем условия WHERE и параметры для SQL-запроса
$where = ["s.room_id = ?"]; // Обязательное условие по кабинету
$params = [$roomId];         // Параметры для привязки
$types = "i";                // Типы параметров (i — integer)
// Добавляем условия фильтрации по дате, группе и преподавателю, если заданы
if ($dateFrom !== '') {
    $where[] = "s.date >= ?";
    $params[] = $dateFrom;
    $types .= "s"; // s — string
}
if ($dateTo !== '') {
    $where[] = "s.date <= ?";
    $params[] = $dateTo;
    $types .= "s";
}
if ($filterGroupId !== null) {
    $where[] = "s.group_id = ?";
    $params[] = $filterGroupId;
    $types .= "i";
}
if ($filterTeacherId !== null) {
    $where[] = "s.teacher_id = ?";
    $params[] = $filterTeacherId;
    $types .= "i";
}
// Объединяем условия в строку WHERE с помощью AND
$whereSql = implode(" AND ", $where);
// Формируем SQL-запрос с JOIN для получения всех необходимых данных для отображения расписания
$sql = "
SELECT 
    s.id,
    s.date,
    s.group_id,
    g.name AS group_name,
    s.subject_id,
    sub.name AS subject_name,
    s.teacher_id,
    t.full_name AS teacher_name,
    s.lesson_type_id,
    lt.name AS lesson_type_name,
    s.time_slot_id,
    ts.label AS time_slot_label
FROM schedule s
LEFT JOIN `groups` g ON s.group_id = g.id
LEFT JOIN subjects sub ON s.subject_id = sub.id
LEFT JOIN teachers t ON s.teacher_id = t.id
LEFT JOIN lesson_types lt ON s.lesson_type_id = lt.id
LEFT JOIN time_slots ts ON s.time_slot_id = ts.id
WHERE $whereSql
ORDER BY s.date, s.time_slot_id;
";
// Подготавливаем SQL-запрос
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
// Динамически связываем параметры с запросом
$bindParams = [];
$bindParams[] = &$types; // Первый параметр — строка типов
for ($i = 0; $i < count($params); $i++) {
    $bindParams[] = &$params[$i]; // Остальные — сами параметры
}
// Используем call_user_func_array для вызова bind_param с массивом параметров
call_user_func_array([$stmt, 'bind_param'], $bindParams);
// Выполняем запрос
$stmt->execute();
// Получаем результат запроса
$result = $stmt->get_result();
// Формируем массив расписания для удобного вывода
$schedule = [];
while ($row = $result->fetch_assoc()) {
    $schedule[] = $row;
}
$stmt->close();
/**
 * Функция для загрузки справочников (группы, преподаватели, предметы и т.д.)
 * 
 * @param mysqli $conn соединение с базой данных
 * @param string $table имя таблицы
 * @param string $idField имя поля с ID
 * @param string $nameField имя поля с названием
 * @return array ассоциативный массив id => name
 */
function loadList($conn, $table, $idField, $nameField)
{
    $list = [];
    // Экранируем имя таблицы, чтобы избежать SQL-инъекций
    $tableEscaped = "`" . str_replace("`", "``", $table) . "`";
    $sql = "SELECT $idField, $nameField FROM $tableEscaped ORDER BY $nameField";
    $res = $conn->query($sql);
    if (!$res) {
        die("Ошибка запроса $table: " . $conn->error);
    }
    while ($row = $res->fetch_assoc()) {
        $list[$row[$idField]] = $row[$nameField];
    }
    return $list;
}
// Загружаем справочники для фильтров и выпадающих списков
$groups = loadList($conn, 'groups', 'id', 'name');
$teachers = loadList($conn, 'teachers', 'id', 'full_name');
$subjects = loadList($conn, 'subjects', 'id', 'name');
$lessonTypes = loadList($conn, 'lesson_types', 'id', 'name');
$timeSlots = loadList($conn, 'time_slots', 'id', 'label');
// Закрываем соединение с базой данных
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <!-- Заголовок страницы с динамическим подставлением номера и корпуса кабинета -->
    <title>Редактирование расписания кабинета <?= htmlspecialchars($roomNumber) ?> /
        <?= htmlspecialchars($roomBuilding) ?></title>
    <!-- Обеспечивает адаптивность страницы на мобильных устройствах -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Подключение внешнего CSS для сайдбара -->
    <link rel="stylesheet" href="sidebar.css" />
    <style>
        /* Основные стили для тела страницы: шрифт, максимальная ширина и отступы */
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 30px auto;
        }
        /* Отступ снизу у заголовков первого уровня */
        h1 {
            margin-bottom: 20px;
        }
        /* Стили таблицы: слияние границ и ширина 100% */
        table {
            border-collapse: collapse;
            width: 100%;
        }
        /* Стили для ячеек таблицы: рамка, отступы и выравнивание текста */
        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }
        /* Стили для заголовков таблицы: синий фон и белый текст */
        th {
            background-color: #3498db;
            color: white;
        }
        /* При наведении на редактируемые ячейки меняется фон и курсор */
        td.editable:hover {
            background-color: #f0f8ff;
            cursor: pointer;
        }
        /* Общие стили для кнопок редактирования, отмены и удаления */
        button.edit-btn,
        button.cancel-btn,
        button.delete-btn {
            background-color: #3498db;
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.4);
            user-select: none;
            margin-bottom: 8px;
        }
        /* Эффект при наведении на кнопки */
        button.edit-btn:hover,
        button.cancel-btn:hover,
        button.delete-btn:hover {
            background-color: #2980b9;
            box-shadow: 0 4px 8px rgba(41, 128, 185, 0.6);
        }
        /* Особые стили для кнопки отмены: красный фон и отступ слева */
        button.cancel-btn {
            background-color: #e74c3c;
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.4);
            margin-left: 8px;
        }
        button.cancel-btn:hover {
            background-color: #c0392b;
            box-shadow: 0 4px 8px rgba(192, 57, 43, 0.6);
        }
        /* Особые стили для кнопки удаления: красный фон */
        button.delete-btn {
            background-color: #e74c3c;
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.4);
        }
        /* Стили для ссылки "назад" в виде кнопки */
        a.btn-back {
            display: inline-block;
            margin-bottom: 20px;
            background-color: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        /* Эффект при наведении на ссылку "назад" */
        a.btn-back:hover {
            background-color: #2980b9;
        }
        /* Стили для формы фильтрации: горизонтальное расположение с отступами */
        form.filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        /* Стили для меток в форме: вертикальное расположение и жирный шрифт */
        form.filter-form label {
            display: flex;
            flex-direction: column;
            font-weight: bold;
            font-size: 14px;
        }
        /* Стили для полей ввода и выпадающих списков в форме */
        form.filter-form input,
        form.filter-form select {
            padding: 6px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            min-width: 150px;
        }
        /* Стили для кнопки отправки формы */
        form.filter-form button {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        /* Эффект при наведении на кнопку формы */
        form.filter-form button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Ссылка для возврата к выбору кабинета -->
    <a href="admin_rooms_select.php" class="btn-back">← Назад к выбору кабинета</a>
    <!-- Заголовок страницы с динамическим выводом номера и корпуса кабинета -->
    <h1>Редактирование расписания кабинета: <?= htmlspecialchars($roomNumber) ?> /
        <?= htmlspecialchars($roomBuilding) ?></h1>
    <!-- Вывод сообщений об ошибках или успехе, если они есть -->
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <!-- Форма фильтрации расписания по дате, группе и преподавателю -->
    <form method="GET" class="filter-form" action="rooms_admin.php">
        <!-- Скрытое поле с id кабинета для сохранения контекста -->
        <input type="hidden" name="room_id" value="<?= htmlspecialchars($roomId) ?>">
        <!-- Фильтр по дате "с" -->
        <label>
            Дата с:
            <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
        </label>
        <!-- Фильтр по дате "по" -->
        <label>
            Дата по:
            <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
        </label>
        <!-- Фильтр по группе с выпадающим списком -->
        <label>
            Группа:
            <select name="group_id">
                <option value="">Все</option> <!-- Опция для выбора всех групп -->
                <?php foreach ($groups as $id => $name): ?>
                    <!-- Опция с выбранным состоянием, если фильтр совпадает -->
                    <option value="<?= $id ?>" <?= ($filterGroupId === $id) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <!-- Фильтр по преподавателю с выпадающим списком -->
        <label>
            Преподаватель:
            <select name="teacher_id">
                <option value="">Все</option> <!-- Опция для выбора всех преподавателей -->
                <?php foreach ($teachers as $id => $name): ?>
                    <!-- Опция с выбранным состоянием, если фильтр совпадает -->
                    <option value="<?= $id ?>" <?= ($filterTeacherId === $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <!-- Кнопка отправки формы для применения фильтров -->
        <button type="submit">Фильтровать</button>
    </form>
    <!-- Если расписание пустое, выводим сообщение -->
    <?php if (empty($schedule)): ?>
        <p>Записи отсутствуют.</p>
    <?php else: ?>
        <!-- Таблица с расписанием -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Группа</th>
                    <th>Предмет</th>
                    <th>Преподаватель</th>
                    <th>Тип занятия</th>
                    <th>Временной слот</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <!-- Перебор записей расписания -->
                <?php foreach ($schedule as $row): ?>
                    <tr data-id="<?= $row['id'] ?>">
                        <!-- Отображение ID записи -->
                        <td><?= $row['id'] ?></td>
                        <!-- Дата занятия с возможностью редактирования -->
                        <td class="editable" data-field="date" data-value="<?= htmlspecialchars($row['date']) ?>">
                            <?= htmlspecialchars($row['date']) ?></td>
                        <!-- Название группы с редактируемым значением id -->
                        <td class="editable" data-field="group_id" data-value="<?= $row['group_id'] ?>">
                            <?= htmlspecialchars($row['group_name']) ?></td>
                        <!-- Название предмета с редактируемым значением id -->
                        <td class="editable" data-field="subject_id" data-value="<?= $row['subject_id'] ?>">
                            <?= htmlspecialchars($row['subject_name']) ?></td>
                        <!-- Имя преподавателя с редактируемым значением id -->
                        <td class="editable" data-field="teacher_id" data-value="<?= $row['teacher_id'] ?>">
                            <?= htmlspecialchars($row['teacher_name']) ?></td>
                        <!-- Тип занятия с редактируемым значением id -->
                        <td class="editable" data-field="lesson_type_id" data-value="<?= $row['lesson_type_id'] ?>">
                            <?= htmlspecialchars($row['lesson_type_name']) ?></td>
                        <!-- Временной слот с редактируемым значением id -->
                        <td class="editable" data-field="time_slot_id" data-value="<?= $row['time_slot_id'] ?>">
                            <?= htmlspecialchars($row['time_slot_label']) ?></td>
                        <!-- Колонка с действиями: кнопки редактирования и удаления -->
                        <td>
                            <!-- Кнопка для переключения режима редактирования -->
                            <button class="edit-btn">Редактировать</button>
                            <!-- Форма для удаления записи с подтверждением -->
                            <form method="POST" onsubmit="return confirm('Удалить запись ID <?= $row['id'] ?>?');"
                                style="display:inline;">
                                <!-- Скрытое поле с id записи для удаления -->
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                <!-- Кнопка удаления -->
                                <button type="submit" class="delete-btn">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <script>
        // Передача списков для выпадающих списков в JS
        const lists = {
            group_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label]; }, array_keys($groups), $groups), JSON_UNESCAPED_UNICODE) ?>,
            subject_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label]; }, array_keys($subjects), $subjects), JSON_UNESCAPED_UNICODE) ?>,
            teacher_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label]; }, array_keys($teachers), $teachers), JSON_UNESCAPED_UNICODE) ?>,
            lesson_type_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label]; }, array_keys($lessonTypes), $lessonTypes), JSON_UNESCAPED_UNICODE) ?>,
            time_slot_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label]; }, array_keys($timeSlots), $timeSlots), JSON_UNESCAPED_UNICODE) ?>
        };
    </script>
<script>
    // Ждём полной загрузки DOM, чтобы элементы были доступны для скрипта
    document.addEventListener('DOMContentLoaded', () => {
        // Получаем все строки таблицы расписания
        const rows = document.querySelectorAll('tbody tr');
        // Для каждой строки добавляем обработчик на кнопку "Редактировать"
        rows.forEach(row => {
            const editBtn = row.querySelector('.edit-btn');
            editBtn.addEventListener('click', () => {
                // Если кнопка в состоянии "Редактировать" — переключаем ячейки в режим редактирования
                if (editBtn.textContent === 'Редактировать') {
                    // Проходим по всем редактируемым ячейкам в строке
                    row.querySelectorAll('td.editable').forEach(cell => {
                        const field = cell.dataset.field;       // Имя поля, например, 'date' или 'teacher_id'
                        const currentValue = cell.dataset.value; // Текущее значение поля
                        let editor; // Элемент для редактирования
                        // Для поля даты создаём input с типом date
                        if (field === 'date') {
                            editor = document.createElement('input');
                            editor.type = 'date';
                            editor.value = currentValue;
                            editor.style.width = '100%';
                        }
                        // Для справочников (списки) создаём select с опциями из объекта lists
                        else if (lists[field]) {
                            editor = document.createElement('select');
                            editor.style.width = '100%';
                            lists[field].forEach(item => {
                                const option = document.createElement('option');
                                option.value = item.id;
                                option.textContent = item.label;
                                if (item.id == currentValue) option.selected = true;
                                editor.appendChild(option);
                            });
                        }
                        // Для остальных полей создаём обычный текстовый input
                        else {
                            editor = document.createElement('input');
                            editor.type = 'text';
                            editor.value = currentValue;
                            editor.style.width = '100%';
                        }
                        // Очищаем содержимое ячейки и вставляем элемент редактирования
                        cell.textContent = '';
                        cell.appendChild(editor);
                    });
                    // Меняем текст кнопки на "Сохранить"
                    editBtn.textContent = 'Сохранить';
                    // Если кнопка "Отмена" ещё не добавлена, создаём её
                    if (!row.querySelector('.cancel-btn')) {
                        const cancelBtn = document.createElement('button');
                        cancelBtn.textContent = 'Отмена';
                        cancelBtn.classList.add('cancel-btn');
                        cancelBtn.style.marginLeft = '8px';
                        // Вставляем кнопку "Отмена" после кнопки "Сохранить"
                        editBtn.insertAdjacentElement('afterend', cancelBtn);
                        // Обработчик кнопки "Отмена" — отменяет редактирование
                        cancelBtn.addEventListener('click', () => {
                            // Восстанавливаем исходные значения ячеек
                            row.querySelectorAll('td.editable').forEach(cell => {
                                const field = cell.dataset.field;
                                const value = cell.dataset.value;
                                if (lists[field]) {
                                    // Если поле связано со списком, показываем метку из списка
                                    const item = lists[field].find(i => i.id == value);
                                    cell.textContent = item ? item.label : value;
                                } else {
                                    // Иначе просто показываем значение
                                    cell.textContent = value;
                                }
                            });
                            // Меняем кнопку обратно на "Редактировать"
                            editBtn.textContent = 'Редактировать';
                            // Удаляем кнопку "Отмена"
                            cancelBtn.remove();
                        });
                    }
                }
                // Если кнопка в состоянии "Сохранить" — сохраняем изменения
                else if (editBtn.textContent === 'Сохранить') {
                    const id = row.dataset.id; // Получаем ID записи
                    const updates = [];        // Массив изменений
                    let hasChanges = false;    // Флаг, есть ли изменения
                    // Проходим по редактируемым ячейкам, сравниваем новые значения с исходными
                    row.querySelectorAll('td.editable').forEach(cell => {
                        const field = cell.dataset.field;
                        const editor = cell.querySelector('input, select');
                        if (!editor) return;
                        let newValue = editor.value.trim();
                        if (newValue !== cell.dataset.value) {
                            hasChanges = true;
                            updates.push({ field, value: newValue });
                        }
                    });
                    // Если изменений нет — предупреждаем пользователя и выходим
                    if (!hasChanges) {
                        alert('Нет изменений для сохранения.');
                        return;
                    }
                    // Функция для последовательного обновления каждого изменённого поля через AJAX
                    const updateField = (index) => {
                        if (index >= updates.length) {
                            // После успешного обновления всех полей обновляем отображение ячеек
                            updates.forEach(update => {
                                const cell = row.querySelector(`td[data-field="${update.field}"]`);
                                if (lists[update.field]) {
                                    const item = lists[update.field].find(i => i.id == update.value);
                                    cell.textContent = item ? item.label : update.value;
                                } else {
                                    cell.textContent = update.value;
                                }
                                // Обновляем data-атрибут с новым значением
                                cell.dataset.value = update.value;
                            });
                            // Возвращаем кнопку в состояние "Редактировать"
                            row.querySelector('.edit-btn').textContent = 'Редактировать';
                            // Удаляем кнопку "Отмена"
                            const cancelBtn = row.querySelector('.cancel-btn');
                            if (cancelBtn) cancelBtn.remove();
                            alert('Изменения сохранены.');
                            return;
                        }
                        // Отправляем AJAX-запрос для обновления одного поля
                        const upd = updates[index];
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', '', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function () {
                            if (xhr.status === 200) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.status === 'success') {
                                        // Рекурсивно вызываем для следующего поля
                                        updateField(index + 1);
                                    } else {
                                        alert('Ошибка: ' + response.message);
                                    }
                                } catch {
                                    alert('Ошибка ответа сервера');
                                }
                            } else {
                                alert('Ошибка сети: ' + xhr.status);
                            }
                        };
                        // Отправляем параметры: id записи, поле и новое значение
                        xhr.send(`edit_id=${encodeURIComponent(id)}&field=${encodeURIComponent(upd.field)}&value=${encodeURIComponent(upd.value)}`);
                    };
                    // Запускаем обновление с первого изменённого поля
                    updateField(0);
                }
            });
        });
    });
</script>
</body>
</html>
