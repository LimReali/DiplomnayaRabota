<?php
// Запускаем сессию для управления состоянием пользователя (авторизация и т.п.)
session_start();
// Подключаем файл с функцией для получения соединения с базой данных
require_once 'database.php';
// Инициализируем переменную для хранения сообщений об ошибках
$error = '';
// Проверяем, был ли отправлен POST-запрос (форма отправлена)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем и очищаем введённые пользователем логин и пароль
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    // Проверяем, что оба поля заполнены
    if ($username && $password) {
        // Получаем соединение с базой данных
        $conn = getDbConnection();
        // Подготавливаем SQL-запрос для поиска пользователя с указанным логином и хэшем пароля
        $stmt = $conn->prepare("SELECT id FROM users WHERE login = ? AND password_hash = ?");
        // Привязываем параметры: два строковых значения (логин и пароль)
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        // Получаем результат запроса
        $result = $stmt->get_result();
        // Если пользователь найден
        if ($user = $result->fetch_assoc()) {
            // Сохраняем ID и имя пользователя в сессию
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            // Перенаправляем на главную страницу администратора
            header('Location: MainAdmin.php');
            exit();
        } else {
            // Если пользователь не найден — выводим ошибку
            $error = "Неверный логин или пароль";
        }
    } else {
        // Если поля не заполнены — выводим ошибку
        $error = "Введите имя пользователя и пароль";
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Вход в систему</title>
    <style>
        /* Сброс стилей для всех элементов */
        * {
            box-sizing: border-box;
        }
        /* Стили для тела страницы */
        body {
            background: linear-gradient(135deg, #5a1e2a, #8b3a4b);
            /* багровый градиент */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            /* используем flex для центрирования */
            justify-content: center;
            /* по горизонтали */
            align-items: center;
            /* по вертикали */
            height: 100vh;
            /* высота экрана */
            margin: 0;
            color: #f2e6e9;
            /* светлый цвет текста для контраста */
        }
        /* Контейнер формы входа */
        .login-container {
            background: rgba(75, 20, 35, 0.85);
            /* тёмно-багровый полупрозрачный фон */
            padding: 40px 50px;
            border-radius: 12px;
            /* скругленные углы */
            box-shadow: 0 8px 24px rgba(75, 20, 35, 0.8);
            /* тень */
            width: 320px;
            text-align: center;
            transition: transform 0.2s ease;
        }
        /* Эффект при наведении на контейнер */
        .login-container:hover {
            transform: scale(1.03);
            /* немного увеличиваем */
            box-shadow: 0 10px 30px rgba(75, 20, 35, 0.9);
        }
        /* Заголовок формы */
        h2 {
            margin-bottom: 30px;
            font-weight: 700;
            font-size: 28px;
            letter-spacing: 1px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            /* тень для читаемости */
        }
        /* Стиль формы */
        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            /* расстояние между полями */
        }
        /* Метки для полей */
        label {
            display: flex;
            flex-direction: column;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #f2d7db;
            /* светло-розовый оттенок */
        }
        /* Поля ввода */
        input[type="text"],
        input[type="password"] {
            margin-top: 6px;
            padding: 12px 15px;
            border-radius: 8px;
            border: none;
            outline: none;
            font-size: 16px;
            background-color: rgba(0, 0, 0, 0.15);
            /* полупрозрачный черный фон */
            color: #fff;
            transition: box-shadow 0.3s ease;
        }
        /* Подсветка поля при фокусе */
        input[type="text"]:focus,
        input[type="password"]:focus {
            box-shadow: 0 0 8px 3px rgba(139, 58, 75, 0.7);
            background-color: rgba(0, 0, 0, 0.25);
            color: #fff;
        }
        /* Кнопка входа */
        button {
            padding: 14px;
            border: none;
            border-radius: 8px;
            background-color: #b33951;
            /* насыщенный багровый */
            color: white;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(179, 57, 81, 0.7);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        /* Эффект при наведении на кнопку */
        button:hover {
            background-color: #8b2e3f;
            /* темнее */
            box-shadow: 0 6px 20px rgba(139, 46, 63, 0.9);
        }
        /* Сообщение об ошибке */
        .error-message {
            background-color: rgba(179, 57, 81, 0.85);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(179, 57, 81, 0.7);
            user-select: none;
            /* запрет выделения */
            color: #fff;
            text-align: center;
        }
        /* Адаптив для маленьких экранов */
        @media (max-width: 400px) {
            .login-container {
                width: 90%;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход в систему</h2>
        <!-- Вывод ошибки, если есть -->
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <!-- Форма входа -->
        <form method="POST" autocomplete="off" novalidate>
            <label for="username">Имя пользователя
                <input type="text" id="username" name="username" required autofocus
                    placeholder="Введите имя пользователя" />
            </label>
            <label for="password">Пароль
                <input type="password" id="password" name="password" required placeholder="Введите пароль" />
            </label>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>