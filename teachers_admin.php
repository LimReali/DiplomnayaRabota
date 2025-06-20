<?php
// Запускаем сессию для проверки авторизации администратора
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
// Получаем ID выбранного преподавателя из GET-параметров, если нет — 0
$teacherId = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
// Если преподаватель не выбран, перенаправляем на страницу выбора преподавателя
if (!$teacherId) {
    header('Location: admin_teachers_select.php');
    exit();
}
// Инициализируем переменные для сообщений об ошибках и успехах
$error = '';
$success = '';
// Обработка удаления записи из расписания (POST-запрос с параметром delete_id)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $userId = intval($_SESSION['user_id']);
    $conn->query("SET @current_user_id = $userId");
    $deleteId = intval($_POST['delete_id']);
    // Подготавливаем запрос на удаление записи с указанным id и преподавателем
    $stmt = $conn->prepare("DELETE FROM schedule WHERE id = ? AND teacher_id = ?");
    if (!$stmt) {
        $error = "Ошибка подготовки запроса удаления: " . $conn->error;
    } else {
        $stmt->bind_param("ii", $deleteId, $teacherId);
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
    $userId = intval($_SESSION['user_id']);
    $conn->query("SET @current_user_id = $userId");
    // Устанавливаем заголовок для JSON-ответа
    header('Content-Type: application/json; charset=utf-8');
    $editId = intval($_POST['edit_id']);
    $field = $_POST['field'] ?? '';
    $value = $_POST['value'] ?? '';
    // Разрешенные поля для обновления
    $allowedFields = ['date', 'group_id', 'subject_id', 'room_id', 'lesson_type_id', 'time_slot_id'];
    if (!in_array($field, $allowedFields)) {
        echo json_encode(['status' => 'error', 'message' => 'Недопустимое поле']);
        exit();
    }
    // Подготавливаем запрос на обновление указанного поля
    $sql = "UPDATE schedule SET $field = ? WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка подготовки запроса']);
        exit();
    }
    // Привязываем параметры (тип s — string, i — integer)
    $stmt->bind_param("sii", $value, $editId, $teacherId);
    // Выполняем запрос и возвращаем JSON-ответ с результатом
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка обновления: ' . $stmt->error]);
    }
    $stmt->close();
    exit();
}
// Получаем имя преподавателя для отображения
$stmt = $conn->prepare("SELECT full_name FROM teachers WHERE id = ?");
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $teacherName = $row['full_name'];
} else {
    die("Преподаватель не найден");
}
$stmt->close();
// Получаем фильтры из GET-параметров
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$filterGroupId = isset($_GET['group_id']) && is_numeric($_GET['group_id']) ? intval($_GET['group_id']) : null;
$filterRoomId = isset($_GET['room_id']) && is_numeric($_GET['room_id']) ? intval($_GET['room_id']) : null;
// Формируем условия WHERE и параметры для SQL-запроса
$where = ["s.teacher_id = ?"]; // Обязательное условие по преподавателю
$params = [$teacherId];         // Параметры для привязки
$types = "i";                   // Типы параметров (i — integer)
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
if ($filterRoomId !== null) {
    $where[] = "s.room_id = ?";
    $params[] = $filterRoomId;
    $types .= "i";
}
// Формируем условие WHERE с помощью AND
$whereSql = implode(" AND ", $where);
// Проверяем, что условие WHERE не пустое (без преподавателя запрос не имеет смысла)
if (empty($whereSql)) {
    die("Ошибка: условие WHERE пустое. Невозможно сформировать запрос.");
}
// Формируем SQL-запрос с JOIN для получения расписания с дополнительной информацией
$sql = "
SELECT 
    s.id,
    s.date,
    s.group_id,
    g.name AS group_name,
    s.subject_id,
    sub.name AS subject_name,
    s.room_id,
    r.number AS room_number,
    s.lesson_type_id,
    lt.name AS lesson_type_name,
    s.time_slot_id,
    ts.label AS time_slot_label
