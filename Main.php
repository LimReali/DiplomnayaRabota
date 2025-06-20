<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Панель управления расписанием</title>
    <!-- Подключение иконок Font Awesome для красивых иконок в меню и карточках -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* --- CSS-ПЕРЕМЕННЫЕ ДЛЯ ЦВЕТОВОЙ СХЕМЫ --- */
        :root {
            --main-bg: #f8f5f7;
            --sidebar-bg: #7b203a;
            --sidebar-bg-dark: #5c162b;
            --sidebar-accent: #a83250;
            --sidebar-hover: #c0395b;
            --sidebar-footer: #a83250;
            --card-bg: #fff;
            --card-hover-bg: linear-gradient(135deg, #a83250 0%, #7b203a 100%);
            --card-hover-color: #fff;
            --text-main: #2c3e50;
            --text-light: #fff;
            --text-muted: #e8c6d0;
            --shadow: 0 6px 18px rgba(123, 32, 58, 0.10);
            --shadow-hover: 0 12px 28px rgba(123, 32, 58, 0.15);
        }
        * {
            box-sizing: border-box;
        }
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--main-bg);
            color: var(--text-main);
        }
        a {
            text-decoration: none;
            color: inherit;
        }
        /* --- ОСНОВНОЙ КОНТЕЙНЕР С ЛЕЙАУТОМ --- */
        .container {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        /* --- САЙДБАР --- */
        .sidebar {
            background-color: var(--sidebar-bg);
            color: var(--text-light);
            width: 280px;
            transition: width 0.3s ease;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .sidebar.collapsed {
            width: 70px;
        }
        .sidebar-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 0 16px 0;
            border-bottom: 1px solid var(--sidebar-bg-dark);
            background: var(--sidebar-bg);
        }
        .sidebar-header img {
            width: 54px;
            height: 54px;
            margin-bottom: 6px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(44, 0, 20, 0.09);
            background: #fff;
        }
        .sidebar.collapsed .sidebar-header {
            padding: 20px 0 10px 0;
        }
        .sidebar.collapsed .sidebar-header img {
            width: 38px;
            height: 38px;
            margin-bottom: 0;
        }
        /* Кнопка сворачивания сайдбара (только на десктопе) */
        .toggle-btn {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.6rem;
            cursor: pointer;
            padding: 10px 20px;
            text-align: left;
            outline: none;
            border-bottom: 1px solid var(--sidebar-bg-dark);
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-btn:hover {
            background-color: var(--sidebar-bg-dark);
        }
        .toggle-btn i {
            transition: transform 0.3s ease;
        }
        .sidebar.collapsed .toggle-btn i {
            transform: rotate(180deg);
        }
        .sidebar.collapsed .toggle-btn {
            padding: 10px;
        }
        /* Меню навигации в сайдбаре */
        .nav-menu {
            flex-grow: 1;
            overflow-y: visible;
        }
        .nav-menu ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-menu li {
            display: flex;
        }
        .nav-menu li a {
            flex-grow: 1;
            display: flex;
            align-items: center;
            padding: 15px 28px;
            color: var(--text-light);
            font-size: 1.08rem;
            transition: background-color 0.2s ease;
            white-space: nowrap;
            font-weight: 500;
        }
        .nav-menu li a:hover,
        .nav-menu li a.active {
            background-color: var(--sidebar-hover);
            color: var(--text-light);
        }
        .nav-menu li a i {
            width: 32px;
            min-width: 32px;
            font-size: 1.3rem;
            margin-right: 20px;
            text-align: center;
        }
        .sidebar.collapsed .nav-menu li a span {
            display: none;
        }
        .sidebar.collapsed .nav-menu li a {
            justify-content: center;
            padding: 15px 18px;
        }
        /* Футер сайдбара */
        .sidebar-footer {
            padding: 17px 20px;
            font-size: 0.98rem;
            color: var(--text-muted);
            border-top: 1px solid var(--sidebar-bg-dark);
            text-align: center;
            user-select: none;
            background: var(--sidebar-footer);
            letter-spacing: 1px;
        }
        .nav-menu li a span {
            white-space: normal;
            word-break: break-word;
            line-height: 1.3em;
        }
        /* --- ОБЛАСТЬ КОНТЕНТА --- */
        .content {
            flex-grow: 1;
            padding: 32px 48px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .content h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: var(--sidebar-bg);
        }
        .welcome-msg {
            margin-bottom: 25px;
            font-size: 1.13rem;
            color: #7b203a;
            background: #fff0f5;
            border-left: 4px solid var(--sidebar-bg);
            padding: 12px 18px;
            border-radius: 7px;
            box-shadow: 0 2px 8px rgba(123, 32, 58, 0.05);
        }
        /* --- СЕТКА КАРТОЧЕК --- */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 26px;
            flex-grow: 1;
        }
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 100px 22px;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.3s ease, color 0.3s;
            color: var(--sidebar-bg);
            text-align: center;
            user-select: none;
            border: 2px solid transparent;
            overflow-wrap: break-word;
            word-break: break-word;
            hyphens: auto;
        }
        .card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: var(--shadow-hover);
            background: var(--card-hover-bg);
            color: var(--card-hover-color);
            border: 2px solid #a83250;
        }
        .card i {
            font-size: 3.8rem;
            margin-bottom: 18px;
            transition: color 0.3s ease;
            color: #a83250;
        }
        .card:hover i {
            color: #fff;
        }
        .card span {
            font-weight: 700;
            font-size: 1.17rem;
            transition: color 0.3s ease;
        }
        .card:hover span {
            color: #fff;
        }
        /* --- АДАПТИВНОСТЬ ДЛЯ МОБИЛЬНЫХ --- */
        @media (max-width: 768px) {
            body {
                overflow-x: hidden;
            }
            .container {
                flex-direction: column;
                height: auto;
                min-height: 100vh;
            }
            /* Сайдбар фиксируется вверху, занимает всю ширину, скрывается по умолчанию */
            .sidebar {
                position: fixed;
                top: 56px;
                left: 0;
                right: 0;
                width: 100vw;
                height: auto;
                max-height: calc(100vh - 56px);
                background: var(--sidebar-bg);
                transform: translateY(-100%);
                transition: transform 0.3s ease;
                z-index: 10000;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                overflow-y: auto;
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
            }
            .sidebar.open {
                transform: translateY(0);
            }
            .sidebar.collapsed {
                width: 100%;
            }
            .sidebar-header {
                padding: 16px 0;
                text-align: center;
            }
            .sidebar-footer {
                position: static;
                border-top: 1px solid var(--sidebar-bg-dark);
                padding: 12px 20px;
            }
            .toggle-btn {
                display: none;
            }
            .nav-menu {
                height: auto;
                max-height: none;
            }
            .content {
                padding: 20px 15px;
                margin-top: 56px;
                min-height: calc(100vh - 56px);
            }
            .cards-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 26px;
                flex-grow: 1;
            }
            /* Верхняя мобильная панель */
            .mobile-topbar {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 56px;
                background: var(--sidebar-bg);
                display: flex;
                align-items: center;
                padding: 0 15px;
                z-index: 11000;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            }
            .mobile-topbar button {
                background: none;
                border: none;
                color: var(--text-light);
                font-size: 1.8rem;
                cursor: pointer;
                margin-right: 15px;
                outline: none;
            }
            .mobile-topbar .title {
                color: var(--text-light);
                font-weight: 600;
                font-size: 1.2rem;
                user-select: none;
            }
        }
        @media (max-width: 900px) {
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Мобильная верхняя панель для маленьких экранов -->
    <div class="mobile-topbar" id="mobileTopbar" style="display:none;">
        <button id="mobileMenuBtn" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="title">Панель управления</div>
    </div>
    <div class="container">
        <!-- Вставка боковой панели (sidebar) через PHP -->
        <?php include 'sidebar.php'; ?>
        <main class="content" id="content">
            <!-- Приветствие и быстрые карточки перехода -->
            <h2>Добро пожаловать в панель управления расписанием</h2>
            <div class="welcome-msg">Выберите вкладку слева или воспользуйтесь быстрым переходом ниже.</div>
            <div class="cards-grid">
                <!-- Карточки для быстрого перехода к разделам -->
                <a href="GROUP_SELECT.php" class="card" title="Расписание по группам">
                    <i class="fas fa-users"></i>
                    <span>Расписание по группам</span>
                </a>
                <a href="teachers_select.php" class="card" title="Расписание по преподавателям">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Расписание по преподавателям</span>
                </a>
                <a href="ROOM_select.php" class="card" title="Расписание по кабинетам">
                    <i class="fas fa-door-open"></i>
                    <span>Расписание по кабинетам</span>
                </a>
                <a href="news.php" class="card" title="Расписание семинаров">
                    <i class="fas fa-book"></i>
                    <span>Справочный материал</span>
                </a>
                <a href="MainAdmin.php" class="card" title="Панель администраторов">
                    <i class="fas fa-user-shield"></i>
                    <span>Панель администраторов</span>
                </a>
                <a href="buildings.php" class="card" title="Корпуса университета">
                    <i class="fas fa-university"></i>
                    <span>Корпуса университета</span>
                </a>
            </div>
        </main>
    </div>
    <script>
        // Получаем элементы для управления сайдбаром и мобильной панелью
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileTopbar = document.getElementById('mobileTopbar');
        // Переключение сайдбара на десктопе
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
        // Отображение мобильной панели и сброс сайдбара на мобильных
        function handleResize() {
            if (window.innerWidth <= 768) {
                mobileTopbar.style.display = 'flex';
                sidebar.classList.remove('collapsed');
                sidebar.classList.remove('open');
            } else {
                mobileTopbar.style.display = 'none';
                sidebar.classList.remove('open');
            }
        }
        window.addEventListener('resize', handleResize);
        window.addEventListener('load', handleResize);
        // Открытие/закрытие сайдбара на мобильных
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
        // Закрытие сайдбара при клике вне его области на мобильных
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
</body>
</html>