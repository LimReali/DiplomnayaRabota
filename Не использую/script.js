// Временные слоты с объединением времени
const timeSlots = [
  ["08:20", "09:55"],
  ["10:05", "11:40"],
  ["12:05", "13:40"],
  ["13:55", "15:30"],
  ["15:40", "17:15"],
  ["17:25", "19:00"],
  ["19:10", "20:45"],
];

// Функция для загрузки данных из JSON
async function loadData() {
    try {
      const response = await fetch("data.json");
      if (!response.ok) {
        throw new Error(`Ошибка загрузки файла data.json: ${response.status}`);
      }
      const rawData = await response.json();
      return filterInvalidData(rawData); // Фильтруем некорректные данные
    } catch (error) {
      console.error("Ошибка при загрузке данных:", error);
      return []; // Возвращаем пустой массив в случае ошибки
    }
  }
  
// Функция для проверки корректности записи
function isValidEntry(entry) {
    // Проверяем наличие всех обязательных полей
    return entry.teacher && entry.group && entry.room && entry.day && entry.time && entry.subject;
  }
  
  function filterInvalidData(data) {
    const validEntries = [];
    const invalidEntries = [];
  
    data.forEach((entry) => {
      if (isValidEntry(entry)) {
        validEntries.push(entry);
      } else {
        invalidEntries.push(entry);
      }
    });
  
    // Проверяем наличие некорректных записей
   //  if (invalidEntries.length > 0) {
   //    showNotification(`Обнаружено ${invalidEntries.length} некорректных записей. Они были пропущены.`);
   //    console.warn("Некорректные записи:", invalidEntries);
   //  } else {
   //    showNotification("Все данные введены корректно!");
   //  }
  
    return validEntries;
  }
  
  
// Функция для нормализации формата времени
function normalizeTimeFormat(time) {
  return time.replace(/\./g, ":");
}

// Функция для скачивания файла
function download(filename, text) {
  const element = document.createElement("a");
  element.setAttribute(
      "href",
      "data:text/plain;charset=utf-8," + encodeURIComponent(text)
  );
  element.setAttribute("download", filename);

  element.style.display = "none";
  document.body.appendChild(element);

  element.click();

  document.body.removeChild(element);
}

// Функция для сохранения данных с помощью скачивания
function saveDataAndDownload(data) {
  const json = JSON.stringify(data, null, 4);
  download("data.json", json);
  showNotification(
      "Изменения сохранены. Не забудьте заменить data.json на скачанный файл!"
  );
}

// Функция для отображения уведомлений
function showNotification(message) {
  alert(message); // Можно заменить на более красивое решение
}

// Функция для заполнения основной таблицы расписания
function populateTable(data, deletedEntries = []) {
  const scheduleTableBody = document
      .getElementById("scheduleTable")
      .getElementsByTagName("tbody")[0];
  scheduleTableBody.innerHTML = "";

  timeSlots.forEach((slot) => {
      const row = scheduleTableBody.insertRow();
      const time = `${slot[0]} - ${slot[1]}`;

      row.insertCell(0).innerText = time;

      [
          "Понедельник",
          "Вторник",
          "Среда",
          "Четверг",
          "Пятница",
          "Суббота",
          "Воскресенье",
      ].forEach((day) => {
          const cell = row.insertCell();
          // Filter out deleted entries
          const entry = data.find(
              (item) =>
                  normalizeTimeFormat(item.time) === time &&
                  item.day === day &&
                  !deletedEntries.some(
                      (deleted) =>
                          normalizeTimeFormat(deleted.time) === time && deleted.day === day
                  )
          );

          if (entry) {
              cell.innerText = `${entry.teacher}\n${entry.group}\n${entry.room}\n${entry.subject}`;
              cell.style.whiteSpace = "pre-wrap";
          }
      });
  });
}

