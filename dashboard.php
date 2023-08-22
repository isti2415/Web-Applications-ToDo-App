<?php
// Initialize the session
session_start();

// Check if the user is already logged in
if (!isset($_SESSION["loggedin"]) && !$_SESSION["loggedin"] === true) {
    header("location: login.php");
    exit;
}

// Initialization
$groupName = "";
$alertMessage = "";
$alertType = "";

// Include database configuration
require_once "config.php";

// Handle form submission for group creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $groupName = trim($_POST["name"]);

    // Make sure user is logged in
    if (isset($_SESSION["user_id"])) {
        $userId = $_SESSION["user_id"];

        // Insert into database
        $sql = "INSERT INTO groups (name, created_by) VALUES (?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $groupName, $userId);

            if (mysqli_stmt_execute($stmt)) {
                $alertMessage = "Group created successfully.";
                $alertType = "success";
            } else {
                $alertMessage = "Something went wrong. Please try again later.";
                $alertType = "error";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $alertMessage = "You must be logged in to create a group.";
        $alertType = "error";
    }
}
?>

<?php
// Assuming $link is your database connection
$userId = $_SESSION["user_id"]; // Make sure the user is logged in and you have the user ID

// Retrieve groups owned by the user
$sql = "SELECT * FROM groups WHERE created_by = ?";
$groups = [];

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $userId);

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $group = $row;

            // Fetch todos for the current group
            $groupId = $group["group_id"];
            $sql_todos = "SELECT todo_id, item, description, complete FROM todos WHERE group_id = ?";

            if ($stmt_todos = mysqli_prepare($link, $sql_todos)) {
                mysqli_stmt_bind_param($stmt_todos, "i", $groupId);
                if (mysqli_stmt_execute($stmt_todos)) {
                    $result_todos = mysqli_stmt_get_result($stmt_todos);
                    $todos = [];

                    while ($row_todos = mysqli_fetch_assoc($result_todos)) {
                        $todos[] = $row_todos;
                    }

                    $group["todos"] = $todos; // Add todos to the group array
                }
                mysqli_stmt_close($stmt_todos);
            }

            $groups[] = $group;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_todo'])) {
    // Initialize variables
    $alertMessage = "";
    $alertType = "";

    // Make sure user is logged in
    if (isset($_SESSION["user_id"])) {
        $userId = $_SESSION["user_id"];
        $groupName = $_POST['groupName'];
        $item = $_POST["item"];
        $description = $_POST["description"];

        // Fetch group ID using modalGroupName
        $sql = "SELECT group_id FROM groups WHERE name = ? AND created_by = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "si", $groupName, $userId);
            if (mysqli_stmt_execute($stmt)) {
                $result = mysqli_stmt_get_result($stmt);
                if (mysqli_num_rows($result) == 1) {
                    $row = mysqli_fetch_assoc($result);
                    $groupId = $row["group_id"];
                } else {
                    $alertMessage = "Group not found.";
                    $alertType = "error";
                }
            } else {
                $alertMessage = "Something went wrong when fetching the group ID. Please try again later.";
                $alertType = "error";
            }
            mysqli_stmt_close($stmt);
        }

        // Insert into database (use prepared statements to prevent SQL injection)
        $sql = "INSERT INTO todos (group_id, item, description) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "iss", $groupId, $item, $description);
            if (mysqli_stmt_execute($stmt)) {
                // Todo creation successful
                $alertMessage = "Todo added successfully.";
                $alertType = "success";
            } else {
                $alertMessage = "Something went wrong when adding the todo. Please try again later.";
                $alertType = "error";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $alertMessage = "You must be logged in to add a todo.";
        $alertType = "error";
    }
}
?>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $todoId = $_POST['todo_id'];
    $complete = isset($_POST['complete']) ? 1 : 0;
    $sql = "UPDATE todos SET complete = ? WHERE todo_id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $complete, $todoId);
        if (mysqli_stmt_execute($stmt)) {
            // Todo creation successful
            $alertMessage = "Todo updated successfully.";
            $alertType = "success";
        } else {
            $alertMessage = "Something went wrong when updating the todo. Please try again later.";
            $alertType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($link);
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

<body class="min-h-screen bg-gray-50 dark:bg-gray-900">

    <nav class="bg-white border-gray-200 dark:bg-gray-900">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <a class="flex items-center">
                <img src="/images/todo.png" class="h-8 mr-3" alt="Todo App Logo" />
                <span class="self-center text-2xl font-semibold whitespace-nowrap dark:text-white">Todo App</span>
            </a>
            <div class="flex items-center md:order-2">
                <button type="button" class="flex mr-3 text-sm bg-gray-800 rounded-full md:mr-0 focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                    <span class="sr-only">Open user menu</span>
                    <img class="w-8 h-8 rounded-full" src="<?php echo htmlspecialchars($_SESSION['picture'], ENT_QUOTES); ?>" alt="user photo">
                </button>
                <!-- Dropdown menu -->
                <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow dark:bg-gray-700 dark:divide-gray-600" id="user-dropdown">
                    <div class="px-4 py-3">
                        <span class="block text-sm text-gray-900 dark:text-white"><?php echo $_SESSION["name"] ?></span>
                        <span class="block text-sm  text-gray-500 truncate dark:text-gray-400"><?php echo $_SESSION["email"] ?></span>
                    </div>
                    <ul class="py-2" aria-labelledby="user-menu-button">
                        <li>
                            <a href="/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Dashboard</a>
                        </li>
                        <li>
                            <a href="/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Profile</a>
                        </li>
                        <li>
                            <a href="/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">Log out</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <section class="bg-white dark:bg-gray-900 px-8">
        <div class="max-w-screen-xl my-8 lg:my-16 mx-auto flex justify-between items-center">
            <h2 class="text-2xl md:text-4xl tracking-tight font-extrabold text-gray-900 dark:text-white">Todo Groups</h2>
            <button type="button" data-modal-target="group-modal" data-modal-toggle="group-modal" class="focus:ring-4 focus:outline-none focus:ring-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center focus:ring-gray-600 bg-blue-700 border-blue-600 text-white hover:bg-blue-600 gap-2">
                <svg class="w-5 h-5 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 18 18">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 1v16M1 9h16" />
                </svg>
                Create Group
            </button>
        </div>
        <div id="group-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative rounded-lg bg-gray-100 shadow dark:bg-gray-800">
                    <div class="p-4 mx-auto max-w-2xl lg:p-8">
                        <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Add a todo group</h2>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="grid gap-4 sm:grid-cols-2 sm:gap-6">
                                <div class="sm:col-span-2">
                                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Group Name</label>
                                    <input type="text" name="name" id="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Type group name" required="">
                                    <input type="hidden" name="create_group" value="1">
                                </div>
                            </div>
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 mt-4 sm:mt-6 text-sm font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                Create Group
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="max-w-screen-xl grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mx-auto">
            <!-- Card 1 -->
            <?php
            foreach ($groups as $group) {
                echo '<div class="p-4 bg-white border border-gray-200 rounded-lg shadow sm:p-8 dark:bg-gray-800 dark:border-gray-700">';
                echo '    <div class="flex items-center justify-between mb-4">';
                echo '        <h5 class="text-xl font-bold leading-none text-gray-900 dark:text-white">' . htmlspecialchars($group['name']) . '</h5>';
                echo '    </div>';
                echo '    <div class="flow-root">';
                echo '        <ul role="list" class="divide-y divide-gray-200 dark:divide-gray-700">';
                foreach ($group["todos"] as $todo) {
                    echo '<li class="py-2.5 flex items-center gap-2">';
                    echo '<form action="' . htmlspecialchars($_SERVER["PHP_SELF"]) . ' " method="POST">'; // Replace with the correct endpoint
                    echo '<input type="hidden" name="todo_id" value="' . $todo['todo_id'] . '">'; // Hidden input to store todo ID
                    echo '<input ' . ($todo['complete'] ? 'checked value="1"' : 'value="0" ') . ' onchange="this.form.submit()" type="checkbox" name="complete" class="w-4 h-4 text-blue-600 bg-blue-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-blue-800 focus:ring-2 dark:bg-blue-700 dark:border-blue-600">'; // Submit button to update the checkbox
                    echo '</form>';
                    echo '<p class="text-md text-gray-800 dark:text-white">' . htmlspecialchars($todo['item']) . '</p>';
                    echo '</li>';
                }
                echo '        </ul>';
                echo '    </div>';
                echo '<button data-group-name="' . htmlspecialchars($group['name']) . '" data-modal-target="todo-modal" data-modal-toggle="todo-modal" class="flex items-center justify-center w-full block px-4 py-2 mt-4 text-sm font-medium text-white rounded-lg focus:ring focus:ring-blue-300 focus:outline-none focus:ring-opacity-50 border">';
                echo '    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5 inline-block mr-1">';
                echo '        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>';
                echo '    </svg>';
                echo '    Add Items';
                echo '</button>';
                echo '</div>';
            }
            ?>
        </div>

        <div id="todo-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-md max-h-full">
                <div class="relative rounded-lg bg-gray-100 shadow dark:bg-gray-800">
                    <div class="p-4 mx-auto max-w-2xl lg:p-8">
                        <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white"></h2>
                        <form id="todo_form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="hidden" id="hiddenGroupName" name="groupName" value="">
                            <div class="grid gap-4 sm:grid-cols-2 sm:gap-6">
                                <div class="sm:col-span-2">
                                    <label for="item" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Item</label>
                                    <input type="hidden" name="add_todo" value="1">
                                    <input type="text" name="item" id="item" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-600 focus:border-blue-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Product item" required="">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="description" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Description</label>
                                    <textarea id="description" name="description" rows="4" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Your description here"></textarea>
                                </div>
                            </div>
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 mt-4 sm:mt-6 text-sm font-medium text-center text-white bg-blue-700 rounded-lg focus:ring-4 focus:ring-blue-200 dark:focus:ring-blue-900 hover:bg-blue-800">
                                Add Todo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const cards = document.querySelectorAll('[data-modal-target="todo-modal"]');

            cards.forEach((card) => {
                card.addEventListener("click", function() {
                    const groupName = this.getAttribute("data-group-name");
                    document.querySelector("#todo-modal h2").textContent = groupName;
                    document.querySelector("#hiddenGroupName").value = groupName;
                });
            });
        });
    </script>

    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.1/flowbite.min.js"></script>
</body>

</html>