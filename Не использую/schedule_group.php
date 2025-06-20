<?php
// Подключение к базе данных MySQL с помощью mysqli
$host = 'localhost';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
// Создаем соединение с базой данных
$conn = new mysqli($host, $user, $password, $dbname);
// Проверяем успешность подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем дату начала недели из GET-параметра weekInput в формате 'ГГГГ-WWW' (ISO-8601 год-неделя)
// Если параметр отсутствует или некорректен, пытаемся получить дату из weekStart (формат 'ГГГГ-ММ-ДД')
// Если и этот параметр отсутствует или некорректен — используем текущую дату
if (isset($_GET['weekInput']) && preg_match('/^\d{4}-W\d{2}$/', $_GET['weekInput'])) {
    $inputDate = DateTime::createFromFormat('o-\WW', $_GET['weekInput']);
    if (!$inputDate) {
        $inputDate = new DateTime(); // текущая дата
    }
} elseif (isset($_GET['weekStart'])) {
    $inputDate = DateTime::createFromFormat('Y-m-d', $_GET['weekStart']);
    if (!$inputDate) {
        $inputDate = new DateTime(); // текущая дата
    }
} else {
    $inputDate = new DateTime(); // текущая дата
}
// Находим понедельник выбранной недели (день недели: 1 — понедельник, 7 — воскресенье)
$dayOfWeek = (int) $inputDate->format('N');
$weekStartDate = clone $inputDate;
$weekStartDate->modify('-' . ($dayOfWeek - 1) . ' days'); // сдвигаем дату к понедельнику
$weekStart = $weekStartDate->format('Y-m-d');
// Находим воскресенье выбранной недели (понедельник + 6 дней)
$weekEndDate = clone $weekStartDate;
$weekEndDate->modify('+6 days');
$weekEnd = $weekEndDate->format('Y-m-d');
// Кнопки переключения недель: предыдущая и следующая
$prevWeek = clone $weekStartDate;
$prevWeek->modify('-7 days');
$nextWeek = clone $weekStartDate;
$nextWeek->modify('+7 days');
// Вычисляем понедельник текущей недели (для ссылки "Текущая неделя")
$today = new DateTime();
$dayOfWeekToday = (int) $today->format('N');
$currentWeekMonday = clone $today;
$currentWeekMonday->modify('-' . ($dayOfWeekToday - 1) . ' days');
$currentWeekMondayStr = $currentWeekMonday->format('Y-m-d');
// Получаем список групп из базы для выпадающего списка фильтрации
$groups = [];
$resultGroups = $conn->query("SELECT id, name FROM `groups` ORDER BY name");
if ($resultGroups) {
    while ($row = $resultGroups->fetch_assoc()) {
        $groups[$row['id']] = $row['name'];
    }
} else {
    die("Ошибка получения групп: " . $conn->error);
}
// Получаем выбранную группу из GET-параметров, если она есть и корректна
$filterGroupId = isset($_GET['group_id']) && is_numeric($_GET['group_id']) ? (int) $_GET['group_id'] : null;
// Функция для нормализации временных меток (удаляет пробелы и приводит к нижнему регистру)
function normalizeTimeLabel($label)
{
    return preg_replace('/\s+/', '', mb_strtolower($label));
}
// Получаем временные слоты из базы (id => label), сортируем по времени начала
$timeSlots = [];
$sqlTimeSlots = "SELECT id, label FROM time_slots ORDER BY start_time";
$resultTimeSlots = $conn->query($sqlTimeSlots);
if ($resultTimeSlots) {
    while ($row = $resultTimeSlots->fetch_assoc()) {
        $timeSlots[$row['id']] = $row['label'];
    }
} else {
    die("Ошибка получения временных слотов: " . $conn->error);
}
// Формируем массив дат недели от понедельника до воскресенья включительно
$dates = [];
$current = clone $weekStartDate;
$end = clone $weekEndDate;
while ($current <= $end) {
    $dates[] = $current->format('Y-m-d');
    $current->modify('+1 day');
}
// Формируем SQL-запрос для получения расписания с JOIN по связанным таблицам
$sql = "SELECT 
            s.date, 
            s.time_slot_id,
            ts.label AS time_label,
            g.name AS group_name,
            t.full_name AS teacher_name,
            sub.name AS subject_name,
            r.number AS room_number,
            lt.name AS lesson_type_name
        FROM schedule s
        JOIN `groups` g ON s.group_id = g.id
        JOIN teachers t ON s.teacher_id = t.id
        JOIN subjects sub ON s.subject_id = sub.id
        JOIN rooms r ON s.room_id = r.id
        JOIN time_slots ts ON s.time_slot_id = ts.id
        LEFT JOIN lesson_types lt ON s.lesson_type_id = lt.id
        WHERE s.date BETWEEN ? AND ?";