// Функция для заполнения таблицы редактирования
function populateEditTable(data, deletedEntries = []) {
  const editTableBody = document
      .getElementById("editTable")
      .getElementsByTagName("tbody")[0];
  editTableBody.innerHTML = "";

  timeSlots.forEach((slot) => {
      const row = editTableBody.insertRow();
      const time = `${slot[0]} - ${slot[1]}`;

      row.insertCell(0).innerText = time;

      [
          "Понедельник",
          "Вторник",
          "Среда",
          "Четверг",
          "Пятница",
          "Суббота",
          "Воскресенье",
      ].forEach((day) => {
          const cell = row.insertCell();

          // Filter out deleted entries
          const entry = data.find(
              (item) =>
                  normalizeTimeFormat(item.time) === time &&
                  item.day === day &&
                  !deletedEntries.some(
                      (deleted) =>
                          normalizeTimeFormat(deleted.time) === time && deleted.day === day
                  )
          );

          if (entry) {
              cell.contentEditable = true;
              const contentDiv = document.createElement("div");
              contentDiv.contentEditable = true;
              contentDiv.innerText = `${entry.teacher}\n${entry.group}\n${entry.room}\n${entry.subject}`;
              contentDiv.style.whiteSpace = "pre-wrap";
              cell.appendChild(contentDiv);

              // Добавляем кнопку удаления
              addDeleteButton(cell, time, day, data, deletedEntries);
          } else {
              cell.contentEditable = true;
              cell.innerText = "";
          }
      });
  });
}

// Функция для заполнения таблицы преподавателей
function populateTeacherTable(data, deletedEntries = []) {
  const teacherTableBody = document
      .getElementById("teacherTable")
      .getElementsByTagName("tbody")[0];
  teacherTableBody.innerHTML = "";

  data.forEach((item) => {
      // Filter out deleted entries
      if (
          deletedEntries.some(
              (deleted) =>
                  normalizeTimeFormat(deleted.time) === item.time &&
                  deleted.day === item.day
          )
      ) {
          return; // Skip deleted entries
      }

      const row = teacherTableBody.insertRow();
      row.insertCell(0).innerText = item.teacher;
      row.insertCell(1).innerText = item.time;
      row.insertCell(2).innerText = item.day;
      row.insertCell(3).innerText = item.group;
      row.insertCell(4).innerText = item.room;
      row.insertCell(5).innerText = item.subject;

      // Добавляем кнопку удаления
      const deleteButton = document.createElement("button");
      deleteButton.textContent = "🗑 Удалить";
      deleteButton.classList.add("delete-button"); // Добавляем класс!
      deleteButton.onclick = function () {
          deletedEntries.push(item);
          populateTeacherTable(data, deletedEntries);
          populateTable(data, deletedEntries);
          populateEditTable(data, deletedEntries);
          populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);
      };
      row.insertCell(6).appendChild(deleteButton);
  });
}

// Функция для заполнения таблицы расписания по кабинету
function populateRoomTable(data, selectedRoom, deletedEntries = []) {
  const roomTableBody = document
      .getElementById("roomTable")
      .getElementsByTagName("tbody")[0];
  roomTableBody.innerHTML = "";

  timeSlots.forEach((slot) => {
      const row = roomTableBody.insertRow();
      const time = `${slot[0]} - ${slot[1]}`;

      row.insertCell(0).innerText = time;

      [
          "Понедельник",
          "Вторник",
          "Среда",
          "Четверг",
          "Пятница",
          "Суббота",
          "Воскресенье",
      ].forEach((day) => {
          const cell = row.insertCell();

          // Filter out deleted entries
          const entry = data.find(
              (item) =>
                  normalizeTimeFormat(item.time) === time &&
                  item.day === day &&
                  item.room === selectedRoom &&
                  !deletedEntries.some(
                      (deleted) =>
                          normalizeTimeFormat(deleted.time) === time && deleted.day === day
                  )
          );

          if (entry) {
              cell.innerText = `${entry.teacher}\n${entry.group}\n${entry.subject}`;
              cell.style.whiteSpace = "pre-wrap";
          }
      });
  });
}

