<?php
// Подключение к базе данных MySQL с помощью mysqli
$host = 'MySQL-8.0';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
// Создаем соединение с базой данных
$conn = new mysqli($host, $user, $password, $dbname);
// Проверяем успешность подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем ID выбранного преподавателя из GET-параметров, если нет — 0
$selectedTeacherId = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;
// Получаем дату начала недели из GET-параметра weekInput в формате 'ГГГГ-WWW' (ISO-8601)
// Если параметр отсутствует или некорректен, пытаемся получить дату из weekStart (формат 'ГГГГ-ММ-ДД')
// Если и этот параметр отсутствует или некорректен — используем текущую дату
if (isset($_GET['weekInput']) && preg_match('/^\d{4}-W\d{2}$/', $_GET['weekInput'])) {
    $inputDate = DateTime::createFromFormat('o-\WW', $_GET['weekInput']);
    if (!$inputDate) {
        $inputDate = new DateTime();
    }
} elseif (isset($_GET['weekStart'])) {
    $inputDate = DateTime::createFromFormat('Y-m-d', $_GET['weekStart']);
    if (!$inputDate) {
        $inputDate = new DateTime();
    }
} else {
    $inputDate = new DateTime();
}
// Получаем список всех преподавателей для фильтрации и отображения
$teachers = [];
$sqlTeachers = "SELECT id, full_name FROM `teachers` ORDER BY full_name ASC";
$resultTeachers = $conn->query($sqlTeachers);
if ($resultTeachers) {
    while ($row = $resultTeachers->fetch_assoc()) {
        $teachers[$row['id']] = $row['full_name'];
    }
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
// Текущая дата и понедельник текущей недели (для ссылки "Текущая неделя")
$todayDate = (new DateTime())->format('Y-m-d');
$today = new DateTime();
$dayOfWeekToday = (int) $today->format('N');
$currentWeekMonday = clone $today;
$currentWeekMonday->modify('-' . ($dayOfWeekToday - 1) . ' days');
$currentWeekMondayStr = $currentWeekMonday->format('Y-m-d');
// Получаем временные слоты (id => label) из базы, сортируем по времени начала
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
// Формируем SQL-запрос с JOIN для получения расписания выбранного преподавателя за выбранную неделю
$sql = "SELECT 
    s.date, 
    s.time_slot_id,
    ts.label AS time_label,
    g.name AS group_name,
    t.full_name AS teacher_name,
    sub.name AS subject_name,
    r.number AS room_number,
    r.building AS room_building,  -- добавлено поле корпуса
    lt.name AS lesson_type_name
FROM schedule s
JOIN `groups` g ON s.group_id = g.id
JOIN teachers t ON s.teacher_id = t.id
JOIN subjects sub ON s.subject_id = sub.id
JOIN rooms r ON s.room_id = r.id
JOIN time_slots ts ON s.time_slot_id = ts.id
LEFT JOIN lesson_types lt ON s.lesson_type_id = lt.id
WHERE s.date BETWEEN ? AND ?
";
$params = [$weekStart, $weekEnd];
$types = 'ss'; // два параметра string (даты)
// Если выбран преподаватель, добавляем фильтр по нему
if ($selectedTeacherId) {
    $sql .= " AND s.teacher_id = ?";
    $params[] = $selectedTeacherId;
    $types .= 'i'; // integer
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
 * Функция форматирования даты в удобочитаемый вид с русскими сокращениями дней недели
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
 * Функция определения цвета для типа занятия по названию
 * @param string $typeName название типа занятия
 * @return string цвет в HEX формате
 */
function getLessonColor($typeName)
{
    $colors = [
        'вид нагрузки не определен' => '#999999',
        'лабораторное занятие' => '#FF9800',
        'лекционное занятие' => '#4CAF50',
        'практическое занятие' => '#2196F3',
        'факультатив' => '#9C27B0',
        'экзамен' => '#F44336',
    ];
    $key = mb_strtolower(trim($typeName));
    return $colors[$key] ?? '#999999'; // серый цвет по умолчанию
}
// Формируем значение для поля weekInput в формате ISO-8601 (например "2024-W23")
$weekInputValue = $weekStartDate->format('o-\WW');
// Определяем учебный год по дате начала недели (учебный год начинается с сентября)
$startYear = (int) $weekStartDate->format('Y');
$startMonth = (int) $weekStartDate->format('n');
if ($startMonth < 9) {
    $academicYear = ($startYear - 1) . '-' . $startYear;
} else {
    $academicYear = $startYear . '-' . ($startYear + 1);
}
// Номер недели по ISO-8601
$weekNumber = (int) $weekStartDate->format('W');
// Чётность недели (для расписания)
$weekParity = ($weekNumber % 2 === 0) ? 'чётная' : 'нечётная';
// Массив цветов для легенды расписания (дублирует getLessonColor, но с заглавными буквами)
$lessonTypesColors = [
    'Вид нагрузки не определен' => '#999999',  // серый
    'Лабораторное занятие'      => '#FF9800',  // оранжевый
    'Лекционное занятие'        => '#4CAF50',  // зелёный
    'Практическое занятие'      => '#2196F3',  // синий
    'Факультатив'               => '#9C27B0',  // фиолетовый
    'Экзамен'                   => '#F44336',  // красный
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- Подключение стилей Select2 для выпадающих списков (если потребуется) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Иконка сайта -->
    <link rel="icon" href="img.png" type="image/jpeg" />
    <!-- Стили боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
    <!-- Мета-теги для адаптивности и кодировки -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta charset="UTF-8" />
    <title>Расписание преподавателя</title>
    <style>
        /* --- Таблица расписания --- */
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
        }
        td.empty {
            background-color: #fafafa;
            color: #999;
        }
        /* --- Навигационные кнопки (переключение недель) --- */
        .nav-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 24px;
        }
        .nav-buttons a {
            text-decoration: none;
        }
        .nav-buttons button {
            background-color: #a83250;
            color: #fff;
            border: none;
            padding: 10px 18px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .nav-buttons button:hover,
        .nav-buttons button:focus {
            background-color: #7b203a;
            outline: none;
        }
        /* --- Блоки занятия внутри ячеек --- */
        .lesson-block {
            position: relative;
            padding-left: 14px;
            margin-bottom: 8px;
            text-align: left;
            background: #fff0f2;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 6px;
            box-shadow: 0 1px 4px rgba(123, 32, 58, 0.15);
        }
        .lesson-color-bar {
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 6px;
            border-radius: 3px;
            width: 5px;
            height: 100%;
            left: 0;
            top: 0;
            border-radius: 6px 0 0 6px;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 8px 0;
        }
        /* --- Текущий день --- */
        .today {
            background-color:rgb(173, 19, 43);
            transition: background-color 0.3s ease;
        }
        /* --- Переключатели вида (таблица/день) --- */
        .view-switcher {
            margin-bottom: 18px;
        }
        .switch-btn {
            background: #fff;
            color: #7b203a;
            border: 2px solid #a83250;
            border-radius: 7px 7px 0 0;
            font-weight: 600;
            font-size: 1.04rem;
            padding: 8px 22px;
            margin-right: 6px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .switch-btn.active,
        .switch-btn:hover {
            background: #a83250;
            color: #fff;
        }
        /* --- Кнопки дней недели для дневного вида --- */
        .day-btn {
            background: #fff;
            color: #7b203a;
            border: 1.5px solid #a83250;
            border-radius: 5px;
            font-weight: 500;
            font-size: 1rem;
            padding: 6px 16px;
            margin-right: 6px;
            margin-bottom: 4px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .day-btn.active,
        .day-btn:hover {
            background: #a83250;
            color: #fff;
        }
        /* --- Кнопка печати --- */
        .print-btn {
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border: 2px solid #a83250;
            border-radius: 8px;
            color: #a83250;
            font-size: 1.25rem;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
            box-shadow: 0 2px 8px rgba(123, 32, 58, 0.07);
            outline: none;
            margin-left: auto;
        }
        .print-btn:hover,
        .print-btn:focus {
            background: #a83250;
            color: #fff;
        }
        /* --- Форма выбора даты --- */
        form.date-picker-form {
            max-width: 320px;
            margin: 0 auto 40px auto;
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        form.date-picker-form label {
            font-weight: 600;
            color: #7b203a;
            font-size: 1rem;
        }
        form.date-picker-form input[type="date"] {
            padding: 8px 12px;
            border: 2px solid #a83250;
            border-radius: 6px;
            font-size: 1rem;
            color: #7b203a;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        form.date-picker-form input[type="date"]:focus {
            border-color: #7b203a;
            outline: none;
        }
        form.date-picker-form button {
            background-color: #a83250;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form.date-picker-form button:hover,
        form.date-picker-form button:focus {
            background-color: #7b203a;
            outline: none;
        }
        /* --- Обертка для скролла таблицы --- */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 700px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px 12px;
            vertical-align: top;
        }
        thead th {
            position: sticky;
            top: 0;
            background: #a83250;
            color: white;
            z-index: 10;
            padding: 10px;
            text-align: center;
        }
        /* --- Ховер по ячейкам таблицы --- */
        table td:hover,
        table th:hover {
            background-color: #fce4ec;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        /* --- Заголовок расписания --- */
        .schedule-header {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #a83250;
            text-align: center;
            margin: 30px 0 40px 0;
            font-weight: 700;
            font-size: 2.2rem;
            line-height: 1.2;
            text-shadow: 1px 1px 2px rgba(168, 50, 80, 0.3);
        }
        .schedule-header span {
            display: block;
            font-weight: 500;
            font-size: 1.25rem;
            color: #7b203a;
            margin-top: 6px;
        }
        /* --- Информационный блок --- */
        .schedule-info {
            max-width: 720px;
            margin: 0 auto 40px auto;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 1rem;
            color: #4a2c3a;
            text-align: center;
            font-weight: 600;
            line-height: 1.4;
        }
        .schedule-info span {
            margin: 0 8px;
        }
        /* --- Стилизация скроллбара таблицы --- */
        #tableView {
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            background: #fff;
            margin-bottom: 20px;
            scrollbar-width: thin;
            scrollbar-color: #a83250 #f0e6e9;
        }
        #tableView table {
            min-width: 800px;
            width: 100%;
            border-collapse: collapse;
        }
        #tableView::-webkit-scrollbar {
            height: 10px;
        }
        #tableView::-webkit-scrollbar-track {
            background: #f0e6e9;
            border-radius: 8px;
        }
        #tableView::-webkit-scrollbar-thumb {
            background-color: #a83250;
            border-radius: 8px;
            border: 2px solid #f0e6e9;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        #tableView::-webkit-scrollbar-thumb:hover {
            background-color: #7b203a;
        }
        /* --- Адаптивность для мобильных --- */
        @media (max-width: 600px) {
            .nav-buttons {
                flex-direction: column;
                gap: 14px;
                margin-bottom: 30px;
            }
            .nav-buttons button {
                width: 100%;
                padding: 14px 0;
                font-size: 1.1rem;
            }
            form.date-picker-form {
                flex-direction: column;
                max-width: 100%;
                margin-bottom: 30px;
            }
            form.date-picker-form label,
            form.date-picker-form input[type="date"],
            form.date-picker-form button {
                width: 100%;
                font-size: 1.1rem;
            }
            form.date-picker-form input[type="date"] {
                padding: 12px;
            }
            form.date-picker-form button {
                padding: 14px 0;
            }
        }
    </style>
    <!-- Подключение Font Awesome для иконок -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <!-- Мобильная верхняя панель с кнопкой меню (скрыта по умолчанию) -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Включение боковой панели из отдельного файла -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <!-- Навигационные кнопки для переключения недель -->
            <div class="nav-buttons">
                <a href="?weekStart=<?= htmlspecialchars($prevWeek->format('Y-m-d')) ?>&teacher_id=<?= $selectedTeacherId ?>">
                    <button type="button" aria-label="Предыдущая неделя">← Предыдущая неделя</button>
                </a>
                <a href="?weekStart=<?= htmlspecialchars($currentWeekMondayStr) ?>&teacher_id=<?= $selectedTeacherId ?>">
                    <button type="button" aria-label="Текущая неделя">Текущая неделя</button>
                </a>
                <a href="?weekStart=<?= htmlspecialchars($nextWeek->format('Y-m-d')) ?>&teacher_id=<?= $selectedTeacherId ?>">
                    <button type="button" aria-label="Следующая неделя">Следующая неделя →</button>
                </a>
            </div>
            <!-- Форма выбора даты с сохранением выбранного преподавателя -->
            <form method="GET" action="" class="date-picker-form">
                <label for="datePicker">Выберите дату:</label>
                <input type="date" id="datePicker" name="weekStart" value="<?= htmlspecialchars($inputDate->format('Y-m-d')) ?>" required>
                <input type="hidden" name="teacher_id" value="<?= $selectedTeacherId ?>">
                <button type="submit">Показать расписание</button>
            </form>
            <!-- Переключатель вида расписания: таблица или день -->
            <div class="view-print-wrapper" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <div class="view-switcher">
                    <button id="tableViewBtn" class="switch-btn active">Таблица</button>
                    <button id="dayViewBtn" class="switch-btn">День</button>
                </div>
                <!-- Кнопка печати расписания -->
                <button id="printBtn" class="print-btn" title="Печать" aria-label="Печать расписания">
                    <i class="fas fa-print"></i>
                </button>
            </div>
            <!-- Кнопки для выбора дня недели (скрыты по умолчанию) -->
            <div id="dayButtons" style="display:none; margin-bottom:18px;">
                <?php
                $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                foreach ($dates as $i => $date) {
                    // Первая кнопка активна по умолчанию
                    echo '<button class="day-btn' . ($i == 0 ? ' active' : '') . '" data-day="' . $i . '">' . $weekDays[$i] . '</button> ';
                }
                ?>
            </div>
            <!-- Заголовок с датами выбранной недели -->
            <h1 class="schedule-header">
                Расписание преподавателя
                <span>
                    с <?= htmlspecialchars(formatDate($weekStart)) ?> по <?= htmlspecialchars(formatDate($weekEnd)) ?>
                </span>
            </h1>
            <!-- Информационный блок с выбранным преподавателем, учебным годом и номером недели -->
            <div class="schedule-info">
                <span>Преподаватель:
                    <strong><?= htmlspecialchars($teachers[$selectedTeacherId] ?? 'не выбран') ?></strong></span>
                <span>Учебный год <?= $academicYear ?></span>
                <span><?= $weekNumber ?> неделя (<?= $weekParity ?>)</span>
            </div>
            <!-- Табличный вид расписания -->
            <div id="tableView">
                <table>
                    <thead>
                        <tr>
                            <th>Время / Дата</th>
                            <?php foreach ($dates as $date): ?>
                                <th class="<?= ($date === $todayDate) ? 'today' : '' ?>">
                                    <?= htmlspecialchars(formatDate($date)) ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timeSlots as $slotId => $timeLabel): ?>
                            <tr>
                                <td><?= htmlspecialchars($timeLabel) ?></td>
                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $isToday = ($date === $todayDate);
                                    $tdClass = $isToday ? 'today' : '';
                                    ?>
                                    <?php if (!empty($schedule[$date][$slotId])): ?>
                                        <td class="<?= $tdClass ?>">
                                            <?php foreach ($schedule[$date][$slotId] as $lesson): ?>
                                                <div class="lesson-block">
                                                    <span class="lesson-color-bar" style="background-color: <?= getLessonColor($lesson['lesson_type_name']) ?>;"></span>
                                                    <strong>Предмет:</strong> <?= htmlspecialchars($lesson['subject_name']) ?><br>
                                                    <strong>Группа:</strong> <?= htmlspecialchars($lesson['group_name']) ?><br>
                                                    <strong>Преподаватель:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?><br>
                                                    <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?> / <?= htmlspecialchars($lesson['room_building']) ?>
                                                </div>
                                                <hr>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php else: ?>
                                        <td class="empty <?= $tdClass ?>">-</td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Дневной вид расписания (скрыт по умолчанию) -->
            <div id="dayView" style="display:none;">
                <?php foreach ($dates as $i => $date): ?>
                    <div class="day-schedule" data-day="<?= $i ?>" style="<?= $i == 0 ? '' : 'display:none;' ?>">
                        <h3><?= $weekDays[$i] ?> — <?= htmlspecialchars(formatDate($date)) ?></h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Время</th>
                                    <th>Занятие</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timeSlots as $slotId => $timeLabel): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($timeLabel) ?></td>
                                        <td>
                                            <?php if (!empty($schedule[$date][$slotId])): ?>
                                                <?php foreach ($schedule[$date][$slotId] as $lesson): ?>
                                                    <div class="lesson-block">
                                                        <span class="lesson-color-bar" style="background-color: <?= getLessonColor($lesson['lesson_type_name']) ?>;"></span>
                                                        <strong>Предмет:</strong> <?= htmlspecialchars($lesson['subject_name']) ?><br>
                                                        <strong>Группа:</strong> <?= htmlspecialchars($lesson['group_name']) ?><br>
                                                        <strong>Преподаватель:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?><br>
                                                        <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?> / <?= htmlspecialchars($lesson['room_building']) ?>
                                                    </div>
                                                    <hr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="empty">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
            <!-- Легенда цветов для типов занятий -->
            <div class="legend" style="max-width:1100px; margin: 20px auto; padding: 10px 20px; font-family: Arial, sans-serif;">
                <strong>Легенда цветов:</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                    <?php foreach ($lessonTypesColors as $typeName => $color): ?>
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 14px; color: #333;">
                            <span style="display: inline-block; width: 18px; height: 18px; background-color: <?= htmlspecialchars($color) ?>; border-radius: 50%; border: 1px solid #ccc;"></span>
                            <span><?= htmlspecialchars($typeName) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="sidebar.js"></script>
