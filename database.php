<?php
/**
 * Файл database.php
 * Содержит функции для работы с базой данных расписания
 */
/**
 * Получение соединения с базой данных (Singleton)
 * Используется статическая переменная для повторного использования одного соединения
 *
 * @return mysqli Объект соединения с базой данных
 */
function getDbConnection()
{
    static $conn = null; // Хранит единственное соединение
    if ($conn === null) {
        // Создаем новое соединение с базой данных
        $conn = new mysqli('MySQL-8.0', 'ADMIN_BASIC', 'od3.IyTiJ_[BqCIq', 'ScheduleBase');
        // Проверяем наличие ошибок подключения
        if ($conn->connect_error) {
            die("Ошибка подключения: " . $conn->connect_error);
        }
        // Устанавливаем кодировку UTF-8 для корректной работы с текстом
        $conn->set_charset("utf8");
    }
    // Возвращаем объект соединения
    return $conn;
}
/**
 * Получение списка групп из базы данных
 *
 * @return array Массив групп с полями 'id' и 'name'
 */
function getGroups()
{
    $conn = getDbConnection(); // Получаем соединение
    $result = $conn->query("SELECT id, name FROM `groups` ORDER BY name"); // Выполняем запрос
    return $result->fetch_all(MYSQLI_ASSOC); // Возвращаем все записи в виде ассоциативного массива
}
/**
 * Получение списка кабинетов из базы данных
 *
 * @return array Массив кабинетов с полями 'id' и 'number'
 */
function getClassrooms()
{
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, number FROM rooms ORDER BY number");
    return $result->fetch_all(MYSQLI_ASSOC);
}
/**
 * Получение списка временных слотов из базы данных
 *
 * @return array Массив временных слотов с полями 'id', 'start_time', 'end_time'
 */
function getTimeSlots()
{
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, start_time, end_time FROM time_slots ORDER BY start_time");
    return $result->fetch_all(MYSQLI_ASSOC);
}
/**
 * Проверка конфликта расписания
 * Проверяет, есть ли занятие в указанное время, в указанном кабинете, группе или у преподавателя
 *
 * @param string $date Дата занятия в формате 'YYYY-MM-DD'
 * @param int $timeSlotId ID временного слота
 * @param int $roomId ID кабинета
 * @param int $groupId ID группы
 * @param int $teacherId ID преподавателя
 * @return bool true, если конфликт найден, иначе false
 */
function checkConflict($date, $timeSlotId, $roomId, $groupId, $teacherId)
{
    $conn = getDbConnection();
    $sql = "SELECT COUNT(*) FROM schedule
            WHERE date = ?
            AND time_slot_id = ?
            AND (room_id = ? OR group_id = ? OR teacher_id = ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    // Привязываем параметры к запросу: s - string, i - integer
    $stmt->bind_param("siiii", $date, $timeSlotId, $roomId, $groupId, $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_row()[0];
    $stmt->close();
    // Возвращаем true, если количество конфликтов больше 0
    return $count > 0;
}
/**
 * Добавление нового занятия в расписание
 *
 * @param string $date Дата занятия
 * @param int $groupId ID группы
 * @param int $teacherId ID преподавателя
 * @param int $subjectId ID предмета
 * @param int $roomId ID кабинета
 * @param int $lessonTypeId ID типа занятия
 * @param int $timeSlotId ID временного слота
 * @return bool true при успешном добавлении, false при ошибке
 */
function addLesson($date, $groupId, $teacherId, $subjectId, $roomId, $lessonTypeId, $timeSlotId)
{
    $conn = getDbConnection();
    $sql = "INSERT INTO schedule (date, group_id, teacher_id, subject_id, room_id, lesson_type_id, time_slot_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Ошибка подготовки запроса: " . $conn->error);
    }
    // Привязываем параметры (s - string, i - integer)
    $stmt->bind_param("siiiiii", $date, $groupId, $teacherId, $subjectId, $roomId, $lessonTypeId, $timeSlotId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
/**
 * Получение списка типов занятий
 *
 * @return array Массив типов занятий с полями 'id' и 'name'
 */
function getLessonTypes()
{
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, name FROM lesson_types");
    return $result->fetch_all(MYSQLI_ASSOC);
}
/**
 * Получение списка преподавателей
 *
 * @return array Массив преподавателей с полями 'id' и 'full_name'
 */
function getTeachers()
{
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, full_name FROM teachers");
    return $result->fetch_all(MYSQLI_ASSOC);
}
/**
 * Получение списка предметов
 *
 * @return array Массив предметов с полями 'id' и 'name'
 */
function getSubjects()
{
    $conn = getDbConnection();
    $result = $conn->query("SELECT id, name FROM subjects");
    return $result->fetch_all(MYSQLI_ASSOC);
}