// Функция для заполнения редактируемой таблицы расписания по кабинету
function populateEditRoomEditTable(data, selectedRoom, deletedEntries = []) {
    const tbody = document.querySelector("#editRoomEditTable tbody");
    tbody.innerHTML = "";
  
    const days = ["Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"];
  
    timeSlots.forEach(slot => {
      const row = tbody.insertRow();
      const time = `${slot[0]} - ${slot[1]}`;
      row.insertCell().innerText = time; // Время - не редактируемое
  
      days.forEach(day => {
        const cell = row.insertCell();
  
        // Ищем занятие для этого времени, дня и кабинета
        const entry = data.find(item =>
          normalizeTimeFormat(item.time) === time &&
          item.day === day &&
          item.room === selectedRoom &&
          !deletedEntries.some(deleted =>
            normalizeTimeFormat(deleted.time) === time && deleted.day === day
          )
        );
  
        // Создаём редактируемый div всегда
        const editableDiv = document.createElement("div");
        editableDiv.contentEditable = "true";
        editableDiv.style.whiteSpace = "pre-wrap"; // перенос строк внутри div
  
        // Заполняем данными, если есть запись
        if (entry) {
          editableDiv.innerText = `${entry.teacher}\n${entry.group}\n${entry.subject}`;
        } else {
          editableDiv.innerText = ""; // пустая ячейка - пустой div
        }
  
        cell.appendChild(editableDiv);
  
        // Добавляем кнопку удаления, если есть запись
        if (entry) {
          addDeleteButton(cell, time, day, data, deletedEntries);
        }
      });
    });
  }
  
  

// Функция для заполнения редактируемой таблицы расписания по преподавателю
function populateEditTeacherTable(data, selectedTeacher, deletedEntries = []) {
  const teacherTableBody = document.getElementById("editTeacherTable").getElementsByTagName("tbody")[0];
  teacherTableBody.innerHTML = "";

  // Filter data by selected teacher
  const teacherData = data.filter(item => item.teacher === selectedTeacher);

  teacherData.forEach(item => {
      if (deletedEntries.some(deleted => normalizeTimeFormat(deleted.time) === item.time && deleted.day === item.day)) {
          return; // Skip deleted entries
      }

      const row = teacherTableBody.insertRow();
      row.insertCell(0).innerText = item.teacher;
      row.insertCell(1).innerText = item.time;
      row.insertCell(2).innerText = item.day;
      row.insertCell(3).innerText = item.group;
      row.insertCell(4).innerText = item.room;
      row.insertCell(5).innerText = item.subject;

      // Add delete button
      const deleteButton = document.createElement("button");
      deleteButton.textContent = "🗑 Удалить";
      deleteButton.classList.add("delete-button"); // Добавляем класс!
      deleteButton.onclick = function () {
          deletedEntries.push(item);
          populateEditTeacherTable(data, selectedTeacher, deletedEntries);
          populateTable(data, deletedEntries);
          populateEditTable(data, deletedEntries);
          populateRoomTable(data, document.getElementById("roomSelect").value, deletedEntries);
          populateEditRoomEditTable(data, document.getElementById("editRoomSelect").value, deletedEntries);
          populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);
      };
      row.insertCell(6).appendChild(deleteButton);
  });
}

// Функция для добавления кнопки удаления в ячейку
function addDeleteButton(cell, time, day, data, deletedEntries) {
  const deleteButton = document.createElement("button");
  deleteButton.textContent = "🗑";
  deleteButton.classList.add("delete-button"); // Добавляем класс!
  deleteButton.style.float = "right";

  deleteButton.onclick = function (event) {
      event.stopPropagation(); // Prevent cell editing

      // Find the entry to delete
      const entryToDelete = data.find(
          (item) => normalizeTimeFormat(item.time) === time && item.day === day
      );

      if (entryToDelete) {
          deletedEntries.push(entryToDelete);
      }

      // Очищаем ячейку
      cell.innerHTML = "";

      // Обновляем таблицы
      populateTable(data, deletedEntries);
      populateEditTable(data, deletedEntries);
      populateTeacherTable(data, deletedEntries);
      populateRoomTable(
          data,
          document.getElementById("roomSelect").value,
          deletedEntries
      );
      populateEditRoomEditTable(
          data,
          document.getElementById("editRoomSelect").value,
          deletedEntries
      );
      // Update the edit teacher table
      populateEditTeacherTable(
          data,
          document.getElementById("editTeacherSelect").value,
          deletedEntries
      );
      populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);
  };

  // Создаём div для содержимого ячейки и кнопки
  const contentDiv = document.createElement("div");
  contentDiv.style.float = "left";

  if (cell.children.length > 0) {
      // Если ячейка уже содержит текст, добавляем его в div
      contentDiv.innerText = cell.innerText;
      cell.innerText = ""; // Очищаем ячейку
  }

  cell.appendChild(contentDiv);
  cell.appendChild(deleteButton);
}