<!-- Скрипт для управления переключением видов расписания (таблица/день) -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Получаем элементы управления видами и блоки расписания
        const tableViewBtn = document.getElementById('tableViewBtn');
        const dayViewBtn = document.getElementById('dayViewBtn');
        const tableView = document.getElementById('tableView');
        const dayView = document.getElementById('dayView');
        const dayButtons = document.getElementById('dayButtons');
        const dayBtnList = dayButtons.querySelectorAll('.day-btn');
        const daySchedules = dayView.querySelectorAll('.day-schedule');
        // Обработчик переключения на табличный вид
        tableViewBtn.addEventListener('click', () => {
            tableViewBtn.classList.add('active');       // Активируем кнопку "Таблица"
            dayViewBtn.classList.remove('active');      // Деактивируем кнопку "День"
            tableView.style.display = '';               // Показываем табличный вид
            dayView.style.display = 'none';             // Скрываем дневной вид
            dayButtons.style.display = 'none';          // Скрываем кнопки выбора дня
        });
        // Обработчик переключения на дневной вид
        dayViewBtn.addEventListener('click', () => {
            tableViewBtn.classList.remove('active');
            dayViewBtn.classList.add('active');
            tableView.style.display = 'none';
            dayView.style.display = '';
            dayButtons.style.display = '';
            // По умолчанию показываем понедельник (первый день)
            dayBtnList.forEach((btn, i) => {
                btn.classList.toggle('active', i === 0);
            });
            daySchedules.forEach((el, i) => {
                el.style.display = i === 0 ? '' : 'none';
            });
        });
        // Обработчики переключения между днями недели в дневном виде
        dayBtnList.forEach((btn, idx) => {
            btn.addEventListener('click', () => {
                dayBtnList.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                daySchedules.forEach((el, i) => {
                    el.style.display = i === idx ? '' : 'none';
                });
            });
        });
    });
