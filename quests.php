<?php
// Подключение к базе данных
require 'db.php';

// Функция для генерации списка предметов
function getItemsOptions($selected = '') {
    global $items;
    $options = '<option value="">Выберите предмет</option>';
    foreach ($items as $id => $name) {
        $selectedAttr = ($id == $selected) ? ' selected' : '';
        $options .= '<option value="' . htmlspecialchars($id) . '"' . $selectedAttr . '>' . htmlspecialchars($name) . '</option>';
    }
    return $options;
}

// Получение списка NPC
$npcList = $pdo->query("SELECT id, name FROM npc")->fetchAll(PDO::FETCH_ASSOC);

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['delete_id'])) {
        // Удаление квеста
        $stmt = $pdo->prepare("DELETE FROM quests WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        header("Location: quests.php");
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $gold = intval($_POST['gold'] ?? 0);
    $exp = intval($_POST['exp'] ?? 0);
    $reward_items = implode(',', $_POST['items'] ?? []);
    $requirement_type = $_POST['requirement_type'] ?? '';
    $requirement_value = $_POST['requirement_value'] ?? '';
    $objective_type = $_POST['objective_type'] ?? '';
    $objective_value = $_POST['objective_value'] ?? '';
    $npc_id = $_POST['npc_id'] ?? null; // NPC, который даёт квест

    if ($name && $description && $npc_id) {
        if (!empty($_POST['edit_id'])) {
            // Обновление квеста
            $stmt = $pdo->prepare("UPDATE quests SET name = ?, description = ?, gold = ?, exp = ?, rewards = ?, requirement_type = ?, requirement_value = ?, objective_type = ?, objective_value = ?, npc_id = ? WHERE id = ?");
            $stmt->execute([$name, $description, $gold, $exp, $reward_items, $requirement_type, $requirement_value, $objective_type, $objective_value, $npc_id, $_POST['edit_id']]);
        } else {
            // Добавление нового квеста
            $stmt = $pdo->prepare("INSERT INTO quests (name, description, gold, exp, rewards, requirement_type, requirement_value, objective_type, objective_value, npc_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $gold, $exp, $reward_items, $requirement_type, $requirement_value, $objective_type, $objective_value, $npc_id]);
        }
        header("Location: quests.php");
        exit;
    }
}

// Получение списка квестов и предметов
$quests = $pdo->query("SELECT * FROM quests")->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->query("SELECT id, name FROM items")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление Квестами</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function updateRequirementsFields() {
            let type = document.getElementById('requirement-type').value;
            let container = document.getElementById('requirement-fields');
            container.innerHTML = '';

            if (type === 'item') {
                let select = document.createElement('select');
                select.name = "requirement_value";
                select.className = "form-control mb-2";
                select.innerHTML = '<?= getItemsOptions() ?>';
                container.appendChild(select);
            } else if (type === 'gold') {
                container.innerHTML = '<input type="number" name="requirement_value" class="form-control mb-2" placeholder="Количество золота" min="1">';
            }
        }

        function updateObjectivesFields() {
            let action = document.getElementById('objective-action').value;
            let container = document.getElementById('objective-fields');
            container.innerHTML = '';

            if (action === 'kill') {
                container.innerHTML = '<input type="text" name="objective_value" class="form-control mb-2" placeholder="ID NPC">';
            } else if (action === 'bring') {
                let select = document.createElement('select');
                select.name = "objective_value";
                select.className = "form-control mb-2";
                select.innerHTML = '<?= getItemsOptions() ?>';
                container.appendChild(select);
            }
        }
    </script>
</head>
<body class="container mt-4">

<h2><?= isset($_GET['edit_id']) ? 'Редактировать Квест' : 'Добавить Квест' ?></h2>

<?php
$quest = [];
if (!empty($_GET['edit_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM quests WHERE id = ?");
    $stmt->execute([$_GET['edit_id']]);
    $quest = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<form method="post">
    <input type="hidden" name="edit_id" value="<?= htmlspecialchars($_GET['edit_id'] ?? '') ?>">

    <input type="text" name="name" class="form-control mb-2" placeholder="Название квеста" value="<?= htmlspecialchars($quest['name'] ?? '') ?>" required>
    <textarea name="description" class="form-control mb-2" placeholder="Описание" required><?= htmlspecialchars($quest['description'] ?? '') ?></textarea>

    <!-- NPC, который даёт квест -->
    <label>Выберите NPC, который даёт квест:</label>
    <select name="npc_id" class="form-control mb-2" required>
        <option value="">Выберите NPC</option>
        <?php foreach ($npcList as $npc): ?>
            <option value="<?= htmlspecialchars($npc['id']) ?>" <?= isset($quest['npc_id']) && $quest['npc_id'] == $npc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($npc['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <!-- Требования -->
    <label>Требования:</label>
    <select id="requirement-type" name="requirement_type" class="form-control mb-2" onchange="updateRequirementsFields()">
        <option value="">Выберите тип требования</option>
        <option value="item">Предмет</option>
        <option value="gold">Золото</option>
    </select>
    <div id="requirement-fields" class="mb-3"></div>

    <!-- Задачи -->
    <label>Задачи:</label>
    <select id="objective-action" name="objective_type" class="form-control mb-2" onchange="updateObjectivesFields()">
        <option value="">Выберите тип задачи</option>
        <option value="kill">Убить NPC</option>
        <option value="bring">Принести предмет</option>
    </select>
    <div id="objective-fields" class="mb-3"></div>

    <input type="number" name="gold" class="form-control mb-2" placeholder="Золото" min="0" value="<?= htmlspecialchars($quest['gold'] ?? 0) ?>">
    <input type="number" name="exp" class="form-control mb-2" placeholder="Опыт" min="0" value="<?= htmlspecialchars($quest['exp'] ?? 0) ?>">

    <label>Выберите награды (предметы):</label>
    <select name="items[]" class="form-control mb-2" multiple>
        <?= getItemsOptions(explode(',', $quest['rewards'] ?? '')) ?>
    </select>

    <button type="submit" class="btn btn-primary"><?= isset($_GET['edit_id']) ? 'Обновить' : 'Добавить' ?> Квест</button>
</form>

<h2 class="mt-4">Список Квестов</h2>
<table class="table table-bordered">
    <thead>
    <tr>
        <th>ID</th>
        <th>Название</th>
        <th>Описание</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($quests as $q): ?>
        <tr>
            <td><?= $q['id'] ?></td>
            <td><?= htmlspecialchars($q['name']) ?></td>
            <td><?= htmlspecialchars($q['description']) ?></td>
            <td>
                <a href="?edit_id=<?= $q['id'] ?>" class="btn btn-warning btn-sm">Редактировать</a>
                <form method="post" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $q['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
