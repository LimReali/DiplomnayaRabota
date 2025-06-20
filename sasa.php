<?php
$host = 'MySQL-8.0'; // IP сервера MySQL
$user = 'root';      // пользователь
$pass = '';          // пароль (пустой для OpenServer по умолчанию)

try {
    $pdo = new PDO("mysql:host=$host;port=3306", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Тестовый запрос
    $stmt = $pdo->query("SHOW DATABASES;");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Подключение успешно! Доступные базы данных:\n";
    print_r($databases);
    
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}