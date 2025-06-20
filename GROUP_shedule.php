<?php
// Подключение к базе данных
$host = 'MySQL-8.0';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
// Создаем новое соединение с базой данных MySQL
$conn = new mysqli($host, $user, $password, $dbname);
// Проверяем наличие ошибок подключения
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем выбранный ID группы из GET-параметров, если он есть, иначе 0
$selectedGroupId = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
// Получаем дату начала недели из GET-параметра weekInput в формате 'ГГГГ-WWW' или из weekStart в формате 'ГГГГ-ММ-ДД'
// Если параметры отсутствуют или неверны, используем текущую дату
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
// Получаем список всех групп для фильтрации и отображения
$groups = [];
$sqlGroups = "SELECT id, name FROM `groups` ORDER BY name+0 ASC, name ASC";
$resultGroups = $conn->query($sqlGroups);
if ($resultGroups) {
    while ($row = $resultGroups->fetch_assoc()) {
        // Формируем ассоциативный массив с ключом id и значением name
        $groups[$row['id']] = $row['name'];
    }
}
// Находим понедельник выбранной недели (по ISO-стандарту понедельник = 1)
$dayOfWeek = (int) $inputDate->format('N'); // 1 (понедельник) - 7 (воскресенье)
$weekStartDate = clone $inputDate;
$weekStartDate->modify('-' . ($dayOfWeek - 1) . ' days'); // сдвигаем дату на понедельник недели
$weekStart = $weekStartDate->format('Y-m-d'); // форматируем дату понедельника
// Находим воскресенье выбранной недели (понедельник + 6 дней)
$weekEndDate = clone $weekStartDate;
$weekEndDate->modify('+6 days');
$weekEnd = $weekEndDate->format('Y-m-d');
// Кнопки переключения недель: предыдущая и следующая
$prevWeek = clone $weekStartDate;
$prevWeek->modify('-7 days');
$nextWeek = clone $weekStartDate;
$nextWeek->modify('+7 days');
// Получаем текущую дату и понедельник текущей недели для сравнения и выделения
$todayDate = (new DateTime())->format('Y-m-d');
$today = new DateTime();
$dayOfWeekToday = (int) $today->format('N'); // 1 (понедельник) - 7 (воскресенье)
$currentWeekMonday = clone $today;
$currentWeekMonday->modify('-' . ($dayOfWeekToday - 1) . ' days');
$currentWeekMondayStr = $currentWeekMonday->format('Y-m-d');
/**
 * Функция нормализации временных меток:
 * Убирает все пробелы и приводит строку к нижнему регистру
 * Используется для сравнения или стандартизации
 *
 * @param string $label
 * @return string
 */
function normalizeTimeLabel($label)
{
    return preg_replace('/\s+/', '', mb_strtolower($label));
}
// Получаем временные слоты (id => label) из базы
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
// Формируем SQL-запрос для получения расписания с необходимыми JOIN для получения связанных данных
$sql = "SELECT 
    s.date, 
    s.time_slot_id,
    ts.label AS time_label,
    g.name AS group_name,
    t.full_name AS teacher_name,
    sub.name AS subject_name,
    r.number AS room_number,
    r.building AS room_building,
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
// Параметры и типы для подготовленного запроса
$params = [$weekStart, $weekEnd];
$types = 'ss';
// Если выбрана конкретная группа, добавляем фильтр по ней
if ($selectedGroupId) {
    $sql .= " AND s.group_id = ?";
    $params[] = $selectedGroupId;
    $types .= 'i';
}
// Добавляем сортировку по дате и времени занятия
$sql .= " ORDER BY s.date, ts.start_time";
// Подготавливаем запрос
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
// Привязываем параметры динамически
$stmt->bind_param($types, ...$params);
// Выполняем запрос
$stmt->execute();
// Получаем результат запроса
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
 * Функция для форматирования даты в удобочитаемый вид с русскими сокращениями дней недели
 *
 * @param string $date Дата в формате 'Y-m-d'
 * @return string Форматированная дата, например "Пн 01.01.2024"
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
 * Функция для получения цвета занятия по названию типа занятия
 *
 * @param string $typeName Название типа занятия
 * @return string Цвет в формате HEX
 */
