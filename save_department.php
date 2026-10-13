<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: add_department.php");
    exit();
}

// Get form data
$department_name = trim($_POST['department_name'] ?? '');
$department_code = strtoupper(trim($_POST['department_code'] ?? ''));
$description     = trim($_POST['description'] ?? '');
$status          = $_POST['status'] ?? 'Active';

// Basic validation
if (empty($department_name) || empty($department_code)) {
    die("Department name and department code are required.");
}

// Check duplicate department name
$checkName = mysqli_prepare(
    $conn,
    "SELECT id FROM departments WHERE department_name = ? LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkName,
    "s",
    $department_name
);

mysqli_stmt_execute($checkName);

mysqli_stmt_store_result($checkName);

if (mysqli_stmt_num_rows($checkName) > 0) {
    die("Department name already exists.");
}

mysqli_stmt_close($checkName);


// Check duplicate department code
$checkCode = mysqli_prepare(
    $conn,
    "SELECT id FROM departments WHERE department_code = ? LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkCode,
    "s",
    $department_code
);

mysqli_stmt_execute($checkCode);

mysqli_stmt_store_result($checkCode);

if (mysqli_stmt_num_rows($checkCode) > 0) {
    die("Department code already exists.");
}

mysqli_stmt_close($checkCode);


// Insert department
$sql = "INSERT INTO departments
        (department_name, department_code, description, status)
        VALUES (?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $department_name,
    $department_code,
    $description,
    $status
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: departments.php?success=1");
    exit();

} else {

    echo "Failed to save department: " . mysqli_error($conn);

}

?>