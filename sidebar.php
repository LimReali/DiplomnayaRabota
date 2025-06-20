<nav class="sidebar" id="sidebar">
    <!-- Верхняя часть сайдбара с логотипом/иконкой -->
    <div class="sidebar-header">
        <img src="https://images.icon-icons.com/3421/PNG/512/calendar_date_month_schedule_ios_icon_218539.png"
            alt="Scheduler">
        <!-- Логотип или иконка приложения (календарь/расписание) -->
    </div>
    <!-- Кнопка для сворачивания/разворачивания сайдбара (только на десктопе) -->
    <button class="toggle-btn" id="toggleBtn" aria-label="Toggle sidebar">
        <i class="fas fa-angle-double-left"></i>
        <!-- Иконка Font Awesome для визуализации действия -->
    </button>
    <!-- Навигационное меню -->
    <div class="nav-menu">
        <ul>
            <!-- Пункт меню: расписание по группам -->
            <li>
                <a href="GROUP_SELECT.php">
                    <i class="fas fa-users"></i>
                    <span>Расписание по группам</span>
                </a>
            </li>
            <!-- Пункт меню: расписание по преподавателям -->
            <li>
                <a href="teachers_select.php">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Расписание по преподавателям</span>
                </a>
            </li>
            <!-- Пункт меню: расписание по кабинетам -->
            <li>
                <a href="ROOM_select.php">
                    <i class="fas fa-door-open"></i>
                    <span>Расписание по кабинетам</span>
                </a>
            </li>
            <!-- Пункт меню: панель администратора -->
            <li>
                <a href="MainAdmin.php">
                    <i class="fas fa-user-shield"></i>
                    <span>Панель администраторов</span>
                </a>
            </li>
            <!-- Пункт меню: справочный материал -->
            <li>
                <a href="news.php">
                    <i class="fas fa-book"></i>
                    <span>Справочный материал</span>
                </a>
            </li>
            <!-- Пункт меню: корпуса университета -->
            <li>
                <a href="buildings.php">
                    <i class="fas fa-university"></i>
                    <span>Корпуса университета</span>
                </a>
            </li>
        </ul>
    </div>
    <!-- Футер сайдбара (можно добавить контактную информацию, копирайт и т.д.) -->
    <div class="sidebar-footer"></div>
</nav>