// Функция для заполнения таблицы по группам
function populateGroupTable(data, selectedGroup, deletedEntries = []) {
  const groupTableBody = document.getElementById("groupTable").getElementsByTagName("tbody")[0];
  groupTableBody.innerHTML = "";

  const filteredData = selectedGroup
      ? data.filter(item => item.group === selectedGroup)
      : data;

  // Группируем данные по группам (в данном случае фильтруем по одной группе)
  const groupedData = filteredData.reduce((acc, item) => {
      if (
          deletedEntries.some(
              (deleted) =>
                  normalizeTimeFormat(deleted.time) === item.time &&
                  deleted.day === item.day &&
                  deleted.group === item.group
          )
      ) {
          return acc;
      }
      if (!acc[item.group]) {
          acc[item.group] = [];
      }
      acc[item.group].push(item);
      return acc;
  }, {});

  // Заполняем таблицу данными
  Object.keys(groupedData).forEach((group) => {
      groupedData[group].forEach((item) => {
          const row = groupTableBody.insertRow();
          row.insertCell(0).innerText = group;
          row.insertCell(1).innerText = item.time;
          row.insertCell(2).innerText = item.day;
          row.insertCell(3).innerText = item.teacher;
          row.insertCell(4).innerText = item.room;
          row.insertCell(5).innerText = item.subject;

          // Добавляем кнопку удаления
          const deleteButton = document.createElement("button");
          deleteButton.textContent = "🗑 Удалить";
          deleteButton.classList.add("delete-button"); // Добавляем класс!
          deleteButton.onclick = function () {
              deletedEntries.push(item);
              populateGroupTable(data, selectedGroup, deletedEntries);
              populateTable(data, deletedEntries);
              populateEditTable(data, deletedEntries);
              populateTeacherTable(data, deletedEntries);
              populateRoomTable(
                  data,
                  document.getElementById("roomSelect").value,
                  deletedEntries
              );
              populateEditRoomEditTable(
                  data,
                  document.getElementById("editRoomSelect").value,
                  deletedEntries
              );
              populateEditTeacherTable(
                  data,
                  document.getElementById("editTeacherSelect").value,
                  deletedEntries
              );
          };
          row.insertCell(6).appendChild(deleteButton);
      });
  });
}

// Функция для заполнения списка групп
function populateGroupSelect(data) {
  const groupSelect = document.getElementById("groupSelect");
  const uniqueGroups = [...new Set(data.map(item => item.group))];

  // Очищаем список перед заполнением
  groupSelect.innerHTML = '<option value="">Все группы</option>';

  uniqueGroups.forEach(group => {
      const option = document.createElement("option");
      option.value = group;
      option.text = group;
      groupSelect.add(option);
  });
}