</script>
<!-- Скрипт для печати расписания -->
<script>
    document.getElementById('printBtn').addEventListener('click', function () {
        // Находим первую таблицу на странице (если их несколько, лучше использовать id)
        var table = document.querySelector('table');
        if (!table) return;
        // Получаем HTML таблицы
        var tableHtml = table.outerHTML;
        // Открываем новое окно для печати
        var printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
            <head>
              <title>Печать расписания</title>
              <meta charset="UTF-8">
              <style>
                body { font-family: Arial, sans-serif; background: #fff; color: #222; margin: 24px; }
                table { border-collapse: collapse; width: 100%; font-size: 15px; }
                th, td { border: 1px solid #aaa; padding: 6px 10px; text-align: center; }
                th { background: #f0f0f0; }
                td.empty { background: #fafafa; color: #999; }
                .lesson-block { text-align: left; padding-left: 0; }
                .lesson-color-bar { display: none; }
                hr { border: none; border-top: 1px solid #eee; margin: 6px 0; }
                @media print {
                  body { margin: 0; }
                }
              </style>
            </head>
            <body>
              <h2 style="margin-bottom:18px;">Расписание занятий</h2>
              ${tableHtml}
              <script>
                window.onload = function() {
                  window.print();
                  window.onafterprint = function() { window.close(); };
                };
              <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    });
</script>
<!-- Подключение jQuery (требуется для работы Select2) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Подключение Select2 JS для улучшенного выпадающего списка -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        // Инициализация Select2 для селекта с группами
        $('#groupSelect').select2({
            placeholder: "Выберите группу",
            allowClear: true,
            width: 'resolve',
            language: "ru" // Русская локализация
        });
        // Автоматическая отправка формы при изменении выбора группы
        $('#groupSelect').on('change', function () {
            $('#groupForm').submit();
        });
    });
</script>
</body>
</html>
