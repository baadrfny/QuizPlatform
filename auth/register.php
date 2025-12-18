<?php
include "../config/database.php";
require_once "../includes/securite.php";

/* Generate CSRF token */
// if (empty($_SESSION['csrf_token'])) {
//     $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
// }

/* Handle form submit */
if (isset($_POST['register'])) {

    /* CSRF check */
    if (
        !isset($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invalid CSRF token");
    }

    $nom      = trim($_POST['nom']);
    $email    = strtolower(trim($_POST['email']));
    $password = $_POST['password'];
    $role     = ($_POST['role'] === 'enseignant') ? 'enseignant' : 'etudiant';

    /* Validation */
    if (empty($nom) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: register.php");
        exit();
    }

    /* Check email exists */
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Email already exists";
        header("Location: register.php");
        exit();
    }

    /* Hash password */
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    /* Insert user */
    $stmt = $conn->prepare(
        "INSERT INTO users (nom, email, password_hash, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("ssss", $nom, $email, $password_hash, $role);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Account created successfully";
        header("Location: login.php");
        exit();
    }

    $_SESSION['error'] = "Registration failed";
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-600 to-blue-500">

    <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-2xl">

        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">
            Create Account
        </h2>

        <?php
        if (isset($_SESSION['register_error'])) {
            echo "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-center'>";
            echo $_SESSION['register_error'];
            echo "</div>";
            unset($_SESSION['register_error']);
        }

        if (isset($_SESSION['register_success'])) {
            echo "<div class='bg-green-100 text-green-700 p-3 rounded-lg mb-4 text-center'>";
            echo $_SESSION['register_success'];
            echo "</div>";
            unset($_SESSION['register_success']);
        }
        ?>

        <form method="POST" class="space-y-5">


         <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <input
                type="text"
                name="nom"
                placeholder="Full Name"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >

            <input
                type="email"
                name="email"
                placeholder="Email"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >

            <input
                type="password"
                name="password"
                placeholder="Password"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >

            <select
                name="role"
                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
            >
                <option value="">Select Role</option>
                <option value="enseignant">Enseignant</option>
                <option value="etudiant">Etudiant</option>
            </select>

            <button
                type="submit"
                name="register"
                class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition duration-300"
            >
                Create Account
            </button>

        </form>

        <p class="text-center text-gray-600 mt-6">
            Already have an account?
            <a href="login.php" class="text-indigo-600 font-semibold hover:underline">
                Login
            </a>
        </p>

    </div>

</body>
</html>

