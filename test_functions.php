<?php
// Simple testing file to check if functions work correctly

// Show all errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load main files using absolute paths
require_once 'functions/check_generic.php';
require_once 'functions/functions.php';
require_once 'database/db_connect.php';

// Basic CSS for the output
echo "<style>
    body { font-family: sans-serif; line-height: 1.5; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #eee; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
</style>";

echo "<h1>Project Test Results</h1>";

// Compare expected output with actual results
function runTest($name, $expected, $actual) {
    $status = ($expected === $actual) ? "<span class='pass'>PASS</span>" : "<span class='fail'>FAIL</span>";
    echo "<tr><td>$name</td><td>$status</td></tr>";
}

// 1. Test validation and security logic
echo "<h2>1. Validation & Security</h2>";
echo "<table>";
runTest("Valid Username Check", true, check_field("User1", "/^[A-Za-z0-9]+$/", 3, 10));
runTest("Short Username Check", false, check_field("U1", "/^[A-Za-z0-9]+$/", 3, 10));
runTest("XSS Prevention Check", "&lt;script&gt;", e("<script>"));
echo "</table>";

// 2. Test database connection
echo "<h2>2. DB Connection</h2>";
if ($conn->connect_error) {
    echo "<p class='fail'>Connection Failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p class='pass'>Connected to database: $dbname</p>";
}

// 3. Test if SQL queries run without errors
echo "<h2>3. Data Fetching</h2>";
echo "<table><thead><tr><th>Test</th><th>Result</th></tr></thead>";
$res = getAllCourses($conn);
echo "<tr><td>getAllCourses() query</td><td>" . ($res ? "<span class='pass'>OK</span>" : "<span class='fail'>FAIL</span>") . "</td></tr>";
$res = getProfCourses($conn, 1);
echo "<tr><td>getProfCourses() query</td><td>" . ($res ? "<span class='pass'>OK</span>" : "<span class='fail'>FAIL</span>") . "</td></tr>";
echo "</table>";

// 4. Check if all required tables exist in DB
echo "<h2>4. Table Checks</h2>";
echo "<table>";
$tables = ['users', 'courses', 'assignments', 'submissions'];
foreach ($tables as $t) {
    $check = $conn->query("SHOW TABLES LIKE '$t'");
    $exists = ($check->num_rows == 1);
    echo "<tr><td>Table '$t' exists</td><td>" . ($exists ? "<span class='pass'>YES</span>" : "<span class='fail'>NO</span>") . "</td></tr>";
}
echo "</table>";
?>