$params = [$weekStart, $weekEnd];
$types = "ss"; // два параметра типа string (даты)
// Добавляем фильтр по группе, если выбран
if ($filterGroupId) {
    $sql .= " AND s.group_id = ?";
    $types .= "i"; // integer
    $params[] = $filterGroupId;
}
$sql .= " ORDER BY s.date, ts.start_time";
// Подготавливаем запрос
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
// Связываем параметры с запросом динамически
$stmt->bind_param($types, ...$params);
// Выполняем запрос
$stmt->execute();
// Получаем результат
$result = $stmt->get_result();
// Формируем многомерный массив расписания: дата -> временной слот -> массив занятий
$schedule = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    $slotId = $row['time_slot_id'];
    if (!isset($schedule[$date][$slotId])) {
        $schedule[$date][$slotId] = [];
    }
    $schedule[$date][$slotId][] = $row;
}
// Закрываем подготовленный запрос и соединение
$stmt->close();
$conn->close();
/**
 * Форматирует дату в удобочитаемый вид с русскими сокращениями дней недели
 * @param string $date дата в формате Y-m-d
 * @return string форматированная дата, например "Пн 01.01.2024"
 */
function formatDate($date)
{
    $d = new DateTime($date);
    $days = [
        'Mon' => 'Пн',
        'Tue' => 'Вт',
        'Wed' => 'Ср',
        'Thu' => 'Чт',
        'Fri' => 'Пт',
        'Sat' => 'Сб',
        'Sun' => 'Вс'
    ];
    $dow = $d->format('D');
    return ($days[$dow] ?? $dow) . ' ' . $d->format('d.m.Y');
}
/**
 * Возвращает цвет для типа занятия по названию
 * @param string $typeName название типа занятия
 * @return string цвет в HEX формате
 */
function getLessonColor($typeName)
{
    $colors = [
        'вид нагрузки не определен' => '#999999',  // серый
        'лабораторное занятие' => '#FF9800',       // оранжевый
        'лекционное занятие' => '#4CAF50',         // зелёный
        'практическое занятие' => '#2196F3',       // синий
        'факультатив' => '#9C27B0',                 // фиолетовый
        'экзамен' => '#F44336',                      // красный
    ];
    $key = mb_strtolower(trim($typeName));
    return $colors[$key] ?? '#999999'; // серый по умолчанию
}
// Массив цветов для легенды расписания (дублирует getLessonColor, но с заглавными буквами)
$lessonTypesColors = [
    'Вид нагрузки не определен' => '#999999',
    'Лабораторное занятие'      => '#FF9800',
    'Лекционное занятие'        => '#4CAF50',
    'Практическое занятие'      => '#2196F3',
    'Факультатив'               => '#9C27B0',
    'Экзамен'                   => '#F44336',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <!-- Мета-тег для адаптивного отображения на мобильных устройствах -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Расписание на неделю</title>
    <style>
        /* Стили для таблицы расписания */
        table {
            border-collapse: collapse; /* Убираем двойные границы между ячейками */
            width: 100%; /* Таблица занимает всю ширину контейнера */
        }
        th,
        td {
            border: 1px solid #ccc; /* Светло-серая рамка */
            padding: 8px; /* Внутренние отступы */
            text-align: center; /* Выравнивание текста по центру */
            vertical-align: top; /* Выравнивание содержимого по верхнему краю */
        }
        th {
            background-color: #f0f0f0; /* Светлый фон заголовков */
        }
        /* Стили для пустых ячеек */
        td.empty {
            background-color: #fafafa; /* Очень светлый фон */
            color: #999; /* Серый цвет текста */
        }
        /* Контейнер для кнопок навигации по неделям */
        .nav-buttons {
            margin-bottom: 20px; /* Отступ снизу */
        }
        /* Стили для кнопок навигации */
        .nav-buttons button {
            padding: 8px 16px; /* Внутренние отступы */
            margin-right: 10px; /* Отступ справа */
            font-size: 16px; /* Размер шрифта */
            cursor: pointer; /* Курсор в виде руки */
        }
        /* Блок с информацией о занятии */
        .lesson-block {
            position: relative;
            padding-left: 14px; /* Отступ слева для цветной полоски */
            margin-bottom: 8px; /* Отступ снизу */
            text-align: left; /* Выравнивание текста по левому краю */
        }
        /* Цветная полоска слева для обозначения типа занятия */
        .lesson-color-bar {
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 6px;
            border-radius: 3px; /* Скругленные углы полоски */
        }
        /* Горизонтальная линия для разделения занятий */
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 8px 0;
        }
        /* Стили для формы фильтрации по группе */
        form.group-filter {
            margin-bottom: 20px; /* Отступ снизу */
        }
        /* Выделение текущего дня */
        .today {
            border: 2px solid orange !important; /* Оранжевая рамка для наглядности */
        }
    </style>
    <!-- Подключение иконок Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Подключение стилей боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
