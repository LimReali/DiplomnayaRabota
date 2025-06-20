<?php
// Тестовая изначальная страница, не нужна!!!
// Тестовая изначальная страница, не нужна!!!
// Тестовая изначальная страница, не нужна!!!
// Тестовая изначальная страница, не нужна!!!
// Тестовая изначальная страница, не нужна!!!
// Подключение к базе данных
$host = 'localhost';
$user = 'ADMIN_BASIC';
$password = 'od3.IyTiJ_[BqCIq';
$dbname = 'ScheduleBase';
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}
// Получаем дату начала недели из GET-параметра или используем текущую дату
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
// Находим понедельник выбранной недели
$dayOfWeek = (int) $inputDate->format('N'); // 1 (понедельник) - 7 (воскресенье)
$weekStartDate = clone $inputDate;
$weekStartDate->modify('-' . ($dayOfWeek - 1) . ' days');
$weekStart = $weekStartDate->format('Y-m-d');
// Находим воскресенье выбранной недели
$weekEndDate = clone $weekStartDate;
$weekEndDate->modify('+6 days');
$weekEnd = $weekEndDate->format('Y-m-d');
// Кнопки переключения недель
$prevWeek = clone $weekStartDate;
$prevWeek->modify('-7 days');
$nextWeek = clone $weekStartDate;
$nextWeek->modify('+7 days');
// Вычисляем понедельник текущей недели (сегодня)
$todayDate = (new DateTime())->format('Y-m-d');
$today = new DateTime();
$dayOfWeekToday = (int) $today->format('N'); // 1 (понедельник) - 7 (воскресенье)
$currentWeekMonday = clone $today;
$currentWeekMonday->modify('-' . ($dayOfWeekToday - 1) . ' days');
$currentWeekMondayStr = $currentWeekMonday->format('Y-m-d');
// Функция нормализации временных меток (убирает пробелы и приводит к нижнему регистру)
function normalizeTimeLabel($label)
{
    return preg_replace('/\s+/', '', mb_strtolower($label));
}
// Получаем временные слоты (id => label)
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
// Формируем массив дат недели
$dates = [];
$current = clone $weekStartDate;
$end = clone $weekEndDate;
while ($current <= $end) {
    $dates[] = $current->format('Y-m-d');
    $current->modify('+1 day');
}
// Получаем расписание только за нужную неделю
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
        WHERE s.date BETWEEN ? AND ?
        ORDER BY s.date, ts.start_time";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param('ss', $weekStart, $weekEnd);
$stmt->execute();
$result = $stmt->get_result();
$schedule = [];
while ($row = $result->fetch_assoc()) {
    $date = $row['date'];
    $slotId = $row['time_slot_id'];
    if (!isset($schedule[$date][$slotId])) {
        $schedule[$date][$slotId] = [];
    }
    $schedule[$date][$slotId][] = $row;
}
$stmt->close();
$conn->close();
// Функция для удобного форматирования даты
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
$weekInputValue = $weekStartDate->format('o-\WW');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <link rel="icon" href="img.png" type="image/jpeg" />
    <link rel="stylesheet" href="sidebar.css" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta charset="UTF-8" />
    <title>Расписание на неделю</title>
    <style>
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
        .nav-buttons {
            margin-bottom: 20px;
        }
        .nav-buttons button {
            padding: 8px 16px;
            margin-right: 10px;
            font-size: 16px;
            cursor: pointer;
        }
        .lesson-block {
            position: relative;
            padding-left: 14px;
            margin-bottom: 8px;
            text-align: left;
        }
        .lesson-color-bar {
            position: absolute;
            left: 0;
            top: 4px;
            bottom: 4px;
            width: 6px;
            border-radius: 3px;
        }
        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 8px 0;
        }
        .today {
            border: 2px solid orange !important;
            /* оранжевая рамка для наглядности */
        }
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
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <div class="nav-buttons">
                <a href="?weekStart=<?= htmlspecialchars($prevWeek->format('Y-m-d')) ?>"><button>← Предыдущая
                        неделя</button></a>
                <a href="?weekStart=<?= htmlspecialchars($currentWeekMondayStr) ?>"><button>Текущая неделя</button></a>
                <a href="?weekStart=<?= htmlspecialchars($nextWeek->format('Y-m-d')) ?>"><button>Следующая неделя
                        →</button></a>
            </div>
            <form method="GET" action="">
                <label for="datePicker">Выберите дату:</label>
                <input type="date" id="datePicker" name="weekStart"
                    value="<?= htmlspecialchars($inputDate->format('Y-m-d')) ?>">
                <button type="submit">Показать расписание</button>
            </form>
            <div class="view-switcher" style="margin-bottom:18px;">
                <button id="tableViewBtn" class="switch-btn active">Таблица</button>
                <button id="dayViewBtn" class="switch-btn">День</button>
            </div>
            <div id="dayButtons" style="display:none; margin-bottom:18px;">
                <?php
                $weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
                foreach ($dates as $i => $date) {
                    echo '<button class="day-btn' . ($i == 0 ? ' active' : '') . '" data-day="' . $i . '">' . $weekDays[$i] . '</button> ';
                }
                ?>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-bottom: 8px;">
                <button id="printBtn" class="print-btn" title="Печать">
                    <i class="fas fa-print"></i>
                </button>
            </div>
            <h1>Расписание занятий с <?= htmlspecialchars(formatDate($weekStart)) ?> по
                <?= htmlspecialchars(formatDate($weekEnd)) ?></h1>
            <p>Сегодня: <?= $todayDate ?></p>
            <p>Даты недели: <?= implode(', ', $dates) ?></p>
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
                                                    <span class="lesson-color-bar"
                                                        style="background-color: <?= getLessonColor($lesson['lesson_type_name']) ?>;"></span>
                                                    <strong>Предмет:</strong> <?= htmlspecialchars($lesson['subject_name']) ?><br>
                                                    <strong>Группа:</strong> <?= htmlspecialchars($lesson['group_name']) ?><br>
                                                    <strong>Преподаватель:</strong> <?= htmlspecialchars($lesson['teacher_name']) ?><br>
                                                    <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?>
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
                                                        <strong>Кабинет:</strong> <?= htmlspecialchars($lesson['room_number']) ?>
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
        </main>
    </div>
    <script src="sidebar.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tableViewBtn = document.getElementById('tableViewBtn');
            const dayViewBtn = document.getElementById('dayViewBtn');
            const tableView = document.getElementById('tableView');
            const dayView = document.getElementById('dayView');
            const dayButtons = document.getElementById('dayButtons');
            const dayBtnList = dayButtons.querySelectorAll('.day-btn');
            const daySchedules = dayView.querySelectorAll('.day-schedule');
            // Переключение вида
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
            // Переключение дня
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
        document.getElementById('printBtn').addEventListener('click', function () {
            // Если на странице несколько таблиц, используйте id у нужной таблицы!
            var table = document.querySelector('table');
            if (!table) return;
            var tableHtml = table.outerHTML;
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
</body>
</html>