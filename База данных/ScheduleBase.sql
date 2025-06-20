-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1:3306
-- Время создания: Июн 20 2025 г., 14:31
-- Версия сервера: 8.0.30
-- Версия PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `ScheduleBase`
--

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`root`@`%` PROCEDURE `remove_duplicate_rooms` ()   BEGIN
    DELETE r1 FROM rooms r1
    INNER JOIN rooms r2 
    ON r1.number = r2.number AND r1.building = r2.building AND r1.id > r2.id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `consultations`
--

CREATE TABLE `consultations` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `date` date NOT NULL,
  `time_slot_id` int NOT NULL,
  `room_id` int NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `groups`
--

CREATE TABLE `groups` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `faculty` varchar(100) DEFAULT NULL,
  `course` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `groups`
--

INSERT INTO `groups` (`id`, `name`, `faculty`, `course`) VALUES
(1, '716', 'Заочный факультет', 1),
(2, '1030', 'Дорожно-строительный факультет', 2),
(3, '592', 'Строительный факультет', 3),
(4, '287', 'Архитектурный факультет', 1),
(5, '942', 'Архитектурный факультет', 2),
(6, '415', 'Строительный факультет', 3),
(7, '678', 'Строительный факультет', 4),
(8, '331', 'Дорожно-строительный факультет', 1),
(9, '857', 'Дорожно-строительный факультет', 2),
(10, '504', 'Факультет инженерных систем и экологии', 3),
(11, '799', 'Факультет инженерных систем и экологии', 4),
(12, '226', 'Факультет экономики и управления', 1),
(13, '963', 'Факультет экономики и управления', 2),
(14, '712', 'Факультет информационных технологий', 3),
(15, '144', 'Факультет информационных технологий', 4),
(16, '385', 'Факультет землеустройства и кадастров', 1),
(17, '517', 'Факультет заочного обучения', 2),
(18, '4821', 'Заочный факультет', 1),
(19, '739', 'Дорожно-строительный факультет', 2),
(20, '3150', 'Строительный факультет', 3),
(21, '982', 'Архитектурный факультет', 1),
(22, '6574', 'Архитектурный факультет', 2),
(23, '2401', 'Строительный факультет', 3),
(24, '891', 'Строительный факультет', 4),
(25, '4230', 'Дорожно-строительный факультет', 1),
(26, '1507', 'Дорожно-строительный факультет', 2),
(27, '3712', 'Факультет инженерных систем и экологии', 3),
(28, '2849', 'Факультет инженерных систем и экологии', 4),
(29, '4976', 'Факультет экономики и управления', 1),
(30, '1983', 'Факультет экономики и управления', 2),
(31, '6097', 'Факультет информационных технологий', 3),
(32, '7124', 'Факультет информационных технологий', 4),
(33, '834', 'Факультет землеустройства и кадастров', 1),
(34, '4506', 'Факультет заочного обучения', 2),
(35, '5612', 'Заочный факультет', 1),
(36, '305', 'Дорожно-строительный факультет', 2),
(37, '7923', 'Строительный факультет', 3),
(38, '687', 'Архитектурный факультет', 1),
(39, '4321', 'Архитектурный факультет', 2),
(40, '975', 'Строительный факультет', 3),
(41, '2048', 'Строительный факультет', 4),
(42, '6890', 'Дорожно-строительный факультет', 1);

