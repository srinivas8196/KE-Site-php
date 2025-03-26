<?php
if (session_status() == PHP_SESSION_NONE) { // Check if a session is NOT already started
    session_start(); // Start the session only if not already started
}

// Set session timeout (e.g., 1 hour)
$timeout = 3600; // 1 hour in seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    // Last activity was more than 1 hour ago, destroy the session
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1"); // Added timeout parameter for login page
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time(); // Update last activity time

// Regenerate session ID periodically to prevent session fixation attacks
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 1800) { // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// Check if user is logged in (moved to the end for clarity)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karma Experience</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/styles.css">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100">

<header class="bg-gray-900 text-white p-4 flex justify-between items-center shadow-md fixed top-0 left-0 w-full z-50">
    <!-- Logo -->
    <a href="dashboard.php" class="text-lg font-bold">
        <img src="assets/images/logo/KE-Gold.png" alt="Karma Experience" class="h-10">
    </a>

    <!-- Navigation Menu -->
    <nav class="hidden md:flex space-x-6">
        <a href="dashboard.php" class="hover:text-yellow-400">Dashboard</a>
        <a href="destination_list.php" class="hover:text-yellow-400">Destinations</a>
        <a href="manage-users.php" class="hover:text-yellow-400">Users</a>
        <!-- <a href="settings.php" class="hover:text-yellow-400">Settings</a> -->
    </nav>

    <!-- Profile Dropdown -->
    <div class="relative">
        <button id="profileBtn" class="flex items-center space-x-2 bg-gray-800 px-4 py-2 rounded focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Admin</span>
        </button>
        <div id="profileDropdown" class="absolute right-0 mt-2 w-40 bg-white text-gray-900 shadow-lg rounded hidden">
            <a href="profile.php" class="block px-4 py-2 hover:bg-gray-200">My Profile</a>
            <a href="logout.php" class="block px-4 py-2 hover:bg-gray-200">Logout</a>
        </div>
    </div>
</header>

<!-- JavaScript for Profile Dropdown -->
<script>
    document.getElementById("profileBtn").addEventListener("click", function() {
        document.getElementById("profileDropdown").classList.toggle("hidden");
    });

    document.addEventListener("click", function(event) {
        if (!document.getElementById("profileBtn").contains(event.target)) {
            document.getElementById("profileDropdown").classList.add("hidden");
        }
    });

    // Initialize Lucide Icons
    lucide.createIcons();
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- CSS to prevent content overlap -->
<style>
    body {
        padding-top: 75px; /* Adjust to match header height */
    }
</style>

</body>
</html>