document.addEventListener("DOMContentLoaded", async function () {
  let data = [];
  let deletedEntries = []; // Массив для хранения удаленных элементов

  try {
      data = await loadData();

      // Заполняем список выбора групп
      populateGroupSelect(data);

      // Заполняем таблицы после загрузки данных
      populateTable(data, deletedEntries);
      populateEditTable(data, deletedEntries);
      populateTeacherTable(data, deletedEntries);
      populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);

      // Получаем уникальные кабинеты из данных
      const uniqueRooms = [...new Set(data.map((item) => item.room))];

      // Get unique teachers from the data
      const uniqueTeachers = [...new Set(data.map((item) => item.teacher))];

      // Заполняем список выбора кабинетов для расписания
      const roomSelect = document.getElementById("roomSelect");
      uniqueRooms.forEach((room) => {
          const option = document.createElement("option");
          option.value = room;
          option.text = room;
          roomSelect.add(option);
      });

      // Populate the teacher select
      const editTeacherSelect = document.getElementById("editTeacherSelect");
      uniqueTeachers.forEach((teacher) => {
          const option = document.createElement("option");
          option.value = teacher;
          option.text = teacher;
          editTeacherSelect.add(option);
      });

      // Обработчик изменения выбора кабинета для расписания
      roomSelect.addEventListener("change", function () {
          const selectedRoom = this.value;
          populateRoomTable(data, selectedRoom, deletedEntries);
      });

      // Обработчик изменения выбора группы
      document.getElementById("groupSelect").addEventListener("change", function () {
          const selectedGroup = this.value;
          populateGroupTable(data, selectedGroup, deletedEntries);
      });

      // Teacher select change event
      editTeacherSelect.addEventListener("change", function () {
          const selectedTeacher = this.value;
          populateEditTeacherTable(data, selectedTeacher, deletedEntries);
      });

      // Initial population of edit teacher table
      populateEditTeacherTable(data, editTeacherSelect.value, deletedEntries);

      // Заполняем таблицу расписания по кабинетам при начальной загрузке
      populateRoomTable(data, roomSelect.value, deletedEntries);

      // Заполняем список выбора кабинетов для редактирования
      const editRoomSelect = document.getElementById("editRoomSelect");
      uniqueRooms.forEach((room) => {
          const option = document.createElement("option");
          option.value = room;
          option.text = room;
          editRoomSelect.add(option);
      });

      // Обработчик изменения выбора кабинета для редактирования
      editRoomSelect.addEventListener("change", function () {
          const selectedRoom = this.value;
          populateEditRoomEditTable(data, selectedRoom, deletedEntries);
      });

      // Заполняем таблицу редактирования расписания по кабинетам при начальной загрузке
      populateEditRoomEditTable(data, editRoomSelect.value, deletedEntries);
  } catch (error) {
      // console.error("Ошибка при инициализации:", error);
      // showNotification(
      //     "Произошла ошибка при загрузке данных. Пожалуйста, проверьте консоль."
     //  );
      return; // Прекращаем выполнение скрипта, если произошла ошибка загрузки данных
  }

  // Обработчик для кнопки добавления нового занятия
  document
      .getElementById("addScheduleButton")
      .addEventListener("click", function () {
          const time = document.getElementById("time").value;
          const teacher = document.getElementById("teacher").value;
          const room = document.getElementById("room").value;
          const day = document.getElementById("day").value;
          const subject = document.getElementById("subject").value;
          const group = document.getElementById("group").value;

          if (time && teacher && room && day && subject && group) {
              const newEntry = { time, teacher, room, day, subject, group };
              data.push(newEntry);

              populateTable(data, deletedEntries);
              populateEditTable(data, deletedEntries);
              populateTeacherTable(data, deletedEntries);
              populateRoomTable(
                  data,
                  document.getElementById("roomSelect").value,
                  deletedEntries
              );
              populateEditRoomEditTable(
                  data,
                  document.getElementById("editRoomSelect").value,
                  deletedEntries
              );
              populateEditTeacherTable(
                  data,
                  document.getElementById("editTeacherSelect").value,
                  deletedEntries
              );
              populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);

              // Clear input fields
              document.getElementById("time").value = "";
              document.getElementById("teacher").value = "";
              document.getElementById("room").value = "";
              document.getElementById("day").value = "";
              document.getElementById("subject").value = "";
              document.getElementById("group").value = "";
          } else {
              showNotification("Пожалуйста, заполните все поля.");
          }
      });

  // Обработчик для кнопки сохранения изменений
  document
      .getElementById("saveChangesButton")
      .addEventListener("click", function () {
          // Сохраняем все изменения в таблице редактирования
          const editTableBody = document
              .getElementById("editTable")
              .getElementsByTagName("tbody")[0];
          for (let i = 0; i < editTableBody.rows.length; i++) {
              const row = editTableBody.rows[i];
              const time = row.cells[0].innerText;
              for (let j = 1; j < row.cells.length; j++) {
                  const day = [
                      "Понедельник",
                      "Вторник",
                      "Среда",
                      "Четверг",
                      "Пятница",
                      "Суббота",
                      "Воскресенье",
                  ][j - 1];
                  const cell = row.cells[j];
                  if (cell.hasChildNodes()) {
                      // Если ячейка содержит div с текстом
                      const contentDiv = cell.firstChild;
                      if (contentDiv && contentDiv.innerText !== undefined) {
                          const cellText = contentDiv.innerText;

                          // Check if there is an existing entry for this time and day
                          const existingEntryIndex = data.findIndex(
                              (item) =>
                                  normalizeTimeFormat(item.time) === time && item.day === day
                          );

                          if (existingEntryIndex !== -1) {
                              // Update existing entry
                              data[existingEntryIndex].teacher = cellText.split("\n")[0] || "";
                              data[existingEntryIndex].group = cellText.split("\n")[1] || "";
                              data[existingEntryIndex].room = cellText.split("\n")[2] || "";
                              data[existingEntryIndex].subject = cellText.split("\n")[3] || "";
                          } else {
                              // Create new entry
                              const newEntry = {
                                  time: time,
                                  day: day,
                                  teacher: cellText.split("\n")[0] || "",
                                  group: cellText.split("\n")[1] || "",
                                  room: cellText.split("\n")[2] || "",
                                  subject: cellText.split("\n")[3] || "",
                              };
                              data.push(newEntry);
                          }
                      }
                  } else if (cell.innerText) {
                      // Если ячейка содержит текст напрямую
                      const cellText = cell.innerText;

                      // Check if there is an existing entry for this time and day
                      const existingEntryIndex = data.findIndex(
                          (item) =>
                              normalizeTimeFormat(item.time) === time && item.day === day
                      );

                      if (existingEntryIndex !== -1) {
                          // Update existing entry
                          data[existingEntryIndex].teacher = cellText.split("\n")[0] || "";
                          data[existingEntryIndex].group = cellText.split("\n")[1] || "";
                          data[existingEntryIndex].room = cellText.split("\n")[2] || "";
                          data[existingEntryIndex].subject = cellText.split("\n")[3] || "";
                      } else {
                          // Create new entry
                          const newEntry = {
                              time: time,
                              day: day,
                              teacher: cellText.split("\n")[0] || "",
                              group: cellText.split("\n")[1] || "",
                              room: cellText.split("\n")[2] || "",
                              subject: cellText.split("\n")[3] || "",
                          };
                          data.push(newEntry);
                      }
                  }
              }
          }

          populateTable(data, deletedEntries);
          populateEditTable(data, deletedEntries);
          populateTeacherTable(data, deletedEntries);
          populateRoomTable(
              data,
              document.getElementById("roomSelect").value,
              deletedEntries
          );
          populateEditRoomEditTable(
              data,
              document.getElementById("editRoomSelect").value,
              deletedEntries
          );
          populateEditTeacherTable(
              data,
              document.getElementById("editTeacherSelect").value,
              deletedEntries
          );
          populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);

          saveDataAndDownload(data);
      });

  // Обработчик для поиска преподавателя
  document
      .getElementById("teacherSearch")
      .addEventListener("input", function () {
          const searchTerm = this.value.toLowerCase();

          // Clear existing table
          const teacherTableBody = document
              .getElementById("teacherTable")
              .getElementsByTagName("tbody")[0];
          teacherTableBody.innerHTML = "";

          // Filter data based on search term
          const filteredData = data.filter((item) =>
              item.teacher.toLowerCase().includes(searchTerm)
          );

          // Populate the table with the filtered data
          filteredData.forEach((item) => {
              if (
                  deletedEntries.some(
                      (deleted) =>
                          normalizeTimeFormat(deleted.time) === item.time &&
                          deleted.day === item.day
                  )
              ) {
                  return; // Skip deleted entries
              }

              const row = teacherTableBody.insertRow();
              row.insertCell(0).innerText = item.teacher;
              row.insertCell(1).innerText = item.time;
              row.insertCell(2).innerText = item.day;
              row.insertCell(3).innerText = item.group;
              row.insertCell(4).innerText = item.room;
              row.insertCell(5).innerText = item.subject;

              // Add delete button
              const deleteButton = document.createElement("button");
              deleteButton.textContent = "🗑 Удалить";
              deleteButton.classList.add("delete-button"); // Добавляем класс!
              deleteButton.onclick = function () {
                  deletedEntries.push(item);
                  populateTeacherTable(data, deletedEntries);
                  populateTable(data, deletedEntries);
                  populateEditTable(data, deletedEntries);
                  populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);
              };
              row.insertCell(6).appendChild(deleteButton);
          });
      });

  // Обработчик изменения выбора группы
  document.getElementById("groupSelect").addEventListener("change", function () {
      const selectedGroup = this.value;
      populateGroupTable(data, selectedGroup, deletedEntries);
  });

  // Логика переключения вкладок
  document.querySelectorAll(".tab").forEach((tab) => {
      tab.addEventListener("click", function (e) {
          e.preventDefault();
          const targetTab = this.getAttribute("data-tab");

          document.querySelectorAll(".tab-content").forEach((content) => {
              content.classList.remove("active");
          });

          document.getElementById(`${targetTab}Schedule`).classList.add("active");
          if (targetTab === "group") {
              populateTable(data, deletedEntries);
          } else if (targetTab === "teacher") {
              populateTeacherTable(data, deletedEntries);
          } else if (targetTab === "room") {
              populateRoomTable(
                  data,
                  document.getElementById("roomSelect").value,
                  deletedEntries
              );
          } else if (targetTab === "edit") {
              populateEditTable(data, deletedEntries);
          } else if (targetTab === "editRoom") {
              populateEditRoomEditTable(
                  data,
                  document.getElementById("editRoomSelect").value,
                  deletedEntries
              );
          } else if (targetTab === "editTeacher") {
              populateEditTeacherTable(
                  data,
                  document.getElementById("editTeacherSelect").value,
                  deletedEntries
              );
          } else if (targetTab === "groupSort") {
              populateGroupTable(data, document.getElementById("groupSelect").value, deletedEntries);
          }
      });
  });
});

