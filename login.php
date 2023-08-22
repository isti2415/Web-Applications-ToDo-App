<?php
// Initialize the session
session_start();

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

$email = $password = "";
$alertMessage = "";
$alertType = "";

// Include database configuration
require_once "config.php";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate login credentials
    $sql = "SELECT user_id, name, picture, email, password FROM users WHERE email = ?";

    if ($stmt = mysqli_prepare($link, $sql)) {

        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);

        mysqli_stmt_bind_param($stmt, "s", $param_email);
        $param_email = $email;

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                mysqli_stmt_bind_result($stmt, $user_id, $name, $picture, $email, $hashed_password);
                if (mysqli_stmt_fetch($stmt)) {
                    if (password_verify($password, $hashed_password)) {
                        session_start();

                        $_SESSION["loggedin"] = true;
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["name"] = $name;
                        $_SESSION["picture"] = $picture;
                        $_SESSION["email"] = $email;

                        header("location: dashboard.php");
                    } else {
                        $alertMessage = "The password you entered was not valid.";
                        $alertType = "error";
                    }
                }
            } else {
                $alertMessage = "No account found with that email.";
                $alertType = "error";
            }
        } else {
            $alertMessage = "Oops! Something went wrong. Please try again later.";
            $alertType = "error";
        }
        mysqli_stmt_close($stmt);
    }
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" href="/images/todo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900 font-">
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0 gap-4">
        <a href="/index.php" class="flex items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
            <img class="w-8 h-8 mr-2" src="/images/todo.png" alt="logo">
            Todo App
        </a>
        <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                    Sign in to your account
                </h1>
                <form class="space-y-4 md:space-y-6" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Your email</label>
                        <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="name@company.com" required="">
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                        <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 sm:text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input id="remember" aria-describedby="remember" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-blue-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-blue-600 dark:ring-offset-gray-800">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="remember" class="text-gray-500 dark:text-gray-300">Remember me</label>
                            </div>
                        </div>
                        <a href="#" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-500">Forgot password?</a>
                    </div>
                    <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Sign in</button>
                    <p class="text-sm font-light text-gray-500 dark:text-gray-400">
                        Don’t have an account yet? <a href="/register.php" class="font-medium text-blue-600 hover:underline dark:text-blue-500">Sign up</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <?php
    if ($alertMessage !== "") :
        $alertClass = ($alertType === 'error') ? 'text-red-800 bg-red-50 dark:bg-gray-800 dark:text-red-400' : 'text-green-800 bg-green-50 dark:bg-gray-800 dark:text-green-400';
        echo "<div id='alert-1' class='fixed top-10 right-0 m-4 flex items-center p-4 rounded-lg " . $alertClass . "' role='alert'>";
        echo "<div class='ml-3 text-sm font-medium'>" . $alertMessage . "</div>";
        echo "<button type='button' class='ml-auto -mx-1.5 -my-1.5 rounded-lg focus:ring-2 p-1.5 hover:bg-blue-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-blue-400 dark:hover:bg-gray-700' data-dismiss-target='#alert-1' aria-label='Close'>";
        echo "<span class='sr-only'>Close</span>";
        echo "<svg class='w-3 h-3' aria-hidden='true' xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 14 14'>";
        echo "<path stroke='currentColor' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6'></path>";
        echo "</svg></button></div>";
    endif;
    ?>

    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.1/flowbite.min.js"></script>
</body>

</html>