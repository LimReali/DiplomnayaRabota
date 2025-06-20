<?php
require_once 'database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDbConnection(); // Получаем соединение с БД

// Получаем данные для формы (используйте функции, которые внутри вызывают getDbConnection())
$groups = getGroups();
$classrooms = getClassrooms();
$timeSlots = getTimeSlots();
$lessonTypes = getLessonTypes();
$teachers = getTeachers();
$subjects = getSubjects();

$error = '';
$success = '';
$date = '';
$groupId = '';
$teacherId = '';
$subjectId = '';
$roomId = '';
$lessonTypeId = '';
$timeSlotId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_lesson'])) {
    $date = $_POST['date'] ?? '';
    $groupId = $_POST['group'] ?? '';
    $teacherId = $_POST['teacher'] ?? '';
    $subjectId = $_POST['subject'] ?? '';
    $roomId = $_POST['classroom'] ?? '';
    $lessonTypeId = $_POST['lesson_type'] ?? '';
    $timeSlotId = $_POST['time_slot'] ?? '';

    $errors = [];
    if (trim($date) === '') $errors[] = "Поле 'Дата' обязательно для заполнения";
    if (trim($timeSlotId) === '') $errors[] = "Поле 'Временной слот' обязательно для заполнения";
    if (trim($groupId) === '') $errors[] = "Поле 'Группа' обязательно для заполнения";
    if (trim($roomId) === '') $errors[] = "Поле 'Кабинет' обязательно для заполнения";
    if (trim($teacherId) === '') $errors[] = "Поле 'Преподаватель' обязательно для заполнения";
    if (trim($lessonTypeId) === '') $errors[] = "Поле 'Тип занятия' обязательно для заполнения";
    if (trim($subjectId) === '') $errors[] = "Поле 'Предмет' обязательно для заполнения";

    if (!empty($errors)) {
        $error = implode('<br>', $errors);
    } else {
        if (checkConflict($date, $timeSlotId, $roomId, $groupId, $teacherId)) {
            $error = "Конфликт расписания! Занятие уже существует в это время.";
        } else {
            // Устанавливаем переменную для триггеров в том же соединении
            $userId = intval($_SESSION['user_id']);
            if (!$conn->query("SET @current_user_id = $userId")) {
                $error = "Ошибка установки пользователя для истории: " . $conn->error;
            } else {
                if (addLesson($date, $groupId, $teacherId, $subjectId, $roomId, $lessonTypeId, $timeSlotId)) {
                    $success = "Занятие успешно добавлено!";
                    $date = $groupId = $teacherId = $subjectId = $roomId = $lessonTypeId = $timeSlotId = '';
                } else {
                    $error = "Ошибка при добавлении занятия!";
                }
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Добавление занятия</title>
    <style>
        /* Общие стили для страницы */
        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f8;
            color: #333;
        }
        /* Шапка сайта */
        header {
            background-color: #34495e;
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
            margin: 0;
        }
        header h1 {
            margin: 0;
            font-size: 24px;
        }
        /* Навигация в шапке */
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
        nav form button:hover,
        nav a:hover {
            text-decoration: underline;
        }
        /* Заголовок страницы */
        h1.page-title {
            text-align: center;
            color: #2c3e50;
            margin: 30px 0 20px;
        }
        /* Форма добавления занятия */
        form.lesson-form {
            max-width: 600px;
            margin: 0 auto 40px auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 15px;
            font-weight: 600;
            color: #34495e;
        }
        /* Поля ввода и выпадающие списки */
        input[type="date"],
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        input[type="date"]:focus,
        select:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        /* Кнопка добавления занятия */
        button[type="submit"].add-lesson-btn {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 20px;
        }
        button[type="submit"].add-lesson-btn:hover {
            background-color: #2980b9;
        }
        /* Сообщения об ошибке и успехе */
        .error,
        .success {
            max-width: 600px;
            margin: 0 auto 20px auto;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .error {
            background-color: #e74c3c;
            color: white;
            box-shadow: 0 3px 6px rgba(231, 76, 60, 0.4);
        }
        .success {
            background-color: #2ecc71;
            color: white;
            box-shadow: 0 3px 6px rgba(46, 204, 113, 0.4);
        }
        /* Кнопка "Назад" */
        form.back-form {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }
        form.back-form button {
            background-color: #e74c3c;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            width: 250px;
            padding: 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        form.back-form button:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <!-- Шапка с навигацией -->
    <header>
        <h1>Панель администратора</h1>
        <nav>
            <a href="add_lesson.php">Добавить занятие</a>
            <a href="groups_admin.php">Управление группами</a>
            <a href="teachers_admin.php">Управление преподавателями</a>
            <a href="db_history.php">История изменений базы</a>
            <a href="admin_rooms_select.php">Управление кабинетами</a>
            <!-- Форма выхода из системы -->
            <form id="logoutForm" action="logout.php" method="post" style="display:inline;">
                <button type="button" onclick="document.getElementById('logoutForm').submit();"
                    title="Выйти из системы">Выйти</button>
            </form>
        </nav>
    </header>
    <!-- Заголовок страницы -->
    <h1 class="page-title">Добавить новое занятие</h1>
    <!-- Вывод сообщений об ошибке или успехе -->
    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>
    <!-- Форма добавления занятия -->
    <form method="POST" class="lesson-form" novalidate>
        <label>Дата занятия:
            <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
        </label>
        <label>Временной слот:
            <select name="time_slot" required>
                <option value="">-- выберите --</option>
                <?php foreach ($timeSlots as $slot): ?>
                    <option value="<?= $slot['id'] ?>" <?= ($timeSlotId == $slot['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars(substr($slot['start_time'], 0, 5)) ?> -
                        <?= htmlspecialchars(substr($slot['end_time'], 0, 5)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Группа:
            <select name="group" required>
                <option value="">-- выберите --</option>
                <?php foreach ($groups as $group): ?>
                    <option value="<?= $group['id'] ?>" <?= ($groupId == $group['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($group['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Кабинет:
            <select name="classroom" required>
                <option value="">-- выберите --</option>
                <?php foreach ($classrooms as $room): ?>
                    <option value="<?= $room['id'] ?>" <?= ($roomId == $room['id']) ? 'selected' : '' ?>>
                        №<?= htmlspecialchars($room['number']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Тип занятия:
            <select name="lesson_type" required>
                <option value="">-- выберите --</option>
                <?php foreach ($lessonTypes as $type): ?>
                    <option value="<?= $type['id'] ?>" <?= ($lessonTypeId == $type['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($type['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Преподаватель:
            <select name="teacher" required>
                <option value="">-- выберите --</option>
                <?php foreach ($teachers as $teacher): ?>
                    <option value="<?= $teacher['id'] ?>" <?= ($teacherId == $teacher['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($teacher['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Предмет:
            <select name="subject" required>
                <option value="">-- выберите --</option>
                <?php foreach ($subjects as $subject): ?>
                    <option value="<?= $subject['id'] ?>" <?= ($subjectId == $subject['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($subject['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <!-- Кнопка отправки формы -->
        <button type="submit" name="add_lesson" class="add-lesson-btn">Добавить занятие</button>
    </form>
    <!-- Кнопка "Назад" -->
    <form action="MainAdmin.php" method="post" class="back-form">
        <button type="submit" title="Назад">
            <span>Назад</span>
            <span style="font-size: 18px;">❌</span>
        </button>
    </form>
</body>
</html>
