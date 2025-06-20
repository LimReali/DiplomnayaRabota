<?php
// AdminSidebar.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>

<nav class="sidebar" role="navigation" aria-label="Главное меню администратора" id="sidebar">
  <button class="toggle-btn" id="toggleBtn" aria-label="Сворачивать меню">☰</button>
  <h2>Панель администратора</h2>
  <a href="add_lesson.php" title="Добавить занятие">Добавить занятие</a>
  <a href="groups_admin.php" title="Управление группами">Управление группами</a>
  <a href="teachers_admin.php" title="Управление преподавателями">Управление преподавателями</a>
  <a href="db_history.php" title="История изменений базы">История изменений базы</a>
  <a href="admin_rooms_select.php" title="Управление кабинетами">Управление кабинетами</a>
  <form action="logout.php" method="post" aria-label="Выйти из системы">
    <button type="submit" title="Выйти из системы">Выйти</button>
  </form>
</nav>

<script src="AdminSidebar.js"></script>