const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('toggleSidebar');

function updateSidebarState() {
    if (window.outerWidth <= 700) {
        const isOpen = sidebar.classList.contains('open');
        // sidebar.style.transition = 'max-height 0.5s ease, opacity 0.5s ease';
        
        if (isOpen) {
            const height = sidebar.scrollHeight;
            sidebar.style.maxHeight = `${height}px`;
            // sidebar.style.opacity = '1';
            // sidebar.style.display = '';
        } else {
            sidebar.style.maxHeight = '0';
            // sidebar.style.opacity = '0';
            // sidebar.style.display = 'none';
            // setTimeout(() => {
                // sidebar.style.removeProperty('transition');
            // }, 500);
        }
    }
}

toggleBtn.addEventListener('click', () => {
    // toggleBtn.classList.toggle('active');
    // if (toggleBtn.classList.contains('active')) {
        // sidebar.style.display="none";
    // }
    // else {
        // sidebar.style.display="";
    // }
    if (window.outerWidth <= 700) {
        sidebar.classList.toggle('open');
        updateSidebarState();
        
        // Полное скрытие элементов после анимации
        // if (!sidebar.classList.contains('open')) {
            // setTimeout(() => {
                // sidebar.querySelectorAll('a').forEach(link => {
                    // link.style.display = 'none';
                // });
            // }, 500);
        // } else {
            // sidebar.querySelectorAll('a').forEach(link => {
                // link.style.display = 'flex';
            // });
        // }
    } else {
        sidebar.classList.toggle('collapsed');
    }
});