--
-- Триггеры `groups`
--
DELIMITER $$
CREATE TRIGGER `trg_groups_delete` AFTER DELETE ON `groups` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, changed_at)
    VALUES (
        v_user_id,
        'groups',
        'D',
        CONCAT('id:', OLD.id, ', name:', OLD.name, ', faculty:', IFNULL(OLD.faculty, ''), ', course:', IFNULL(OLD.course, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_groups_insert` AFTER INSERT ON `groups` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, new_value, changed_at)
    VALUES (
        v_user_id,
        'groups',
        'C',
        CONCAT('id:', NEW.id, ', name:', NEW.name, ', faculty:', IFNULL(NEW.faculty, ''), ', course:', IFNULL(NEW.course, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_groups_update` AFTER UPDATE ON `groups` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, new_value, changed_at)
    VALUES (
        v_user_id,
        'groups',
        'U',
        CONCAT('id:', OLD.id, ', name:', OLD.name, ', faculty:', IFNULL(OLD.faculty, ''), ', course:', IFNULL(OLD.course, '')),
        CONCAT('id:', NEW.id, ', name:', NEW.name, ', faculty:', IFNULL(NEW.faculty, ''), ', course:', IFNULL(NEW.course, '')),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `history`
--

CREATE TABLE `history` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `change_type` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `changed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `history`
--

INSERT INTO `history` (`id`, `user_id`, `table_name`, `change_type`, `old_value`, `new_value`, `changed_at`) VALUES
(1, 1, 'schedule', 'C', NULL, 'id:72, date:2025-05-27, group_id:11, teacher_id:11, subject_id:11, room_id:15, lesson_type_id:3, time_slot_id:4', '2025-05-19 15:37:05'),
(2, 1, 'schedule', 'U', 'id:72, date:2025-05-27, group_id:11, teacher_id:11, subject_id:11, room_id:15, lesson_type_id:3, time_slot_id:4', 'id:72, date:2025-05-27, group_id:16, teacher_id:11, subject_id:11, room_id:15, lesson_type_id:3, time_slot_id:4', '2025-05-19 15:37:29'),
(3, 1, 'schedule', 'U', 'id:58, date:2025-05-22, group_id:5, teacher_id:6, subject_id:1, room_id:7, lesson_type_id:3, time_slot_id:3', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:1, room_id:7, lesson_type_id:3, time_slot_id:3', '2025-05-19 17:53:29'),
(4, 1, 'schedule', 'U', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:1, room_id:7, lesson_type_id:3, time_slot_id:3', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:7, lesson_type_id:3, time_slot_id:3', '2025-05-19 17:53:31'),
(5, 1, 'schedule', 'U', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:7, lesson_type_id:3, time_slot_id:3', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:17, lesson_type_id:3, time_slot_id:3', '2025-05-19 17:53:34'),
(6, 1, 'schedule', 'U', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:17, lesson_type_id:3, time_slot_id:3', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:17, lesson_type_id:5, time_slot_id:3', '2025-05-19 17:53:36'),
(7, 1, 'schedule', 'U', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:17, lesson_type_id:5, time_slot_id:3', 'id:58, date:2025-05-22, group_id:5, teacher_id:2, subject_id:12, room_id:17, lesson_type_id:5, time_slot_id:7', '2025-05-19 17:53:38'),
(8, 1, 'schedule', 'U', 'id:2, date:2025-05-12, group_id:2, teacher_id:2, subject_id:2, room_id:7, lesson_type_id:2, time_slot_id:2', 'id:2, date:2025-05-12, group_id:2, teacher_id:2, subject_id:2, room_id:7, lesson_type_id:6, time_slot_id:2', '2025-05-31 18:16:10'),
(9, 1, 'schedule', 'C', NULL, 'id:73, date:2025-06-10, group_id:23, teacher_id:13, subject_id:13, room_id:51, lesson_type_id:2, time_slot_id:4', '2025-06-02 06:42:30'),
(10, 1, 'schedule', 'U', 'id:54, date:2025-05-20, group_id:7, teacher_id:6, subject_id:4, room_id:20, lesson_type_id:1, time_slot_id:1', 'id:54, date:2025-05-20, group_id:7, teacher_id:6, subject_id:4, room_id:20, lesson_type_id:1, time_slot_id:2', '2025-06-02 06:43:59'),
(11, 1, 'schedule', 'C', NULL, 'id:74, date:2025-06-09, group_id:41, teacher_id:14, subject_id:15, room_id:62, lesson_type_id:2, time_slot_id:1', '2025-06-02 06:50:49'),
(12, 1, 'schedule', 'U', 'id:52, date:2025-05-19, group_id:4, teacher_id:6, subject_id:1, room_id:7, lesson_type_id:1, time_slot_id:3', 'id:52, date:2025-05-19, group_id:4, teacher_id:6, subject_id:1, room_id:7, lesson_type_id:5, time_slot_id:3', '2025-06-02 06:51:27'),
(13, 1, 'schedule', 'D', 'id:52, date:2025-05-19, group_id:4, teacher_id:6, subject_id:1, room_id:7, lesson_type_id:5, time_slot_id:3', NULL, '2025-06-02 06:51:33'),
(14, 1, 'schedule', 'U', 'id:32, date:2025-05-19, group_id:4, teacher_id:7, subject_id:2, room_id:8, lesson_type_id:2, time_slot_id:4', 'id:32, date:2025-06-02, group_id:4, teacher_id:7, subject_id:2, room_id:8, lesson_type_id:2, time_slot_id:4', '2025-06-02 06:55:47'),
(15, 1, 'schedule', 'D', 'id:33, date:2025-05-19, group_id:4, teacher_id:8, subject_id:3, room_id:15, lesson_type_id:3, time_slot_id:5', NULL, '2025-06-02 06:55:52'),
(16, 1, 'schedule', 'C', NULL, 'id:75, date:2025-06-03, group_id:4, teacher_id:15, subject_id:15, room_id:51, lesson_type_id:6, time_slot_id:3', '2025-06-03 13:17:25'),
(17, 1, 'schedule', 'C', NULL, 'id:77, date:2025-05-12, group_id:1, teacher_id:1, subject_id:1, room_id:6, lesson_type_id:1, time_slot_id:2', '2025-06-03 15:38:07'),
(18, 1, 'schedule', 'U', 'id:34, date:2025-05-20, group_id:4, teacher_id:9, subject_id:4, room_id:10, lesson_type_id:1, time_slot_id:1', 'id:34, date:2025-05-20, group_id:4, teacher_id:9, subject_id:4, room_id:10, lesson_type_id:3, time_slot_id:1', '2025-06-03 16:06:45'),
(19, 1, 'schedule', 'U', 'id:34, date:2025-05-20, group_id:4, teacher_id:9, subject_id:4, room_id:10, lesson_type_id:3, time_slot_id:1', 'id:34, date:2025-05-20, group_id:4, teacher_id:9, subject_id:4, room_id:10, lesson_type_id:1, time_slot_id:1', '2025-06-03 16:12:45'),
(20, 1, 'schedule', 'U', 'id:53, date:2025-05-19, group_id:6, teacher_id:6, subject_id:3, room_id:9, lesson_type_id:3, time_slot_id:5', 'id:53, date:2025-05-19, group_id:6, teacher_id:6, subject_id:3, room_id:9, lesson_type_id:3, time_slot_id:7', '2025-06-03 19:08:41'),
(21, 1, 'schedule', 'D', 'id:53, date:2025-05-19, group_id:6, teacher_id:6, subject_id:3, room_id:9, lesson_type_id:3, time_slot_id:7', NULL, '2025-06-03 19:08:46'),
(24, 1, 'schedule', 'C', NULL, 'id:80, date:2025-06-12, group_id:41, teacher_id:15, subject_id:14, room_id:67, lesson_type_id:2, time_slot_id:5', '2025-06-03 19:27:04'),
(30, 2, 'teachers', 'C', NULL, 'id:56, full_name:Петров П.П., Кафедра:Кафедра информатики', '2025-06-03 19:34:11'),
(31, 2, 'teachers', 'C', NULL, 'id:57, full_name:Смирнова А.А., Кафедра:Кафедра теоретической физики', '2025-06-03 19:34:11'),
(32, 2, 'schedule', 'U', 'id:34, date:2025-05-20, group_id:4, teacher_id:9, subject_id:4, room_id:10, lesson_type_id:1, time_slot_id:1', 'id:34, date:2025-05-20, group_id:4, teacher_id:7, subject_id:4, room_id:10, lesson_type_id:1, time_slot_id:1', '2025-06-03 19:40:29'),
(33, 2, 'schedule', 'C', NULL, 'id:81, date:2025-06-20, group_id:30, teacher_id:16, subject_id:12, room_id:7, lesson_type_id:3, time_slot_id:4', '2025-06-03 19:45:06'),
(34, 2, 'schedule', 'U', 'id:54, date:2025-05-20, group_id:7, teacher_id:6, subject_id:4, room_id:20, lesson_type_id:1, time_slot_id:2', 'id:54, date:2025-05-20, group_id:7, teacher_id:6, subject_id:17, room_id:20, lesson_type_id:1, time_slot_id:2', '2025-06-03 19:45:43'),
(35, 2, 'schedule', 'C', NULL, 'id:82, date:2025-06-12, group_id:12, teacher_id:14, subject_id:14, room_id:62, lesson_type_id:2, time_slot_id:4', '2025-06-03 19:53:48'),
(36, 1, 'schedule', 'C', NULL, 'id:83, date:2025-06-05, group_id:25, teacher_id:15, subject_id:15, room_id:57, lesson_type_id:2, time_slot_id:4', '2025-06-03 19:55:27'),
(37, 1, 'schedule', 'C', NULL, 'id:84, date:2025-06-11, group_id:41, teacher_id:16, subject_id:14, room_id:51, lesson_type_id:1, time_slot_id:4', '2025-06-03 20:05:19'),
(38, 1, 'schedule', 'C', NULL, 'id:85, date:2025-06-11, group_id:12, teacher_id:15, subject_id:15, room_id:67, lesson_type_id:2, time_slot_id:3', '2025-06-03 20:05:43'),
(39, 1, 'schedule', 'C', NULL, 'id:86, date:2025-06-12, group_id:30, teacher_id:11, subject_id:13, room_id:67, lesson_type_id:2, time_slot_id:3', '2025-06-03 20:07:19'),
(40, 3, 'schedule', 'C', NULL, 'id:87, date:2025-06-04, group_id:41, teacher_id:17, subject_id:15, room_id:7, lesson_type_id:2, time_slot_id:1', '2025-06-03 20:08:53'),
(41, 2, 'schedule', 'U', 'id:34, date:2025-05-20, group_id:4, teacher_id:7, subject_id:4, room_id:10, lesson_type_id:1, time_slot_id:1', 'id:34, date:2025-05-20, group_id:4, teacher_id:7, subject_id:17, room_id:10, lesson_type_id:1, time_slot_id:1', '2025-06-03 20:09:38'),
(42, 3, 'schedule', 'D', 'id:34, date:2025-05-20, group_id:4, teacher_id:7, subject_id:17, room_id:10, lesson_type_id:1, time_slot_id:1', NULL, '2025-06-03 20:11:37'),
(43, 3, 'schedule', 'U', 'id:35, date:2025-05-20, group_id:4, teacher_id:10, subject_id:5, room_id:11, lesson_type_id:2, time_slot_id:2', 'id:35, date:2025-05-20, group_id:4, teacher_id:20, subject_id:5, room_id:11, lesson_type_id:2, time_slot_id:2', '2025-06-03 20:11:44'),
(44, 3, 'schedule', 'U', 'id:54, date:2025-05-20, group_id:7, teacher_id:6, subject_id:17, room_id:20, lesson_type_id:1, time_slot_id:2', 'id:54, date:2025-05-20, group_id:7, teacher_id:6, subject_id:17, room_id:20, lesson_type_id:1, time_slot_id:5', '2025-06-03 20:12:58'),
(45, 3, 'schedule', 'D', 'id:3, date:2025-05-13, group_id:1, teacher_id:1, subject_id:1, room_id:3, lesson_type_id:1, time_slot_id:1', NULL, '2025-06-03 20:13:26'),
(46, 2, 'schedule', 'U', 'id:55, date:2025-05-20, group_id:10, teacher_id:6, subject_id:6, room_id:15, lesson_type_id:3, time_slot_id:3', 'id:55, date:2025-05-20, group_id:10, teacher_id:6, subject_id:6, room_id:15, lesson_type_id:3, time_slot_id:7', '2025-06-03 20:17:46'),
(47, 3, 'schedule', 'U', 'id:5, date:2025-05-14, group_id:1, teacher_id:1, subject_id:1, room_id:1, lesson_type_id:1, time_slot_id:2', 'id:5, date:2025-05-14, group_id:1, teacher_id:1, subject_id:1, room_id:1, lesson_type_id:4, time_slot_id:2', '2025-06-03 20:18:52'),
(48, 3, 'schedule', 'U', 'id:5, date:2025-05-14, group_id:1, teacher_id:1, subject_id:1, room_id:1, lesson_type_id:4, time_slot_id:2', 'id:5, date:2025-05-14, group_id:1, teacher_id:1, subject_id:1, room_id:1, lesson_type_id:4, time_slot_id:5', '2025-06-03 20:24:37'),
(49, 3, 'schedule', 'D', 'id:5, date:2025-05-14, group_id:1, teacher_id:1, subject_id:1, room_id:1, lesson_type_id:4, time_slot_id:5', NULL, '2025-06-03 20:24:47'),
(50, 3, 'schedule', 'C', NULL, 'id:88, date:2025-05-27, group_id:41, teacher_id:15, subject_id:30, room_id:57, lesson_type_id:2, time_slot_id:3', '2025-06-07 13:06:24'),
(57, 2, 'schedule', 'C', NULL, 'id:119, date:2025-06-04, group_id:4, teacher_id:2, subject_id:7, room_id:1, lesson_type_id:1, time_slot_id:3', '2025-06-07 13:34:33'),
(58, 2, 'schedule', 'C', NULL, 'id:120, date:2025-06-04, group_id:4, teacher_id:8, subject_id:4, room_id:9, lesson_type_id:3, time_slot_id:4', '2025-06-07 13:34:33'),
(59, 2, 'schedule', 'C', NULL, 'id:121, date:2025-06-05, group_id:4, teacher_id:3, subject_id:1, room_id:6, lesson_type_id:1, time_slot_id:3', '2025-06-07 13:34:33'),
(60, 2, 'schedule', 'C', NULL, 'id:122, date:2025-06-05, group_id:4, teacher_id:7, subject_id:9, room_id:2, lesson_type_id:5, time_slot_id:4', '2025-06-07 13:34:33'),
(61, 2, 'schedule', 'C', NULL, 'id:123, date:2025-06-06, group_id:4, teacher_id:5, subject_id:6, room_id:10, lesson_type_id:1, time_slot_id:3', '2025-06-07 13:34:33'),
(62, 2, 'schedule', 'C', NULL, 'id:124, date:2025-06-06, group_id:4, teacher_id:1, subject_id:2, room_id:7, lesson_type_id:4, time_slot_id:4', '2025-06-07 13:34:33'),
(63, 2, 'schedule', 'C', NULL, 'id:125, date:2025-06-07, group_id:4, teacher_id:9, subject_id:8, room_id:4, lesson_type_id:1, time_slot_id:3', '2025-06-07 13:34:33'),
(64, 2, 'schedule', 'C', NULL, 'id:126, date:2025-06-07, group_id:4, teacher_id:4, subject_id:3, room_id:5, lesson_type_id:2, time_slot_id:4', '2025-06-07 13:34:33'),
(65, 2, 'schedule', 'C', NULL, 'id:127, date:2025-06-08, group_id:4, teacher_id:6, subject_id:10, room_id:8, lesson_type_id:1, time_slot_id:3', '2025-06-07 13:34:33'),
(66, 2, 'schedule', 'C', NULL, 'id:128, date:2025-06-08, group_id:4, teacher_id:10, subject_id:5, room_id:3, lesson_type_id:5, time_slot_id:4', '2025-06-07 13:34:33'),
(67, 1, 'schedule', 'C', NULL, 'id:129, date:2025-06-18, group_id:26, teacher_id:15, subject_id:14, room_id:7, lesson_type_id:2, time_slot_id:4', '2025-06-10 21:19:57'),
(68, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:17, subject_id:31, room_id:7, lesson_type_id:1, time_slot_id:3', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:31, room_id:7, lesson_type_id:1, time_slot_id:3', '2025-06-10 21:20:19'),
(69, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:31, room_id:7, lesson_type_id:1, time_slot_id:3', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:7, lesson_type_id:1, time_slot_id:3', '2025-06-10 21:20:21'),
(70, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:7, lesson_type_id:1, time_slot_id:3', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:1, time_slot_id:3', '2025-06-10 21:20:23'),
(71, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:1, time_slot_id:3', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:3', '2025-06-10 21:20:25'),
(72, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:3', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:3', '2025-06-10 21:20:27'),
(73, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:3', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', '2025-06-10 21:20:29'),
(74, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', '2025-06-10 21:20:31'),
(75, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', '2025-06-10 21:20:33'),
(76, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', '2025-06-10 21:20:35'),
(77, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', '2025-06-10 21:20:39'),
(78, 1, 'schedule', 'U', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', 'id:69, date:2025-05-26, group_id:12, teacher_id:22, subject_id:5, room_id:4, lesson_type_id:4, time_slot_id:6', '2025-06-10 21:20:41'),
(79, 1, 'schedule', 'D', 'id:35, date:2025-05-20, group_id:4, teacher_id:20, subject_id:5, room_id:11, lesson_type_id:2, time_slot_id:2', NULL, '2025-06-10 21:23:26'),
(80, 1, 'schedule', 'C', NULL, 'id:129, date:2025-06-18, group_id:26, teacher_id:15, subject_id:14, room_id:7, lesson_type_id:2, time_slot_id:4', '2025-06-10 21:29:57');

-- --------------------------------------------------------

--
-- Структура таблицы `lesson_types`
--

CREATE TABLE `lesson_types` (
  `id` int NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `lesson_types`
--

INSERT INTO `lesson_types` (`id`, `name`) VALUES
(6, 'вид нагрузки не определен'),
(2, 'лабораторное занятие'),
(1, 'лекционное занятие'),
(3, 'практическое занятие'),
(4, 'факультатив'),
(5, 'экзамен');

-- --------------------------------------------------------

--
-- Структура таблицы `roles`
--

CREATE TABLE `roles` (
  `id` int NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `number` varchar(20) NOT NULL,
  `building` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `rooms`
--

INSERT INTO `rooms` (`id`, `number`, `building`) VALUES
(1, '101', '1'),
(2, '103', '1'),
(3, '104', '1'),
(4, '105', '1'),
(5, '106', '1'),
(6, '107', '1'),
(7, '201', '1'),
(8, '202', '2'),
(9, '203', '3'),
(10, '204', '4'),
(11, '205', '5'),
(12, '206', '6'),
(13, '207', '7'),
(14, '301', '2'),
(15, '302', '3'),
(16, '303', '4'),
(17, '304', '5'),
(18, '305', '6'),
(19, '306', '7'),
(20, '401', '1'),
(21, '402', '2'),
(43, '403', '3'),
(44, '404', '4'),
(45, '405', '5'),
(46, '406', '6'),
(47, '407', '7'),
(48, '114', '3'),
(49, '263', '1'),
(50, '321', '7'),
(51, '142', '5'),
(52, '256', '2'),
(53, '335', '4'),
(54, '126', '6'),
(55, '214', '1'),
(56, '341', '7'),
(57, '153', '2'),
(58, '224', '3'),
(59, '317', '5'),
(60, '132', '4'),
(61, '261', '6'),
(62, '198', '1'),
(63, '374', '7'),
(64, '115', '2'),
(65, '243', '5'),
(66, '326', '3'),
(67, '164', '6'),
(68, '211', '4'),
(69, '354', '1'),
(70, '123', '7'),
(71, '286', '2'),
(72, '399', '5'),
(73, '134', '3');

--
-- Триггеры `rooms`
--
DELIMITER $$
CREATE TRIGGER `before_insert_rooms` BEFORE INSERT ON `rooms` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM rooms WHERE number = NEW.number AND building = NEW.building) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Запись с таким номером кабинета и корпусом уже существует.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_update_rooms` BEFORE UPDATE ON `rooms` FOR EACH ROW BEGIN
    IF EXISTS (SELECT 1 FROM rooms WHERE number = NEW.number AND building = NEW.building AND id <> NEW.id) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Запись с таким номером кабинета и корпусом уже существует.';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_rooms_delete` AFTER DELETE ON `rooms` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, changed_at)
    VALUES (
        v_user_id,
        'rooms',
        'D',
        CONCAT('id:', OLD.id, ', number:', OLD.number, ', building:', IFNULL(OLD.building, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_rooms_insert` AFTER INSERT ON `rooms` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, new_value, changed_at)
    VALUES (
        v_user_id,
        'rooms',
        'C',
        CONCAT('id:', NEW.id, ', number:', NEW.number, ', building:', IFNULL(NEW.building, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_rooms_update` AFTER UPDATE ON `rooms` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, new_value, changed_at)
    VALUES (
        v_user_id,
        'rooms',
        'U',
        CONCAT('id:', OLD.id, ', number:', OLD.number, ', building:', IFNULL(OLD.building, '')),
        CONCAT('id:', NEW.id, ', number:', NEW.number, ', building:', IFNULL(NEW.building, '')),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `schedule`
--

CREATE TABLE `schedule` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `group_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `room_id` int NOT NULL,
  `lesson_type_id` int DEFAULT NULL,
  `time_slot_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `schedule`
--

INSERT INTO `schedule` (`id`, `date`, `group_id`, `teacher_id`, `subject_id`, `room_id`, `lesson_type_id`, `time_slot_id`) VALUES
(1, '2025-05-12', 1, 1, 1, 6, 1, 1),
(2, '2025-05-12', 2, 2, 2, 7, 6, 2),
(4, '2025-05-13', 2, 2, 2, 2, 2, 3),
(6, '2025-05-14', 2, 2, 2, 2, 2, 4),
(7, '2025-05-15', 1, 1, 1, 1, 1, 1),
(8, '2025-05-15', 2, 2, 2, 2, 2, 5),
(9, '2025-05-16', 1, 1, 1, 1, 1, 3),
(10, '2025-05-16', 2, 2, 2, 2, 2, 6),
(11, '2025-05-17', 1, 1, 1, 1, 1, 2),
(12, '2025-05-17', 2, 2, 2, 2, 2, 7),
(13, '2025-05-18', 1, 1, 1, 1, 1, 1),
(14, '2025-05-18', 2, 2, 2, 2, 2, 4),
(32, '2025-06-02', 4, 7, 2, 8, 2, 4),
(36, '2025-05-20', 4, 11, 6, 12, 3, 3),
(37, '2025-05-20', 4, 12, 7, 13, 1, 4),
(38, '2025-05-21', 4, 13, 8, 14, 2, 5),
(39, '2025-05-21', 4, 14, 9, 12, 3, 6),
(40, '2025-05-22', 4, 15, 10, 16, 1, 1),
(41, '2025-05-22', 4, 16, 11, 17, 2, 2),
(42, '2025-05-22', 4, 13, 1, 10, 3, 3),
(43, '2025-05-22', 4, 7, 2, 8, 1, 4),
(44, '2025-05-22', 4, 8, 3, 10, 2, 5),
(45, '2025-05-23', 4, 9, 4, 10, 3, 4),
(46, '2025-05-23', 4, 10, 5, 11, 2, 5),
(47, '2025-05-23', 4, 11, 6, 20, 2, 6),
(48, '2025-05-24', 4, 12, 7, 13, 3, 2),
(49, '2025-05-24', 4, 13, 8, 14, 1, 3),
(50, '2025-05-24', 4, 14, 9, 15, 2, 4),
(51, '2025-05-24', 4, 15, 10, 17, 3, 5),
(54, '2025-05-20', 7, 6, 17, 20, 1, 5),
(55, '2025-05-20', 10, 6, 6, 15, 3, 7),
(56, '2025-05-21', 7, 6, 9, 13, 3, 6),
(57, '2025-05-22', 8, 6, 10, 18, 1, 1),
(58, '2025-05-22', 5, 2, 12, 17, 5, 7),
(59, '2025-05-22', 7, 6, 3, 9, 2, 5),
(60, '2025-05-23', 10, 6, 5, 20, 3, 5),
(61, '2025-05-23', 5, 6, 6, 12, 2, 6),
(62, '2025-05-24', 7, 6, 8, 17, 1, 3),
(63, '2025-05-24', 13, 6, 10, 16, 5, 5),
(64, '2025-05-25', 6, 6, 1, 7, 2, 7),
(65, '2025-05-26', 16, 4, 2, 6, 1, 5),
(66, '2025-05-26', 2, 1, 1, 1, 6, 3),
(67, '2025-05-27', 4, 7, 5, 6, 3, 2),
(68, '2025-05-26', 8, 14, 23, 6, 5, 3),
(69, '2025-05-26', 12, 22, 5, 4, 4, 6),
(70, '2025-05-26', 7, 8, 7, 17, 4, 3),
(71, '2025-05-27', 6, 8, 9, 12, 4, 3),
(72, '2025-05-27', 16, 11, 11, 15, 3, 4),
(73, '2025-06-10', 23, 13, 13, 51, 2, 4),
(74, '2025-06-09', 41, 14, 15, 62, 2, 1),
(75, '2025-06-03', 4, 15, 15, 51, 6, 3),
(77, '2025-05-12', 1, 1, 1, 6, 1, 2),
(80, '2025-06-12', 41, 15, 14, 67, 2, 5),
(81, '2025-06-20', 30, 16, 12, 7, 3, 4),
(82, '2025-06-12', 12, 14, 14, 62, 2, 4),
(83, '2025-06-05', 25, 15, 15, 57, 2, 4),
(84, '2025-06-11', 41, 16, 14, 51, 1, 4),
(85, '2025-06-11', 12, 15, 15, 67, 2, 3),
(86, '2025-06-12', 30, 11, 13, 67, 2, 3),
(87, '2025-06-04', 41, 17, 15, 7, 2, 1),
(88, '2025-05-27', 41, 15, 30, 57, 2, 3),
(119, '2025-06-04', 4, 2, 7, 1, 1, 3),
(120, '2025-06-04', 4, 8, 4, 9, 3, 4),
(121, '2025-06-05', 4, 3, 1, 6, 1, 3),
(122, '2025-06-05', 4, 7, 9, 2, 5, 4),
(123, '2025-06-06', 4, 5, 6, 10, 1, 3),
(124, '2025-06-06', 4, 1, 2, 7, 4, 4),
(125, '2025-06-07', 4, 9, 8, 4, 1, 3),
(126, '2025-06-07', 4, 4, 3, 5, 2, 4),
(127, '2025-06-08', 4, 6, 10, 8, 1, 3),
(128, '2025-06-08', 4, 10, 5, 3, 5, 4),
(129, '2025-06-18', 26, 15, 14, 7, 2, 4);

--
-- Триггеры `schedule`
--
DELIMITER $$
CREATE TRIGGER `trg_schedule_delete` AFTER DELETE ON `schedule` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, changed_at)
    VALUES (
        v_user_id,
        'schedule',
        'D',
        CONCAT(
            'id:', OLD.id,
            ', date:', OLD.date,
            ', group_id:', OLD.group_id,
            ', teacher_id:', OLD.teacher_id,
            ', subject_id:', OLD.subject_id,
            ', room_id:', OLD.room_id,
            ', lesson_type_id:', IFNULL(OLD.lesson_type_id, ''),
            ', time_slot_id:', IFNULL(OLD.time_slot_id, '')
        ),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_schedule_insert` AFTER INSERT ON `schedule` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, new_value, changed_at)
    VALUES (
        v_user_id,
        'schedule',
        'C',
        CONCAT(
            'id:', NEW.id,
            ', date:', NEW.date,
            ', group_id:', NEW.group_id,
            ', teacher_id:', NEW.teacher_id,
            ', subject_id:', NEW.subject_id,
            ', room_id:', NEW.room_id,
            ', lesson_type_id:', IFNULL(NEW.lesson_type_id, ''),
            ', time_slot_id:', IFNULL(NEW.time_slot_id, '')
        ),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_schedule_update` AFTER UPDATE ON `schedule` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, new_value, changed_at)
    VALUES (
        v_user_id,
        'schedule',
        'U',
        CONCAT(
            'id:', OLD.id,
            ', date:', OLD.date,
            ', group_id:', OLD.group_id,
            ', teacher_id:', OLD.teacher_id,
            ', subject_id:', OLD.subject_id,
            ', room_id:', OLD.room_id,
            ', lesson_type_id:', IFNULL(OLD.lesson_type_id, ''),
            ', time_slot_id:', IFNULL(OLD.time_slot_id, '')
        ),
        CONCAT(
            'id:', NEW.id,
            ', date:', NEW.date,
            ', group_id:', NEW.group_id,
            ', teacher_id:', NEW.teacher_id,
            ', subject_id:', NEW.subject_id,
            ', room_id:', NEW.room_id,
            ', lesson_type_id:', IFNULL(NEW.lesson_type_id, ''),
            ', time_slot_id:', IFNULL(NEW.time_slot_id, '')
        ),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `description`) VALUES
(1, 'Математика', 'Математический анализ и алгебра'),
(2, 'Программирование', 'Основы программирования'),
(3, 'Химия', 'Общая химия'),
(4, 'История', 'История России'),
(5, 'Английский язык', 'Изучение английского языка'),
(6, 'Философия', 'Введение в философию'),
(7, 'Алгебра', 'Линейная алгебра'),
(8, 'Высшая математика', 'Дифференциальное и интегральное исчисление, ряды, основы математического анализа'),
(9, 'Математическая статистика', 'Теория вероятностей и основы статистического анализа'),
(10, 'Физика твердого тела', 'Кристаллические структуры, свойства твердых тел, полупроводники'),
(11, 'Квантовая механика', 'Основы квантовой теории, уравнение Шрёдингера, операторы и наблюдаемые'),
(12, 'Теоретическая механика', 'Механика материальных точек и твёрдых тел, динамика и статика'),
(13, 'Компьютерные сети', 'Протоколы передачи данных, архитектура сетей, маршрутизация'),
(14, 'Базы данных', 'Проектирование, нормализация, SQL, транзакции'),
(15, 'Операционные системы', 'Архитектура ОС, процессы, потоки, управление памятью'),
(16, 'Теория алгоритмов', 'Алгоритмы сортировки, поиска, сложность, вычислимость'),
(17, 'Архитектура вычислительных систем', 'Строение компьютеров, процессоры, память, периферия'),
(18, 'Экономическая теория', 'Основы микро- и макроэкономики, рыночные механизмы'),
(19, 'Финансовый менеджмент', 'Управление финансами предприятия, инвестиции, анализ рисков'),
(20, 'Маркетинг', 'Стратегии продвижения, анализ рынка, потребительское поведение'),
(21, 'Гражданское право', 'Основы гражданского законодательства, договорные отношения'),
(22, 'Уголовное право', 'Преступления, наказания, уголовный процесс'),
(23, 'Административное право', 'Правовые основы государственного управления'),
(24, 'Генетика', 'Основы наследственности, гены, генетические мутации'),
(25, 'Молекулярная биология', 'Структура и функции биомолекул, ДНК, РНК, белки'),
(26, 'Биоинформатика', 'Применение вычислительных методов в биологии и медицине'),
(27, 'Органическая химия', 'Строение, свойства и реакции органических соединений'),
(28, 'Физическая химия', 'Термодинамика, химическая кинетика, электрохимия'),
(29, 'Электротехника', 'Электрические цепи, законы Кирхгофа, электромагнетизм'),
(30, 'Теория автоматического управления', 'Системы управления, обратные связи, устойчивость'),
(31, 'Инженерная графика', 'Чертежи, проекции, основы САПР'),
(32, 'Психология', 'Общие закономерности психики, когнитивные процессы'),
(33, 'Социология', 'Структуры общества, социальные институты'),
(34, 'Политология', 'Политические системы, власть, международные отношения'),
(35, 'Философия науки', 'Методология и история науки, научное познание'),
(36, 'Логика', 'Формальные системы, законы мышления, дедукция и индукция'),
(37, 'История искусств', 'Эпохи, стили, направления в искусстве'),
(38, 'Русский язык и культура речи', 'Орфография, пунктуация, речевые нормы'),
(39, 'Математическое моделирование', 'Построение и анализ математических моделей'),
(40, 'Эконометрика', 'Статистические методы в экономике, регрессионный анализ'),
(41, 'Информационная безопасность', 'Методы защиты информации, криптография, политика безопасности'),
(42, 'Нейронные сети', 'Искусственные нейронные сети, машинное обучение'),
(43, 'Теория информации', 'Количественные характеристики информации, энтропия'),
(44, 'Экология', 'Взаимодействие организмов и окружающей среды'),
(45, 'Международное право', 'Правовые нормы международных отношений'),
(46, 'Физическая культура', 'Теория и практика физической активности'),
(47, 'Дискретная математика', 'Множества, графы, булева алгебра'),
(48, 'Статистика', 'Сбор, обработка и анализ статистических данных'),
(49, 'Педагогика', 'Теории и методы обучения и воспитания'),
(50, 'Экспериментальная физика', 'Лабораторные методы исследования физических явлений'),
(51, 'Машинное обучение', 'Алгоритмы и методы обучения с учителем и без учителя'),
(52, 'Культурология', 'Культура, традиции, символы, межкультурная коммуникация'),
(53, 'Правоведение', 'Общие основы права, правовые системы'),
(54, 'Информационные технологии', 'Современные IT-системы, программные комплексы'),
(55, 'Финансовое право', 'Правовое регулирование финансовой деятельности'),
(56, 'Государственное и муниципальное управление', 'Организация и функционирование органов власти');

--
-- Триггеры `subjects`
--
DELIMITER $$
CREATE TRIGGER `trg_subjects_delete` AFTER DELETE ON `subjects` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, changed_at)
    VALUES (
        v_user_id,
        'subjects',
        'D',
        CONCAT('id:', OLD.id, ', name:', OLD.name, ', description:', IFNULL(OLD.description, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_subjects_insert` AFTER INSERT ON `subjects` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, new_value, changed_at)
    VALUES (
        v_user_id,
        'subjects',
        'C',
        CONCAT('id:', NEW.id, ', name:', NEW.name, ', description:', IFNULL(NEW.description, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_subjects_update` AFTER UPDATE ON `subjects` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, new_value, changed_at)
    VALUES (
        v_user_id,
        'subjects',
        'U',
        CONCAT('id:', OLD.id, ', name:', OLD.name, ', description:', IFNULL(OLD.description, '')),
        CONCAT('id:', NEW.id, ', name:', NEW.name, ', description:', IFNULL(NEW.description, '')),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `Кафедра` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `teachers`
--

INSERT INTO `teachers` (`id`, `full_name`, `Кафедра`) VALUES
(1, 'Иванов И.И.', 'Кафедра высшей математики'),
(2, 'Сидоров С.С.', 'Кафедра программирования'),
(3, 'Кузнецова А.А.', 'Кафедра физики'),
(4, 'Смирнов С.С.', 'Кафедра истории'),
(5, 'Петрова А.А.', 'Кафедра иностранных языков'),
(6, 'Власов А.П.', 'Кафедра архитектуры'),
(7, 'Мельникова Е.В.', 'Кафедра архитектуры'),
(8, 'Громов И.С.', 'Кафедра строительных конструкций'),
(9, 'Семенова Л.К.', 'Кафедра строительных конструкций'),
(10, 'Дьячков П.Н.', 'Кафедра инженерных систем и экологии'),
(11, 'Борисова Ю.М.', 'Кафедра инженерных систем и экологии'),
(12, 'Панин В.Л.', 'Кафедра экономики и управления'),
(13, 'Калинина Т.И.', 'Кафедра экономики и управления'),
(14, 'Шаров С.Г.', 'Кафедра землеустройства и кадастров'),
(15, 'Кузьмина О.С.', 'Кафедра землеустройства и кадастров'),
(16, 'Рябов А.Д.', 'Кафедра информационных технологий'),
(17, 'Морозова В.А.', 'Кафедра информационных технологий'),
(18, 'Александров В.В.', 'Кафедра высшей математики'),
(19, 'Белов С.П.', 'Кафедра программирования'),
(20, 'Васильева Е.Н.', 'Кафедра физики'),
(21, 'Григорьев А.А.', 'Кафедра истории'),
(22, 'Данилова М.С.', 'Кафедра иностранных языков'),
(23, 'Егоров П.В.', 'Кафедра архитектуры'),
(24, 'Журавлева Т.К.', 'Кафедра строительных конструкций'),
(25, 'Зайцев К.Д.', 'Кафедра инженерных систем и экологии'),
(26, 'Иванова Л.В.', 'Кафедра экономики и управления'),
(27, 'Козлов Н.М.', 'Кафедра землеустройства и кадастров'),
(28, 'Лебедева О.А.', 'Кафедра информационных технологий'),
(29, 'Морозов С.В.', 'Кафедра высшей математики'),
(30, 'Николаева Е.П.', 'Кафедра программирования'),
(31, 'Орлов Д.И.', 'Кафедра физики'),
(32, 'Петрова А.В.', 'Кафедра истории'),
(33, 'Романов К.С.', 'Кафедра иностранных языков'),
(34, 'Семенова Т.Н.', 'Кафедра архитектуры'),
(35, 'Тарасов И.Ю.', 'Кафедра строительных конструкций'),
(36, 'Ушаков В.А.', 'Кафедра инженерных систем и экологии'),
(37, 'Федорова Л.Д.', 'Кафедра экономики и управления'),
(38, 'Харитонов М.В.', 'Кафедра землеустройства и кадастров'),
(39, 'Цветкова Н.С.', 'Кафедра информационных технологий'),
(40, 'Чистяков А.П.', 'Кафедра высшей математики'),
(41, 'Шестаков В.Н.', 'Кафедра программирования'),
(56, 'Петров Павел Петрович', 'Кафедра прикладной информатики'),
(57, 'Смирнова А.А.', 'Кафедра теоретической физики');

--
-- Триггеры `teachers`
--
DELIMITER $$
CREATE TRIGGER `trg_teachers_delete` AFTER DELETE ON `teachers` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, changed_at)
    VALUES (
        v_user_id,
        'teachers',
        'D',
        CONCAT('id:', OLD.id, ', full_name:', OLD.full_name, ', Кафедра:', IFNULL(OLD.`Кафедра`, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_teachers_insert` AFTER INSERT ON `teachers` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, new_value, changed_at)
    VALUES (
        v_user_id,
        'teachers',
        'C',
        CONCAT('id:', NEW.id, ', full_name:', NEW.full_name, ', Кафедра:', IFNULL(NEW.`Кафедра`, '')),
        NOW()
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_teachers_update` AFTER UPDATE ON `teachers` FOR EACH ROW BEGIN
    DECLARE v_user_id INT DEFAULT 2;

    IF @current_user_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE id = @current_user_id) THEN
        SET v_user_id = @current_user_id;
    END IF;

    INSERT INTO history (user_id, table_name, change_type, old_value, new_value, changed_at)
    VALUES (
        v_user_id,
        'teachers',
        'U',
        CONCAT('id:', OLD.id, ', full_name:', OLD.full_name, ', Кафедра:', IFNULL(OLD.`Кафедра`, '')),
        CONCAT('id:', NEW.id, ', full_name:', NEW.full_name, ', Кафедра:', IFNULL(NEW.`Кафедра`, '')),
        NOW()
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `time_slots`
--

CREATE TABLE `time_slots` (
  `id` int NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `label` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `time_slots`
--

INSERT INTO `time_slots` (`id`, `start_time`, `end_time`, `label`) VALUES
(1, '08:20:00', '09:55:00', '08:20-09:55'),
(2, '10:05:00', '11:40:00', '10:05-11:40'),
(3, '12:05:00', '13:40:00', '12:05-13:40'),
(4, '13:55:00', '15:30:00', '13:55-15:30'),
(5, '15:40:00', '17:15:00', '15:40-17:15'),
(6, '17:25:00', '19:00:00', '17:25-19:00'),
(7, '19:10:00', '20:45:00', '19:10-20:45');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `login` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `login`, `password_hash`, `email`, `created_at`) VALUES
(1, 'Admin', '3322', 'admin@adimn.ru', '2025-05-15 11:02:29'),
(2, 'system', '', NULL, '2025-06-03 16:23:36'),
(3, 'newuser', '5225', 'newuser@example.com', '2025-06-03 17:06:32');

-- --------------------------------------------------------

--
-- Структура таблицы `user_roles`
--

CREATE TABLE `user_roles` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `time_slot_id` (`time_slot_id`);

--
-- Индексы таблицы `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `history`
--
ALTER TABLE `history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `lesson_types`
--
ALTER TABLE `lesson_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Индексы таблицы `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Индексы таблицы `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_group_time` (`date`,`time_slot_id`,`group_id`),
  ADD UNIQUE KEY `unique_teacher_time` (`date`,`time_slot_id`,`teacher_id`),
  ADD UNIQUE KEY `unique_room_time` (`date`,`time_slot_id`,`room_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `lesson_type_id` (`lesson_type_id`),
  ADD KEY `idx_date_time` (`date`),
  ADD KEY `fk_schedule_time_slot` (`time_slot_id`);

--
-- Индексы таблицы `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `label` (`label`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Индексы таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`user_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `consultations`
--
ALTER TABLE `consultations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `history`
--
ALTER TABLE `history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `lesson_types`
--
ALTER TABLE `lesson_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `schedule`
--
ALTER TABLE `schedule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `consultations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`),
  ADD CONSTRAINT `consultations_ibfk_3` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`);

--
-- Ограничения внешнего ключа таблицы `history`
--
ALTER TABLE `history`
  ADD CONSTRAINT `history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ограничения внешнего ключа таблицы `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `fk_schedule_time_slot` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_4` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `schedule_ibfk_5` FOREIGN KEY (`lesson_type_id`) REFERENCES `lesson_types` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_roles_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
