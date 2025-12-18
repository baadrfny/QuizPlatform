<?php
session_start();
require_once "../config/database.php";
require_once "../includes/securite.php";

/* protection login */
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'enseignant') {
    header("Location: ../auth/login.php");
    exit;
}

/* UPDATE POST handling */
if (isset($_POST['update_categorie'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }

    $id = (int) $_POST['id'];
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $enseignant_id = $_SESSION['user_id'];

    /* check existence + ownership */
    $check = $conn->prepare("SELECT id FROM categories WHERE id = ? AND created_by = ?");
    $check->bind_param("ii", $id, $enseignant_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 1 && !empty($nom)) {
        $stmt = $conn->prepare(
            "UPDATE categories SET nom = ?, description = ?, updated_at = NOW() WHERE id = ? AND created_by = ?"
        );
        $stmt->bind_param("ssii", $nom, $description, $id, $enseignant_id);
        $stmt->execute();
    }

    header("Location: add_categorie.php"); // redirect to your list page
    exit;
}

/* CHECK GET ID */
if (!isset($_GET['id'])) {
    header("Location: add_categorie.php");
    exit;
}

$id = (int) $_GET['id'];

/* FETCH CATEGORY */
$stmt = $conn->prepare("SELECT id, nom, description FROM categories WHERE id = ? AND created_by = ?");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: add_categorie.php");
    exit;
}

$categorie = $result->fetch_assoc();

include __DIR__ . "/../includes/header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Catégorie</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 py-10">

<div class="bg-white p-8 rounded-2xl shadow-md w-full max-w-md mx-auto">
    <h2 class="text-2xl font-bold mb-6 text-center">Modifier Catégorie</h2>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="id" value="<?= $categorie['id'] ?>">

        <input
            type="text"
            name="nom"
            value="<?= htmlspecialchars($categorie['nom']) ?>"
            class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Nom de la catégorie"
            required
        >

        <textarea
            name="description"
            class="w-full px-4 py-3 border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
            placeholder="Description"
        ><?= htmlspecialchars($categorie['description']) ?></textarea>

        <button
            type="submit"
            name="update_categorie"
            class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition"
        >
            Enregistrer
        </button>
    </form>
</div>

</body>
</html>