window.addEventListener('resize', () => {
    if (window.outerWidth > 700) {
        sidebar.classList.remove('open');
        sidebar.style.removeProperty('max-height');
        // sidebar.style.removeProperty('opacity');
        // sidebar.style.removeProperty('transition');
        sidebar.querySelectorAll('a').forEach(link => {
            link.style.display = 'flex';
        });
    } else {
        sidebar.classList.remove('collapsed');
    }
});

function wrapTablesForMobile() {
    if (window.innerWidth <= 700) {
      document.querySelectorAll('table').forEach(table => {
        if (!table.parentElement.classList.contains('scrollable-table-wrapper')) {
          const wrapper = document.createElement('div');
          wrapper.className = 'scrollable-table-wrapper';
          table.parentNode.insertBefore(wrapper, table);
          wrapper.appendChild(table);
        }
      });
    }
  }
  
  window.addEventListener('DOMContentLoaded', wrapTablesForMobile);
  window.addEventListener('resize', wrapTablesForMobile);

  document.getElementById("saveTeacherChangesButton").onclick = function() {
    const tbody = document.getElementById("editTeacherTable").getElementsByTagName("tbody")[0];
    const rows = tbody.getElementsByTagName("tr");
    let updatedData = [];

    for (let row of rows) {
        // Если вы используете contentEditable:
        updatedData.push({
            teacher: row.cells[0].innerText.trim(),
            time: row.cells[1].innerText.trim(),
            day: row.cells[2].innerText.trim(),
            group: row.cells[3].innerText.trim(),
            room: row.cells[4].innerText.trim(),
            subject: row.cells[5].innerText.trim(),
        });
        // Если используете <input>, то row.cells[1].querySelector('input').value и т.д.
    }

    // Здесь нужно обновить ваши данные (data) и вызвать функцию сохранения
    saveDataAndDownload(updatedData);

    showNotification("Изменения сохранены!");
};