</head>
<body>
    <!-- Мобильная верхняя панель с кнопкой меню -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Включение боковой панели через PHP -->
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <!-- Навигационные кнопки для переключения между неделями -->
            <div class="nav-buttons">
                <a href="?weekStart=<?= htmlspecialchars($prevWeek->format('Y-m-d')) ?>">
                    <button>← Предыдущая неделя</button>
                </a>
                <a href="?weekStart=<?= htmlspecialchars($currentWeekMondayStr) ?>">
                    <button>Текущая неделя</button>
                </a>
                <a href="?weekStart=<?= htmlspecialchars($nextWeek->format('Y-m-d')) ?>">
                    <button>Следующая неделя →</button>
                </a>
            </div>
            <!-- Заголовок и фильтр по группе -->
            <h2>Расписание по группе</h2>
            <form method="GET" class="group-filter">
                <label for="groupSelect">Выберите группу:</label>
                <select name="group_id" id="groupSelect" onchange="this.form.submit()">
                    <option value="">Все группы</option>
                    <?php foreach ($groups as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($filterGroupId == $id) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Скрытое поле для сохранения выбранной недели при смене группы -->
                <input type="hidden" name="weekStart" value="<?= htmlspecialchars($weekStart) ?>">
            </form>
            <!-- Заголовок с датами недели -->
            <h1>Расписание занятий с <?= htmlspecialchars(formatDate($weekStart)) ?> по <?= htmlspecialchars(formatDate($weekEnd)) ?></h1>
            <!-- Таблица расписания -->
            <table>
                <thead>
                    <tr>
                        <th>Время / Дата</th>
                        <!-- Заголовки с датами недели -->
                        <?php foreach ($dates as $date): ?>
                            <th><?= htmlspecialchars(formatDate($date)) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Перебор временных слотов -->
                    <?php foreach ($timeSlots as $slotId => $timeLabel): ?>
                        <tr>
                            <td><?= htmlspecialchars($timeLabel) ?></td>
                            <!-- Перебор дат недели -->
                            <?php foreach ($dates as $date): ?>
                                <?php if (!empty($schedule[$date][$slotId])): ?>
                                    <td>
                                        <!-- Перебор занятий для конкретной даты и временного слота -->
                                        <?php foreach ($schedule[$date][$slotId] as $lesson): ?>
                                            <div class="lesson-block">
                                                <!-- Цветная полоска, обозначающая тип занятия -->
                                                <span class="lesson-color-bar" style="background-color: <?= getLessonColor($lesson['lesson_type_name']) ?>;"></span>
                                                <strong>Предмет:</strong> <?= htmlspecialchars($lesson['subject_name']) ?><br>
                                                <strong>Группа:</strong> <?= htmlspecialchars($lesson['group_name']) ?><br>
                                                <strong>Преподаватель:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?><br>
                                                <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?>
                                            </div>
                                            <hr>
                                        <?php endforeach; ?>
                                    </td>
                                <?php else: ?>
                                    <!-- Пустая ячейка, если занятий нет -->
                                    <td class="empty">-</td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    <!-- Подключение скрипта для боковой панели -->
    <script src="sidebar.js"></script>
</body>
</html>
