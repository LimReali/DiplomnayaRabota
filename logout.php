<?php
session_start();
session_unset();     // Очистить все переменные сессии
session_destroy();   // Уничтожить сессию
header('Location: login.php'); // Перенаправление на страницу входа
exit();