function getLessonColor($typeName)
{
    $colors = [
        'вид нагрузки не определен' => '#999999',  // серый
        'лабораторное занятие' => '#FF9800',  // оранжевый
        'лекционное занятие' => '#4CAF50',  // зелёный
        'практическое занятие' => '#2196F3',  // синий
        'факультатив' => '#9C27B0',  // фиолетовый
        'экзамен' => '#F44336',  // красный
    ];
    $key = mb_strtolower(trim($typeName));
    return $colors[$key] ?? '#999999'; // серый по умолчанию
}
// Форматируем значение для поля weekInput (например, "2024-W23")
$weekInputValue = $weekStartDate->format('o-\WW');
// Определяем учебный год по дате начала недели (учебный год начинается с сентября)
$startYear = (int) $weekStartDate->format('Y');
$startMonth = (int) $weekStartDate->format('n');
if ($startMonth < 9) {
    // Если месяц до сентября, учебный год начался в прошлом календарном году
    $academicYear = ($startYear - 1) . '-' . $startYear;
} else {
    $academicYear = $startYear . '-' . ($startYear + 1);
}
// Номер недели по ISO-8601
$weekNumber = (int) $weekStartDate->format('W');
// Чётность недели (для расписания)
$weekParity = ($weekNumber % 2 === 0) ? 'чётная' : 'нечётная';
// Массив цветов для легенды расписания
$lessonTypesColors = [
    'Вид нагрузки не определен' => '#999999',  // серый
    'Лабораторное занятие' => '#FF9800',  // оранжевый
    'Лекционное занятие' => '#4CAF50',  // зелёный
    'Практическое занятие' => '#2196F3',  // синий
    'Факультатив' => '#9C27B0',  // фиолетовый
    'Экзамен' => '#F44336',  // красный
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- Подключение стилей Select2 для красивых выпадающих списков -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <!-- Иконка сайта (favicon) -->
    <link rel="icon" href="img.png" type="image/jpeg" />
    <!-- Стили для боковой панели (sidebar) -->
    <link rel="stylesheet" href="sidebar.css" />
    <!-- Мета-тег для мобильной адаптивности -->
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Кодировка страницы -->
    <meta charset="UTF-8" />
    <!-- Заголовок страницы -->
    <title>Расписание на неделю</title>
    <style>
        /* --- СТИЛИ ТАБЛИЦЫ --- */
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th,
        td {
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
        /* --- НАВИГАЦИОННЫЕ КНОПКИ --- */
        .nav-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .nav-buttons button {
            padding: 8px 16px;
            margin-right: 10px;
            font-size: 16px;
            cursor: pointer;
            background-color: #a83250;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .nav-buttons button:hover,
        .nav-buttons button:focus {
            background-color: #7b203a;
            outline: none;
        }
        .nav-buttons a {
            text-decoration: none;
        }
        /* --- БЛОК ЗАНЯТИЯ В ЯЧЕЙКЕ --- */
        .lesson-block {
            position: relative;
            padding-left: 14px;
            margin-bottom: 8px;
            text-align: left;
            background: #fff0f2;
            border-radius: 6px;
            padding: 8px 12px;
            box-shadow: 0 1px 4px rgba(123, 32, 58, 0.15);
        }
        .lesson-color-bar {
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 6px;
            border-radius: 3px;
            background: #a83250; /* по умолчанию, может быть переопределён inline-стилем */
        }
        /* --- СЕГОДНЯШНИЙ ДЕНЬ --- */
        .today {
            background-color:rgb(180, 40, 61);
            transition: background-color 0.3s ease;
        }
        /* --- КНОПКИ ПЕРЕКЛЮЧЕНИЯ ВИДА --- */
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
        /* --- КНОПКИ ДНЕЙ НЕДЕЛИ --- */
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
        /* --- КНОПКА ПЕЧАТИ --- */
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
        /* --- ФОРМА ВЫБОРА ДАТЫ --- */
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
        /* --- ОБЁРТКА ДЛЯ ГОРИЗОНТАЛЬНОГО СКРОЛЛА ТАБЛИЦЫ --- */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* плавная прокрутка на iOS */
            margin-bottom: 20px;
        }
        table {
            min-width: 700px; /* минимальная ширина для удобства */
        }
        th,
        td {
            padding: 8px 12px;
            vertical-align: top;
        }
        thead th {
            top: 0;
            background: #a83250;
            color: white;
            z-index: 10;
            padding: 10px;
            text-align: center;
        }
        /* --- ХОВЕР ДЛЯ ЯЧЕЕК ТАБЛИЦЫ --- */
        table td:hover,
        table th:hover {
            background-color: #fce4ec; /* светло-розовый фон */
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        /* --- ЗАГОЛОВОК РАСПИСАНИЯ --- */
        .schedule-header {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #a83250;
            text-align: center;
            margin: 5px 0 10px 0;
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
        /* --- ИНФОБЛОК О РАСПИСАНИИ --- */
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
        /* --- ОБЁРТКА ДЛЯ ТАБЛИЦЫ --- */
        #tableView {
            max-width: 100%;
            overflow-x: auto; /* Только горизонтальный скролл */
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            border-radius: 8px;
            background: #fff;
            margin-bottom: 20px;
        }
        #tableView table {
            min-width: 800px;
            width: 100%;
            border-collapse: collapse;
        }
        /* Стилизация горизонтального скроллбара для Webkit */
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
        /* Для Firefox */
        #tableView {
            scrollbar-width: thin;
            scrollbar-color: #a83250 #f0e6e9;
        }
        /* --- АДАПТИВНОСТЬ ДЛЯ МОБИЛЬНЫХ --- */
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <!-- Мобильная верхняя панель, изначально скрыта -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <!-- Кнопка для открытия мобильного меню -->
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <!-- Заголовок панели -->
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Включаем боковую панель -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <!-- Навигационные кнопки для переключения недель -->
            <div class="nav-buttons">
                <!-- Кнопка перехода к предыдущей неделе -->
                <a href="?weekStart=<?= htmlspecialchars($prevWeek->format('Y-m-d')) ?>&group_id=<?= $selectedGroupId ?>">
                    <button type="button" aria-label="Предыдущая неделя">← Предыдущая неделя</button>
                </a>
                <!-- Кнопка перехода к текущей неделе -->
                <a href="?weekStart=<?= htmlspecialchars($currentWeekMondayStr) ?>&group_id=<?= $selectedGroupId ?>">
                    <button type="button" aria-label="Текущая неделя">Текущая неделя</button>
                </a>
                <!-- Кнопка перехода к следующей неделе -->
                <a href="?weekStart=<?= htmlspecialchars($nextWeek->format('Y-m-d')) ?>&group_id=<?= $selectedGroupId ?>">
                    <button type="button" aria-label="Следующая неделя">Следующая неделя →</button>
                </a>
            </div>
            <!-- Форма выбора даты -->
            <form method="GET" action="" class="date-picker-form">
                <label for="datePicker">Выберите дату:</label>
                <!-- Поле выбора даты -->
                <input type="date" id="datePicker" name="weekStart" value="<?= htmlspecialchars($inputDate->format('Y-m-d')) ?>" required>
                <!-- Скрытое поле с выбранной группой -->
                <input type="hidden" name="group_id" value="<?= $selectedGroupId ?>">
                <button type="submit">Показать расписание</button>
            </form>
            <!-- Обертка для переключения вида и кнопки печати -->
            <div class="view-print-wrapper" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <!-- Переключатель вида расписания (таблица/день) -->
                <div class="view-switcher">
                    <button id="tableViewBtn" class="switch-btn active">Таблица</button>
                    <button id="dayViewBtn" class="switch-btn">День</button>
                </div>
                <!-- Кнопка печати расписания -->
                <button id="printBtn" class="print-btn" title="Печать" aria-label="Печать расписания">
                    <i class="fas fa-print"></i>
                </button>
            </div>
            <!-- Кнопки выбора дня недели, изначально скрыты -->
            <div id="dayButtons" style="display:none; margin-bottom:18px;">
                <?php
                $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                foreach ($dates as $i => $date) {
                    // Кнопка для каждого дня недели, активная — понедельник
                    echo '<button class="day-btn' . ($i == 0 ? ' active' : '') . '" data-day="' . $i . '">' . $weekDays[$i] . '</button> ';
                }
                ?>
            </div>
            <!-- Заголовок расписания с датами недели -->
            <h1 class="schedule-header">
                Расписание занятий
                <span>
                    с <?= htmlspecialchars(formatDate($weekStart)) ?> по <?= htmlspecialchars(formatDate($weekEnd)) ?>
                </span>
            </h1>
            <!-- Информационный блок с выбранной группой, учебным годом и номером недели -->
            <div class="schedule-info">
                <span>Расписание группы
                    <strong><?= htmlspecialchars($groups[$selectedGroupId] ?? 'не выбрана') ?></strong></span>
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
                                <!-- Выделяем сегодняшнюю дату классом today -->
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
                                                    <!-- Цветовой индикатор типа занятия -->
                                                    <span class="lesson-color-bar"
                                                        style="background-color: <?= getLessonColor($lesson['lesson_type_name']) ?>;"></span>
                                                    <strong>Предмет:</strong> <?= htmlspecialchars($lesson['subject_name']) ?><br>
                                                    <strong>Группа:</strong> <?= htmlspecialchars($lesson['group_name']) ?><br>
                                                    <strong>Преподаватель:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?><br>
                                                    <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?> /
                                                    <?= htmlspecialchars($lesson['room_building']) ?>
                                                </div>
                                                <hr>
                                            <?php endforeach; ?>
                                        </td>
                                    <?php else: ?>
                                        <!-- Пустая ячейка -->
                                        <td class="empty <?= $tdClass ?>">-</td>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Вид по дням недели, изначально скрыт -->
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
                                                        <span class="lesson-color-bar"
                                                            style="background-color: <?= getLessonColor($lesson['lesson_type_name']) ?>;"></span>
                                                        <strong>Предмет:</strong> <?= htmlspecialchars($lesson['subject_name']) ?><br>
                                                        <strong>Группа:</strong> <?= htmlspecialchars($lesson['group_name']) ?><br>
                                                        <strong>Преподаватель:</strong>
                                                        <?= htmlspecialchars($lesson['teacher_name']) ?><br>
                                                        <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?> /
                                                        <?= htmlspecialchars($lesson['room_building']) ?>
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
            <div class="legend"
                style="max-width:1100px; margin: 20px auto; padding: 10px 20px; font-family: Arial, sans-serif;">
                <strong>Легенда цветов:</strong>
                <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px;">
                    <?php foreach ($lessonTypesColors as $typeName => $color): ?>
                        <div style="display: flex; align-items: center; gap: 6px; font-size: 14px; color: #333;">
                            <!-- Кружок с цветом -->
                            <span
                                style="display: inline-block; width: 18px; height: 18px; background-color: <?= htmlspecialchars($color) ?>; border-radius: 50%; border: 1px solid #ccc;"></span>
                            <span><?= htmlspecialchars($typeName) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>
    <!-- Подключаем скрипт боковой панели -->
    <script src="sidebar.js"></script>
    <script>
        // Ждем загрузки DOM
        document.addEventListener('DOMContentLoaded', function () {
            const tableViewBtn = document.getElementById('tableViewBtn');
            const dayViewBtn = document.getElementById('dayViewBtn');
            const tableView = document.getElementById('tableView');
            const dayView = document.getElementById('dayView');
            const dayButtons = document.getElementById('dayButtons');
            const dayBtnList = dayButtons.querySelectorAll('.day-btn');
            const daySchedules = dayView.querySelectorAll('.day-schedule');
            // Переключение между табличным и дневным видом
            tableViewBtn.addEventListener('click', () => {
                tableViewBtn.classList.add('active');
                dayViewBtn.classList.remove('active');
                tableView.style.display = '';
                dayView.style.display = 'none';
                dayButtons.style.display = 'none';
            });
            dayViewBtn.addEventListener('click', () => {
                tableViewBtn.classList.remove('active');
                dayViewBtn.classList.add('active');
                tableView.style.display = 'none';
                dayView.style.display = '';
                dayButtons.style.display = '';
                // Показываем понедельник по умолчанию
                dayBtnList.forEach((btn, i) => {
                    btn.classList.toggle('active', i === 0);
                });
                daySchedules.forEach((el, i) => {
                    el.style.display = i === 0 ? '' : 'none';
                });
            });
            // Переключение между днями недели в дневном виде
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
    <script>
        // Обработчик кнопки печати
        document.getElementById('printBtn').addEventListener('click', function () {
            // Находим первую таблицу на странице (можно уточнить селектор)
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
    <!-- Подключение Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Инициализация Select2 для выпадающего списка групп
        $(document).ready(function () {
            $('#groupSelect').select2({
                placeholder: "Выберите группу",
                allowClear: true,
                width: 'resolve',
                language: "ru"
            });
            // Автоматическая отправка формы при выборе группы
            $('#groupSelect').on('change', function () {
                $('#groupForm').submit();
            });
        });
    </script>
</body>
</html>
