<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Полезные строительные ресурсы</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="img.png" type="image/jpeg" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="sidebar.css" />
    <style>
        /* Основной контейнер контента */
        main.content {
            max-width: 900px;
            /* Максимальная ширина контейнера */
            margin: 40px auto;
            /* Центрирование с отступом сверху и снизу */
            padding: 0 20px 40px;
            /* Внутренние отступы: слева/справа и снизу */
        }
        /* Заголовки второго уровня */
        h2 {
            text-align: center;
            /* Выравнивание по центру */
            margin-bottom: 25px;
            /* Отступ снизу */
            color: #2c3e50;
            /* Темно-синий цвет текста */
        }
        /* Контейнер для вкладок (табов) */
        .tabs {
            display: flex;
            /* Горизонтальное расположение элементов */
            justify-content: center;
            /* Центрирование по горизонтали */
            gap: 15px;
            /* Отступ между кнопками */
            margin-bottom: 30px;
            /* Отступ снизу */
            flex-wrap: wrap;
            /* Перенос кнопок на новую строку при нехватке места */
        }
        /* Кнопки вкладок */
        .tab-btn {
            background-color: #a860width: 60px;
            /* Ошибка: неверный цвет, должно быть, например, #a86060 */
            color: white;
            /* Белый цвет текста */
            border: none;
            /* Без рамки */
            padding: 12px 22px;
            /* Внутренние отступы */
            font-size: 16px;
            /* Размер шрифта */
            font-weight: 600;
            /* Жирный текст */
            border-radius: 8px;
            /* Скругленные углы */
            cursor: pointer;
            /* Курсор в виде руки */
            transition: background-color 0.3s ease;
            /* Плавный переход цвета фона */
            user-select: none;
            /* Запрет выделения текста */
        }
        /* Активная вкладка и эффект при наведении */
        .tab-btn.active,
        .tab-btn:hover {
            background-color: #7b203a;
            /* Темно-бордовый цвет */
        }
        /* Контейнер содержимого вкладок */
        .tab-content {
            display: none;
            /* Скрываем по умолчанию */
            background: #fff;
            /* Белый фон */
            border-radius: 8px;
            /* Скругление углов */
            padding: 25px 30px;
            /* Внутренние отступы */
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            /* Тень */
            color: #34495e;
            /* Темно-серый цвет текста */
            line-height: 1.5;
            /* Межстрочный интервал */
        }
        /* Отображение активного таба */
        .tab-content.active {
            display: block;
        }
        /* Стили для ссылок с классом link */
        a.link {
            color: #a860width: 60px;
            /* Ошибка: неверный цвет, например #a86060 */
            font-weight: 600;
            /* Жирный текст */
            text-decoration: none;
            /* Без подчеркивания */
        }
        /* Эффект при наведении на ссылки */
        a.link:hover {
            text-decoration: underline;
            /* Подчеркивание */
        }
        /* Отступы для списков */
        ul {
            margin-left: 20px;
            /* Отступ слева */
        }
        /* Кнопка "Назад" */
        .back-btn {
            margin-top: 30px;
            /* Отступ сверху */
            display: inline-block;
            /* Блочно-строчный элемент */
            background-color: #e74c3c;
            /* Красный фон */
            color: white;
            /* Белый текст */
            padding: 12px 28px;
            /* Внутренние отступы */
            border-radius: 8px;
            /* Скругленные углы */
            font-weight: 700;
            /* Жирный текст */
            cursor: pointer;
            /* Курсор в виде руки */
            border: none;
            /* Без рамки */
            transition: background-color 0.3s ease;
            /* Плавный переход цвета фона */
            text-decoration: none;
            /* Без подчеркивания */
        }
        /* Эффект при наведении на кнопку */
        .back-btn:hover {
            background-color: #c0392b;
            /* Темно-красный фон */
        }
        /* Стилизация скроллбаров для Firefox */
        * {
            scrollbar-width: thin;
            /* Тонкий ползунок */
            scrollbar-color: #a860width: 60px #f0e6e9;
            /* Ошибка: неверный цвет, должно быть, например, #a86060 */
        }
        /* Стилизация скроллбаров для WebKit-браузеров (Chrome, Safari, Edge) */
        *::-webkit-scrollbar {
            width: 10px;
            /* Ширина вертикального скроллбара */
            height: 10px;
            /* Высота горизонтального скроллбара */
        }
        *::-webkit-scrollbar-track {
            background: #f0e6e9;
            /* Цвет дорожки */
            border-radius: 8px;
            /* Скругление углов дорожки */
        }
        *::-webkit-scrollbar-thumb {
            background-color: #a860width: 60px;
            /* Ошибка: неверный цвет, например #a86060 */
            border-radius: 8px;
            /* Скругление углов ползунка */
            border: 2px solid #f0e6e9;
            /* Отступ вокруг ползунка для эффекта */
            transition: background-color 0.3s ease;
            /* Плавный переход цвета */
            cursor: pointer;
            /* Курсор в виде руки */
        }
        *::-webkit-scrollbar-thumb:hover {
            background-color: #7b203a;
            /* Цвет ползунка при наведении */
        }
        /* Контейнер для карточек новостей */
        .news-cards {
            display: flex;
            /* Горизонтальное расположение карточек */
            flex-wrap: wrap;
            /* Перенос на новую строку при нехватке места */
            gap: 24px;
            /* Отступы между карточками */
            justify-content: center;
            /* Центрирование карточек */
            margin: 30px 0;
            /* Отступы сверху и снизу */
        }
        /* Карточка новости */
        .news-card {
            background: #fff;
            /* Белый фон */
            border-radius: 14px;
            /* Скругленные углы */
            box-shadow: 0 6px 18px rgba(168, 50, 80, 0.09);
            /* Тень */
            width: 340px;
            /* Ширина карточки */
            min-height: 340px;
            /* Минимальная высота */
            display: flex;
            flex-direction: column;
            overflow: hidden;
            /* Обрезаем содержимое, выходящее за границы */
            transition: transform 0.2s;
            /* Плавное изменение трансформации */
            border: 1.5px solid #f3e2e8;
            /* Рамка */
            position: relative;
        }
        /* Эффект при наведении на карточку */
        .news-card:hover {
            transform: translateY(-7px) scale(1.03);
            /* Поднятие и увеличение */
            box-shadow: 0 12px 30px rgba(168, 50, 80, 0.16);
            /* Усиленная тень */
        }
        /* Изображение в карточке */
        .news-card img {
            width: 100%;
            /* Ширина по контейнеру */
            height: 120px;
            /* Фиксированная высота */
            object-fit: cover;
            /* Обрезка изображения по размеру */
            background: #f8f5f7;
            /* Фон на случай загрузки */
        }
        /* Контент новости */
        .news-card .news-content {
            padding: 18px 20px 12px 20px;
            flex: 1;
            /* Занимает оставшееся пространство */
            display: flex;
            flex-direction: column;
        }
        /* Заголовок новости */
        .news-card .news-title {
            font-size: 1.13rem;
            font-weight: 700;
            color: #a83250;
            /* Бордовый цвет */
            margin-bottom: 8px;
        }
        /* Источник новости */
        .news-card .news-source {
            font-size: 0.92rem;
            color: #7b203a;
            margin-bottom: 8px;
        }
        /* Текст новости */
        .news-card .news-text {
            color: #34495e;
            font-size: 0.97rem;
            flex: 1;
        }
        /* Ссылка "Подробнее" */
        .news-card .news-link {
            margin-top: 12px;
            color: #7b203a;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.97rem;
            transition: color 0.2s;
        }
        /* Эффект при наведении на ссылку */
        .news-card .news-link:hover {
            color: #a83250;
        }
        /* Адаптив для экранов шириной до 900px */
        @media (max-width: 900px) {
            .news-cards {
                flex-direction: column;
                /* Вертикальное расположение карточек */
                align-items: center;
                /* Центрирование */
            }
            .news-card {
                width: 98%;
                /* Почти полная ширина */
            }
        }
    </style>
