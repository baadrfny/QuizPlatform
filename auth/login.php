<?php
require_once "../config/database.php";
require_once "../includes/securite.php";


// if (empty($_SESSION['csrf_token'])) {
//     $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
// }

if (isset($_POST['login'])) {

    if (!isset($_POST['csrf_token'])||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        die("Invali CSRF Token");
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "All fields are required";
        header("Location: login.php");
        exit;
    }


    

    // get user
    $stmt = $conn->prepare(
        "SELECT id, nom, email, password_hash, role FROM users WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // password_verify = compare password with hash
        if (password_verify($password, $user['password_hash'])) {

            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];

            if ($user['role'] === 'enseignant') {
                header("Location: ../enseignant/dashboard.php");
            } else {
                header("Location: ../etudiant/dashboard.php");
            }
            exit;
        }
    }

    $_SESSION['login_error'] = "Invalid email or password";
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-600 to-blue-500">

    <div class="bg-white w-full max-w-md p-8 rounded-2xl shadow-2xl">
        
        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">
            Welcome Back
        </h2>

        <?php
        if (isset($_SESSION['login_error'])) {
            echo "<div class='bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-center'>";
            echo $_SESSION['login_error'];
            echo "</div>";
            unset($_SESSION['login_error']);
        }
        ?>

        <form method="POST" class="space-y-5">


            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

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

            <button 
                type="submit" 
                name="login"
                class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition duration-300"
            >
                Login
            </button>

        </form>

        <p class="text-center text-gray-600 mt-6">
            Don’t have an account?
            <a href="register.php" class="text-indigo-600 font-semibold hover:underline">
                Create one
            </a>
        </p>

    </div>

</body>
</html>

