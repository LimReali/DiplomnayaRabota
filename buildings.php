<?php
// buildings.php — страница с информацией о корпусах университета
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Корпуса университета</title>
    <link rel="icon" href="img.png" type="image/jpeg" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Подключаем иконки Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Подключаем стили боковой панели -->
    <link rel="stylesheet" href="sidebar.css" />
    <style>
        /* Контейнер основного контента */
        .content {
            max-width: 1100px;
            margin: 0 auto; /* центрируем по горизонтали */
        }
        /* Заголовок страницы */
        .buildings-title {
            color: #7b203a; /* бордовый цвет */
            font-size: 2.2rem;
            margin-bottom: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        /* Список карточек корпусов */
        .buildings-list {
            display: flex;
            flex-direction: column; /* вертикальный список */
            gap: 28px; /* расстояние между карточками */
            margin-bottom: 40px;
        }
        /* Карточка корпуса */
        .building-card {
            background: #fff; /* белый фон */
            border-radius: 12px; /* скругление углов */
            box-shadow: 0 2px 12px rgba(123, 32, 58, 0.07); /* лёгкая тень */
            padding: 24px 20px 20px 20px;
            border-left: 6px solid #7b203a; /* бордовая полоска слева */
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-width: 1000px;
        }
        /* Название корпуса */
        .building-name {
            color: #7b203a;
            font-size: 1.3rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px; /* расстояние между иконкой и текстом */
        }
        /* Адрес корпуса */
        .building-address {
            color: #a83250; /* более светлый бордовый */
            font-size: 1.05rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        /* Ссылки в карточке корпуса */
        .building-links {
            display: flex;
            gap: 16px;
            font-size: 0.95rem;
        }
        /* Стили для ссылок */
        .building-links a {
            color: #a83250;
            text-decoration: none;
            border-bottom: 1px dashed transparent;
            transition: border-color 0.3s;
        }
        /* При наведении подчеркивание */
        .building-links a:hover {
            border-color: #a83250;
        }
        /* Колонка с картой и фото корпуса */
        .building-map-photo-column {
            display: flex;
            flex-direction: column;
            gap: 24px;
            max-width: 1000px;
        }
        /* Контейнер карты */
        .building-map {
            width: 100%;
            height: 546px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(123, 32, 58, 0.09);
            background: #f8f5f7; /* светлый фон */
        }
        /* Фото корпуса */
        .building-photo img {
            width: 100%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 2px 12px rgba(123, 32, 58, 0.08);
            display: block;
        }
        /* Адаптивные стили для мобильных устройств */
        @media (max-width: 768px) {
            .building-map-photo-column {
                max-width: 100%;
                padding: 0 16px;
            }
            .building-map {
                height: 300px; /* уменьшаем высоту карты */
            }
            .buildings-title {
                margin-top: 60px; /* отступ сверху */
            }
        }
    </style>
</head>
<body>
    <!-- Верхняя панель для мобильных устройств -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Включаем боковую панель -->
        <?php include 'sidebar.php'; ?>
        <main class="content">
            <!-- Заголовок страницы с иконкой -->
            <div class="buildings-title"><i class="fas fa-university"></i> Корпуса университета</div>
            <!-- Список корпусов -->
            <div class="buildings-list">
                <!-- Карточка корпуса №1 -->
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №1 (библиотека)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> Соляная площадь, 2 к1</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
                <!-- Карточка корпуса №2 -->
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №2 (приёмная комиссия)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> Соляная площадь, 2 к2</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                        <a href="https:///tomsk/search/%D0%A2%D0%B3%D0%B0%D1%81%D1%83%20%D0%BA%D0%BE%D1%80%D0%BF%D1%83%D1%81%D0%B0"
                            target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
                <!-- Аналогично остальные карточки корпусов -->
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №6 (региональный проектный институт)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> Соляная площадь, 2 к6</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                        <a href="https:///tomsk/search/%D0%A2%D0%B3%D0%B0%D1%81%D1%83%20%D0%BA%D0%BE%D1%80%D0%BF%D1%83%D1%81%D0%B0"
                            target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №9 (институт непрерывного образования)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> улица Розы Люксембург, 13</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №10 (институт международных связей и интернационализации образования)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> улица 79 Гвардейской Дивизии, 25</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                        <a href="https://maps.yandex.ru" target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №11 (институт кадастра, экономики и инженерных систем в строительстве)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> улица 79 Гвардейской Дивизии, 25/1</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                        <a href="https:///tomsk/search/%D0%A2%D0%B3%D0%B0%D1%81%D1%83%20%D0%BA%D0%BE%D1%80%D0%BF%D1%83%D1%81%D0%B0"
                            target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
                <div class="building-card">
                    <div class="building-name"><i class="fas fa-building"></i> Корпус №12 (научно-техническая библиотека)</div>
                    <div class="building-address"><i class="fas fa-map-marker-alt"></i> улица 79 Гвардейской Дивизии, 25/2</div>
                    <div class="building-links">
                        <a href="https://" target="_blank" rel="noopener noreferrer"></a>
                    </div>
                </div>
            </div>
            <!-- Колонка с картой и фото корпусов -->
            <div class="building-map-photo-column">
                <div class="building-map">
                    <!-- Встраиваем Яндекс.Карту с помощью скрипта -->
                    <script type="text/javascript" charset="utf-8" async
                        src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A4ee202722e44a699481007066fbf54996a1ebb7e3e82818173bd9813f9dc8062&amp;width=100%25&amp;height=546&amp;lang=ru_RU&amp;scroll=true"></script>
                </div>
                <div class="building-photo">
                    <!-- Фото корпусов -->
                    <img src="passport-map2018_resize.jpg" alt="Фото корпусов" />
                </div>
            </div>
        </main>
    </div>
    <!-- Подключаем скрипт для работы боковой панели -->
    <script src="sidebar.js"></script>
</body>
</html>