</head>
<body>
    <!-- Мобильная верхняя панель -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <!-- Кнопка открытия мобильного меню -->
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <!-- Заголовок панели -->
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Включаем боковую панель -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <!-- Заголовок страницы -->
            <h2>Полезные строительные ресурсы и приложения</h2>
            <!-- Вкладки для переключения разделов -->
            <div class="tabs" role="tablist" aria-label="Основные разделы">
                <button class="tab-btn active" role="tab" aria-selected="true" aria-controls="tab1"
                    id="tab1-btn">Программы для проектирования и расчетов</button>
                <button class="tab-btn" role="tab" aria-selected="false" aria-controls="tab2" id="tab2-btn">Новости и
                    статьи по строительству</button>
            </div>
            <section id="tab1" class="tab-content active" role="tabpanel" aria-labelledby="tab1-btn" tabindex="0">
                <!-- Заголовок раздела -->
                <h3>Программы для проектирования и расчетов</h3>
                <!-- Вводный абзац с описанием раздела -->
                <p>
                    Современные технологии трехмерного моделирования и автоматизации расчетов помогают специалистам и
                    студентам эффективно работать в строительной сфере. Ниже представлены популярные программы с
                    краткими обзорами, иконками, примерами интерфейса и полезными ссылками.
                </p>
                <!-- Первый программный продукт: AutoCAD -->
                <article style="margin-bottom: 40px;">
                    <!-- Заголовок с иконкой программы -->
                    <h4>
                        <img src="icons/autocad.png" alt="AutoCAD"
                            style="width:60px; vertical-align:middle; margin-right:8px;">
                        AutoCAD
                    </h4>
                    <!-- Краткое описание программы -->
                    <p>
                        Одна из самых популярных программ для 2D и 3D проектирования. Используется для создания
                        чертежей, планов и архитектурных проектов.
                    </p>
                    <!-- Скриншот интерфейса программы -->
                    <img src="images/autocad_screenshot.jpg" alt="Скриншот AutoCAD"
                        style="max-width:100%; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:10px;">
                    <!-- Список с дополнительной информацией и ссылками -->
                    <ul>
                        <li>
                            <!-- Ссылка на официальный сайт, открывается в новой вкладке с безопасными атрибутами -->
                            <a href="https://www.autodesk.com/products/autocad/overview" target="_blank" rel="noopener"
                                class="link">
                                Официальный сайт AutoCAD
                            </a>
                        </li>
                        <li>Возможности: черчение, аннотирование, 3D-моделирование, совместная работа.</li>
                        <li>
                            <!-- Ссылка для скачивания шаблонов и примеров файлов -->
                            Шаблоны и примеры файлов:
                            <a href="files/autocad_templates.zip" download class="link">Скачать</a>
                        </li>
                    </ul>
                </article>
                <!-- Второй программный продукт: Revit -->
                <article style="margin-bottom: 40px;">
                    <h4><img src="icons/revit.png" alt="Revit"
                            style="width: 60px; vertical-align:middle; margin-right:8px;"> Revit</h4>
                    <p>Программа для BIM-моделирования, которая позволяет создавать информационные модели зданий и
                        сооружений.</p>
                    <img src="images/revit_screenshot.jpg" alt="Скриншот Revit"
                        style="max-width:100%; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:10px;">
                    <ul>
                        <li><a href="https://www.autodesk.com/products/revit/overview" target="_blank" rel="noopener"
                                class="link">Официальный сайт Revit</a></li>
                        <li>Особенности: архитектурное проектирование, инженерные системы, визуализация.</li>
                        <li>Полезные шаблоны: <a href="files/revit_templates.zip" download class="link">Скачать</a></li>
                    </ul>
                </article>
                <article style="margin-bottom: 40px;">
                    <h4><img src="icons/mathcad.png" alt="MathCad"
                            style="width: 60px; vertical-align:middle; margin-right:8px;"> MathCad</h4>
                    <p>Программное обеспечение для инженерных расчетов и документирования математических моделей.</p>
                    <img src="images/mathcad_screenshot.png" alt="Скриншот MathCad"
                        style="max-width:100%; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:10px;">
                    <ul>
                        <li><a href="https://www.ptc.com/en/products/mathcad" target="_blank" rel="noopener"
                                class="link">Официальный сайт MathCad</a></li>
                        <li>Позволяет создавать интерактивные документы с расчетами и графиками.</li>
                    </ul>
                </article>
                <article style="margin-bottom: 40px;">
                    <h4><img src="icons/ansys.png" alt="Ansys"
                            style="width: 60px; vertical-align:middle; margin-right:8px;"> Ansys</h4>
                    <p>Программа для инженерного анализа методом конечных элементов (FEA), широко используется для
                        прочностных и тепловых расчетов.</p>
                    <img src="images/ansys_screenshot.png" alt="Скриншот Ansys"
                        style="max-width:100%; border-radius:8px; box-shadow:0 4px 10px rgba(0,0,0,0.1); margin-bottom:10px;">
                    <ul>
                        <li><a href="https://www.ansys.com/" target="_blank" rel="noopener" class="link">Официальный
                                сайт Ansys</a></li>
                        <li>Поддерживает моделирование сложных конструкций и материалов.</li>
                    </ul>
                </article>
                <h4>Другие полезные инструменты</h4>
                <ul>
                    <li>Автоматизация сметных расчетов — программы для составления и контроля бюджета проектов.</li>
                    <li>Планирование проектов и управление строительством (MS Project, Primavera).</li>
                    <li>Технологии лазерного сканирования и фотограмметрии для точного измерения объектов.</li>
                    <li>Программное обеспечение для управления строительными площадками и документооборотом.</li>
                </ul>
            </section>
            <section id="tab2" class="tab-content" role="tabpanel" aria-labelledby="tab2-btn" tabindex="0">
                <!-- Заголовок раздела -->
                <h3>Новости и статьи по строительству и инновациям</h3>
                <!-- Вводный абзац с описанием раздела -->
                <p>
                    Актуальные новости, статьи и аналитика о современных технологиях, инновациях и событиях в
                    строительной отрасли.
                </p>
                <!-- Контейнер с карточками новостей -->
                <div class="news-cards">
                    <!-- Первая новостная карточка -->
                    <div class="news-card">
                        <!-- Изображение новости с альтернативным текстом -->
                        <img src="images/bim.jpg" alt="BIM-моделирование">
                        <!-- Контент новости -->
                        <div class="news-content">
                            <!-- Заголовок новости -->
                            <div class="news-title">Инновации в строительстве: ключевые тренды 2025 года</div>
                            <!-- Источник новости -->
                            <div class="news-source">Источник: 1solution.ru</div>
                            <!-- Краткое описание новости -->
                            <div class="news-text">
                                Строительная отрасль активно внедряет цифровые технологии: BIM-моделирование, 3D-печать
                                зданий, искусственный интеллект и машинное обучение для оптимизации процессов,
                                автоматизации и повышения качества строительства. Использование дронов и робототехники
                                позволяет ускорить и обезопасить работы на площадках.
                            </div>
                            <!-- Ссылка для перехода к полной новости, открывается в новой вкладке -->
                            <a href="https://1solution.ru/events/articles/innovatsii-v-stroitelstve-v-2025-godu/"
                                target="_blank" class="news-link">Подробнее</a>
                        </div>
                    </div>
                    <!-- Вторая новостная карточка -->
                    <div class="news-card">
                        <img src="images/tsuab.jpg" alt="Цифровизация и BIM в ТГАСУ">
                        <div class="news-content">
                            <div class="news-title">Цифровизация и BIM в ТГАСУ: открытие Центра цифровых технологий
                            </div>
                            <div class="news-source">Источник: tsuab.ru</div>
                            <div class="news-text">
                                В ТГАСУ открыт современный центр с лабораториями BIM, VR/AR и оборудованием для обучения
                                студентов и специалистов новым цифровым инструментам в строительстве. Центр оснащён
                                средствами для моделирования, анализа и визуализации проектов.
                            </div>
                            <a href="https://tsuab.ru/news/" target="_blank" class="news-link">Подробнее</a>
                        </div>
                    </div>
                    <div class="news-card">
                        <img src="images/3dprint.jpg" alt="3D-печать в строительстве">
                        <div class="news-content">
                            <div class="news-title">3D-печать в строительстве: перспективы и первые проекты</div>
                            <div class="news-source">Источник: 1solution.ru</div>
                            <div class="news-text">
                                Рост применения аддитивных технологий позволяет создавать сложные архитектурные
                                конструкции и целые здания с сокращением сроков и затрат. В России реализуются пилотные
                                проекты с использованием строительных 3D-принтеров.
                            </div>
                            <a href="https://1solution.ru/events/articles/innovatsii-v-stroitelstve-v-2025-godu/"
                                target="_blank" class="news-link">Подробнее</a>
                        </div>
                    </div>
                    <div class="news-card">
                        <img src="images/bim-olymp.jpg" alt="BIM-олимпиада ТГАСУ">
                        <div class="news-content">
                            <div class="news-title">Победы студентов ТГАСУ на Всероссийской олимпиаде по BIM</div>
                            <div class="news-source">Источник: tsuab.ru</div>
                            <div class="news-text">
                                Команда университета представила инновационный проект реконструкции с применением Revit
                                и Navisworks, продемонстрировав высокий уровень цифрового моделирования и интеграции
                                инженерных систем.
                            </div>
                            <a href="https://tsuab.ru/news/" target="_blank" class="news-link">Подробнее</a>
                        </div>
                    </div>
                    <div class="news-card">
                        <img src="images/eco.jpg" alt="Энергоэффективность">
                        <div class="news-content">
                            <div class="news-title">Новые стандарты энергоэффективности в строительстве</div>
                            <div class="news-source">Источник: 1solution.ru</div>
                            <div class="news-text">
                                Введены обновлённые нормативы, стимулирующие применение современных теплоизоляционных
                                материалов и автоматизированных систем управления микроклиматом, что снижает
                                энергопотребление зданий до 30%.
                            </div>
                            <a href="https://1solution.ru/events/articles/innovatsii-v-stroitelstve-v-2025-godu/"
                                target="_blank" class="news-link">Подробнее</a>
                        </div>
                    </div>
                    <div class="news-card">
                        <img src="images/webinar.jpg" alt="Вебинар BIM">
                        <div class="news-content">
                            <div class="news-title">Вебинар «BIM как инструмент управления строительством»</div>
                            <div class="news-source">Источник: tsuab.ru</div>
                            <div class="news-text">
                                Эксперты отрасли рассказали о практическом применении BIM на всех этапах жизненного
                                цикла объектов — от проектирования до эксплуатации. BIM позволяет повысить качество
                                проектов, сократить сроки и снизить риски.
                            </div>
                            <a href="https://tsuab.ru/news/" target="_blank" class="news-link">Подробнее</a>
                        </div>
                    </div>
                    <!-- Добавьте другие новости по аналогии, меняя картинки, заголовки и текст -->
                </div>
                <p style="margin-top:30px;">Полезные ресурсы и порталы:</p>
                <ul>
                    <li><a href="https://tsuab.ru/" target="_blank" rel="noopener" class="link">Официальный сайт
                            ТГАСУ</a></li>
                    <li><a href="https://tsuab.ru/news/" target="_blank" rel="noopener" class="link">Новости ТГАСУ</a>
                    </li>
                    <li><a href="https://stroygaz.ru/" target="_blank" rel="noopener" class="link">Строительные новости
                            России</a></li>
                    <li><a href="https://1solution.ru/events/articles/innovatsii-v-stroitelstve-v-2025-godu/"
                            target="_blank" rel="noopener" class="link">Инновации в строительстве — 1solution.ru</a>
                    </li>
                    <li><a href="https://darstroy-yug.ru/articles/sovremennye-tekhnologii-v-stroitelstve-2025"
                            target="_blank" rel="noopener" class="link">Современные технологии строительства —
                            darstroy-yug.ru</a></li>
                    <li><a href="https://vestnikstroy.ru/articles/building/budushchee-promyshlennogo-stroitelstva-trendy-2025-goda/"
                            target="_blank" rel="noopener" class="link">Будущее промышленного строительства —
                            vestnikstroy.ru</a></li>
                </ul>
            </section>
        </main>
    </div>
    <script>
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Снимаем активность со всех кнопок и контента
                tabs.forEach(t => {
                    t.classList.remove('active');
                    t.setAttribute('aria-selected', 'false');
                });
                contents.forEach(c => c.classList.remove('active'));
                // Активируем выбранный таб и контент
                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');
                const id = tab.getAttribute('aria-controls');
                document.getElementById(id).classList.add('active');
                document.getElementById(id).focus();
            });
        });
    </script>
    <script>// Загрузка картинки заместо отсуствующей (Заглушка)
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('img').forEach(img => {
                img.onerror = () => {
                    console.log('Ошибка загрузки:', img.src);
                    img.onerror = null;
                    img.src = 'images/placeholder.jpg';
                };
            });
        });
    </script>
    <script src="sidebar.js"></script>
</body>
</html>