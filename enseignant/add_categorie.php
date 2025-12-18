<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";
include __DIR__ . "/../includes/header.php";

/* protection login (ila user makach enseignant raj3o login) */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* CREATE */
if (isset($_POST['add_category'])) {

    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF token");
    }

    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $enseignant_id = $_SESSION['user_id'];

    if (!empty($nom)) {
        $stmt = $conn->prepare(
            "INSERT INTO categories (nom, description, created_by) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("ssi", $nom, $description, $enseignant_id);
        $stmt->execute();
    }
}

/* DELETE */
if (isset($_POST['delete_category'])) {

    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF token");
    }

    //tranform to int pour eviter attack (sql injection)
    $id = (int) $_POST['id'];

    // check existence + ownership
    $check = $conn->prepare(
        "SELECT id FROM categories WHERE id = ? AND created_by = ?"
    );
    $check->bind_param("ii", $id, $_SESSION['user_id']);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 1) {
        $stmt = $conn->prepare(
            "DELETE FROM categories WHERE id = ? AND created_by = ?"
        );
        $stmt->bind_param("ii", $id, $_SESSION['user_id']);
        $stmt->execute();
    }
}

//update

if (isset($_POST['update_categorie'])) {
    if (
        !isset($_POST['csrf_token'])||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF Token");
    }

    $id = (int) $_POST['id'];
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $enseignant_id = $_SESSION['user_id'];

    // check existence + ownership
    $check = $conn->prepare(
        "SELECT id FROM categories WHERE id = ? AND created_by = ?"
    );
    $check->bind_param("ii", $id, $enseignant_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 1 && !empty($nom)) {
        $stmt = $conn->prepare(
            "UPDATE categories 
             SET nom = ?, description = ?, updated_at = NOW()
             WHERE id = ? AND created_by = ?"
        );
        $stmt->bind_param("ssii", $nom, $description, $id, $enseignant_id);
        $stmt->execute();
    }
}

/* READ */
$stmt = $conn->prepare( 
    "SELECT id, nom, description, created_at
     FROM categories
     WHERE created_by = ?
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$categories = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head> 
    <meta charset="UTF-8">
    <title>Categories</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

<h1 class="text-2xl font-bold mb-6">Gestion des catégories</h1>

<!-- ADD CATEGORY -->
<form method="POST" class="bg-white p-6 rounded-xl shadow mb-8 space-y-4">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <input
        type="text"
        name="nom"
        placeholder="Nom de la catégorie"
        class="w-full border p-3 rounded"
        required
    >

    <textarea
        name="description"
        placeholder="Description"
        class="w-full border p-3 rounded"
    ></textarea>

    <button
        type="submit"
        name="add_category"
        class="bg-indigo-600 text-white px-6 py-2 rounded"
    >
        Ajouter
    </button>
</form>

<!-- LIST -->
<table class="w-full bg-white rounded-xl shadow">
    <thead class="bg-gray-200">
        <tr>
            <th class="p-3 text-left">Nom</th>
            <th class="p-3 text-left">Description</th>
            <th class="p-3 text-left">Date</th>
            <th class="p-3 text-left">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $categories->fetch_assoc()) : ?>
        <tr class="border-t">
            <td class="p-3"><?= htmlspecialchars($row['nom']) ?></td>
            <td class="p-3"><?= htmlspecialchars($row['description']) ?></td>
            <td class="p-3"><?= $row['created_at'] ?></td>
            <td class="p-3 space-x-3">

                <!-- DELETE -->
                <form method="POST" class="inline" onsubmit="return confirm('Supprimer ?')">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button
                        type="submit"
                        name="delete_category"
                        class="text-red-600 font-semibold"
                    >
                        Supprimer
                    </button>
                </form>

                <!-- EDIT -->
                <form method="POST" class="inline">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="nom" value="<?= $row['nom'] ?>">
                    <input type="hidden" name="description" value="<?= $row['description'] ?>">
                    <!-- <button
                        type="submit"
                        name="update_categorie"
                        class="text-blue-600 font-semibold"
                    >
                        Modifier
                    </button> -->
                    <a href="edit_categorie.php?id=<?= $row['id'] ?>" class="text-blue-600 font-semibold">Modifier</a>

                </form>

            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>



<script>
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');

            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleDropdown') === -1) {
                if (!dropdown.contains(event.target)) {
                    dropdown.classList.add('hidden');
                }
            }
        });
</script>

</body>
</html>