FROM schedule s
LEFT JOIN `groups` g ON s.group_id = g.id
LEFT JOIN subjects sub ON s.subject_id = sub.id
LEFT JOIN rooms r ON s.room_id = r.id
LEFT JOIN lesson_types lt ON s.lesson_type_id = lt.id
LEFT JOIN time_slots ts ON s.time_slot_id = ts.id
WHERE $whereSql
ORDER BY s.date, s.time_slot_id
";
// Подготавливаем запрос
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
// Связываем параметры динамически с передачей по ссылке (требуется для call_user_func_array)
$bindParams = [];
$bindParams[] = &$types;
foreach ($params as $key => &$param) {
    $bindParams[] = &$param;
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);
// Выполняем запрос
$stmt->execute();
// Получаем результат
$result = $stmt->get_result();
// Формируем массив расписания для удобного отображения
$schedule = [];
while ($row = $result->fetch_assoc()) {
    $schedule[] = $row;
}
// Функция для загрузки справочников (группы, предметы, кабинеты и т.д.)
function loadList($conn, $table, $idField, $nameField)
{
    $list = [];
    // Экранируем имя таблицы обратными кавычками для безопасности
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
// Загружаем справочники для фильтров и отображения
$groups = loadList($conn, 'groups', 'id', 'name');
$subjects = loadList($conn, 'subjects', 'id', 'name');
$rooms = loadList($conn, 'rooms', 'id', 'number');
$lessonTypes = loadList($conn, 'lesson_types', 'id', 'name');
$timeSlots = loadList($conn, 'time_slots', 'id', 'label');
// Закрываем соединение с базой данных
$conn->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <!-- Заголовок страницы с динамическим именем преподавателя -->
    <title>Редактирование расписания преподавателя <?= htmlspecialchars($teacherName) ?></title>
    <!-- Мета-тег для адаптивного отображения на мобильных устройствах -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Подключение стилей боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
    <style>
        /* --- Общие стили для тела страницы --- */
        body {
            font-family: Arial, sans-serif;
            /* Шрифт без засечек */
            max-width: 900px;
            /* Максимальная ширина содержимого */
            margin: 30px auto;
            /* Центрирование страницы с отступом сверху и снизу */
        }
        /* Заголовок первого уровня */
        h1 {
            margin-bottom: 20px;
            /* Отступ снизу */
        }
        /* Стили таблицы */
        table {
            border-collapse: collapse;
            /* Убираем двойные границы между ячейками */
            width: 100%;
            /* Таблица занимает всю ширину контейнера */
        }
        /* Стили ячеек таблицы */
        th,
        td {
            border: 1px solid #ccc;
            /* Светло-серая рамка */
            padding: 8px;
            /* Внутренние отступы */
            text-align: center;
            /* Выравнивание текста по центру */
        }
        /* Стили заголовков таблицы */
        th {
            background-color: #3498db;
            /* Синий фон */
            color: white;
            /* Белый текст */
        }
        /* Подсветка редактируемых ячеек при наведении */
        td.editable:hover {
            background-color: #f0f8ff;
            /* Светло-голубой фон */
            cursor: pointer;
            /* Курсор в виде руки */
        }
        /* Общие стили для кнопок редактирования, отмены и удаления */
        button.edit-btn,
        button.cancel-btn,
        button.delete-btn {
            background-color: #3498db;
            /* Синий фон */
            border: none;
            /* Без рамки */
            color: white;
            /* Белый текст */
            padding: 6px 14px;
            /* Внутренние отступы */
            border-radius: 5px;
            /* Скругленные углы */
            cursor: pointer;
            /* Курсор в виде руки */
            font-weight: 600;
            /* Жирный текст */
            font-size: 14px;
            /* Размер шрифта */
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
            /* Плавные переходы */
            box-shadow: 0 2px 5px rgba(52, 152, 219, 0.4);
            /* Тень */
            user-select: none;
            /* Запрет выделения текста */
            margin-bottom: 8px;
            /* Отступ снизу */
        }
        /* Эффекты при наведении на кнопки */
        button.edit-btn:hover,
        button.cancel-btn:hover,
        button.delete-btn:hover {
            background-color: #2980b9;
            /* Темно-синий фон */
            box-shadow: 0 4px 8px rgba(41, 128, 185, 0.6);
            /* Более яркая тень */
        }
        /* Специфические стили для кнопки отмены */
        button.cancel-btn {
            background-color: #e74c3c;
            /* Красный фон */
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.4);
            /* Тень красного цвета */
            margin-left: 8px;
            /* Отступ слева */
        }
        /* Эффект при наведении на кнопку отмены */
        button.cancel-btn:hover {
            background-color: #c0392b;
            /* Темно-красный фон */
            box-shadow: 0 4px 8px rgba(192, 57, 43, 0.6);
            /* Более яркая тень */
        }
        /* Специфические стили для кнопки удаления */
        button.delete-btn {
            background-color: #e74c3c;
            /* Красный фон */
            box-shadow: 0 2px 5px rgba(231, 76, 60, 0.4);
        }
        /* Стили для кнопки "Назад" */
        a.btn-back {
            display: inline-block;
            margin-bottom: 20px;
            background-color: #3498db;
            /* Синий фон */
            color: white;
            /* Белый текст */
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        /* Эффект при наведении на кнопку "Назад" */
        a.btn-back:hover {
            background-color: #2980b9;
            /* Темно-синий фон */
        }
        /* Стили для формы фильтрации */
        form.filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        /* Метки в форме фильтрации */
        form.filter-form label {
            display: flex;
            flex-direction: column;
            font-weight: bold;
            font-size: 14px;
        }
        /* Поля ввода и селекты в форме */
        form.filter-form input,
        form.filter-form select {
            padding: 6px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
            min-width: 150px;
        }
        /* Кнопка отправки формы */
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
    <!-- Кнопка "Назад" для возврата к выбору преподавателя -->
    <a href="admin_teachers_select.php" class="btn-back">← Назад к выбору преподавателя</a>
    <!-- Заголовок страницы с динамическим именем преподавателя -->
    <h1>Редактирование расписания преподавателя: <?= htmlspecialchars($teacherName) ?></h1>
    <!-- Вывод сообщений об ошибках или успехах -->
    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <!-- Форма фильтрации расписания -->
    <form method="GET" class="filter-form" action="teachers_admin.php">
        <!-- Скрытое поле с ID преподавателя для сохранения контекста -->
        <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($teacherId) ?>">
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
        <!-- Фильтр по группе -->
        <label>
            Группа:
            <select name="group_id">
                <option value="">Все</option>
                <?php foreach ($groups as $id => $name): ?>
                    <option value="<?= $id ?>" <?= ($filterGroupId === $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <!-- Фильтр по кабинету -->
        <label>
            Кабинет:
            <select name="room_id">
                <option value="">Все</option>
                <?php foreach ($rooms as $id => $number): ?>
                    <option value="<?= $id ?>" <?= ($filterRoomId === $id) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($number) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <!-- Кнопка отправки формы фильтрации -->
        <button type="submit">Фильтровать</button>
    </form>
    <!-- Проверка наличия записей расписания -->
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
                    <th>Кабинет</th>
                    <th>Тип занятия</th>
                    <th>Временной слот</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <!-- Перебираем все записи расписания -->
                <?php foreach ($schedule as $row): ?>
                    <tr data-id="<?= $row['id'] ?>">
                        <!-- ID записи -->
                        <td><?= $row['id'] ?></td>
                        <!-- Редактируемая ячейка с датой -->
                        <td class="editable" data-field="date" data-value="<?= htmlspecialchars($row['date']) ?>">
                            <?= htmlspecialchars($row['date']) ?>
                        </td>
                        <!-- Редактируемая ячейка с группой -->
                        <td class="editable" data-field="group_id" data-value="<?= $row['group_id'] ?>">
                            <?= htmlspecialchars($row['group_name']) ?>
                        </td>
                        <!-- Редактируемая ячейка с предметом -->
                        <td class="editable" data-field="subject_id" data-value="<?= $row['subject_id'] ?>">
                            <?= htmlspecialchars($row['subject_name']) ?>
                        </td>
                        <!-- Редактируемая ячейка с кабинетом -->
                        <td class="editable" data-field="room_id" data-value="<?= $row['room_id'] ?>">
                            <?= htmlspecialchars($row['room_number']) ?>
                        </td>
                        <!-- Редактируемая ячейка с типом занятия -->
                        <td class="editable" data-field="lesson_type_id" data-value="<?= $row['lesson_type_id'] ?>">
                            <?= htmlspecialchars($row['lesson_type_name']) ?>
                        </td>
                        <!-- Редактируемая ячейка с временным слотом -->
                        <td class="editable" data-field="time_slot_id" data-value="<?= $row['time_slot_id'] ?>">
                            <?= htmlspecialchars($row['time_slot_label']) ?>
                        </td>
                        <!-- Колонка с действиями: кнопка редактирования и форма удаления -->
                        <td>
                            <!-- Кнопка для переключения в режим редактирования -->
                            <button class="edit-btn">Редактировать</button>
                            <!-- Форма для удаления записи с подтверждением -->
                            <form method="POST" onsubmit="return confirm('Удалить запись ID <?= $row['id'] ?>?');"
                                style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
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
                return ['id' => $id, 'label' => $label];
            }, array_keys($groups), $groups), JSON_UNESCAPED_UNICODE) ?>,
            subject_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label];
            }, array_keys($subjects), $subjects), JSON_UNESCAPED_UNICODE) ?>,
            room_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label];
            }, array_keys($rooms), $rooms), JSON_UNESCAPED_UNICODE) ?>,
            lesson_type_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label];
            }, array_keys($lessonTypes), $lessonTypes), JSON_UNESCAPED_UNICODE) ?>,
            time_slot_id: <?= json_encode(array_map(function ($id, $label) {
                return ['id' => $id, 'label' => $label];
            }, array_keys($timeSlots), $timeSlots), JSON_UNESCAPED_UNICODE) ?>
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Получаем все строки таблицы с расписанием
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                // Находим кнопку редактирования в каждой строке
                const editBtn = row.querySelector('.edit-btn');
                // Добавляем обработчик клика на кнопку редактирования/сохранения
                editBtn.addEventListener('click', () => {
                    if (editBtn.textContent === 'Редактировать') {
                        // Если сейчас режим "Редактировать", переключаем ячейки в режим редактирования
                        row.querySelectorAll('td.editable').forEach(cell => {
                            const field = cell.dataset.field;       // Имя поля (например, group_id)
                            const currentValue = cell.dataset.value; // Текущее значение поля
                            let editor;
                            if (field === 'date') {
                                // Для даты создаём input с типом date
                                editor = document.createElement('input');
                                editor.type = 'date';
                                editor.value = currentValue;
                                editor.style.width = '100%';
                            } else if (lists[field]) {
                                // Для справочников (группы, предметы и т.п.) создаём select с опциями
                                editor = document.createElement('select');
                                editor.style.width = '100%';
                                lists[field].forEach(item => {
                                    const option = document.createElement('option');
                                    option.value = item.id;
                                    option.textContent = item.label;
                                    if (item.id == currentValue) option.selected = true;
                                    editor.appendChild(option);
                                });
                            } else {
                                // Для остальных полей создаём обычный текстовый input
                                editor = document.createElement('input');
                                editor.type = 'text';
                                editor.value = currentValue;
                                editor.style.width = '100%';
                            }
                            // Заменяем содержимое ячейки на элемент редактирования
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
                            editBtn.insertAdjacentElement('afterend', cancelBtn);
                            // Обработчик кнопки "Отмена"
                            cancelBtn.addEventListener('click', () => {
                                // Восстанавливаем исходные значения ячеек
                                row.querySelectorAll('td.editable').forEach(cell => {
                                    const field = cell.dataset.field;
                                    const value = cell.dataset.value;
                                    if (lists[field]) {
                                        const item = lists[field].find(i => i.id == value);
                                        cell.textContent = item ? item.label : value;
                                    } else {
                                        cell.textContent = value;
                                    }
                                });
                                // Возвращаем кнопку в состояние "Редактировать"
                                editBtn.textContent = 'Редактировать';
                                // Удаляем кнопку "Отмена"
                                cancelBtn.remove();
                            });
                        }
                    } else if (editBtn.textContent === 'Сохранить') {
                        // Если сейчас режим "Сохранить", собираем изменения и отправляем на сервер
                        const id = row.dataset.id; // ID записи
                        const updates = [];
                        let hasChanges = false;
                        // Проходим по редактируемым ячейкам и собираем изменения
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
                        if (!hasChanges) {
                            alert('Нет изменений для сохранения.');
                            return;
                        }
                        // Рекурсивная функция для последовательного обновления каждого изменённого поля через AJAX
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
                                    cell.dataset.value = update.value; // Обновляем data-атрибут
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
                            xhr.open('POST', '', true); // Отправляем на тот же URL
                            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    try {
                                        const response = JSON.parse(xhr.responseText);
                                        if (response.status === 'success') {
                                            // Рекурсивно обновляем следующий
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
                        // Запускаем обновление с первого поля
                        updateField(0);
                    }
                });
            });
        });
    </script>
</body>
</html>