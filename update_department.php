<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../config/database.php");

// Only allow POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: departments.php");
    exit();
}

// Get submitted data
$id              = intval($_POST['id'] ?? 0);
$department_name = trim($_POST['department_name'] ?? '');
$department_code = strtoupper(trim($_POST['department_code'] ?? ''));
$description     = trim($_POST['description'] ?? '');
$status          = $_POST['status'] ?? 'Active';

// Validate ID
if ($id <= 0) {
    die("Invalid department ID.");
}

// Validate required fields
if (empty($department_name) || empty($department_code)) {
    die("Department name and department code are required.");
}


// Check duplicate department name
$checkName = mysqli_prepare(
    $conn,
    "SELECT id
     FROM departments
     WHERE department_name = ?
     AND id != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkName,
    "si",
    $department_name,
    $id
);

mysqli_stmt_execute($checkName);

mysqli_stmt_store_result($checkName);

if (mysqli_stmt_num_rows($checkName) > 0) {

    die("Another department already uses this name.");

}

mysqli_stmt_close($checkName);


// Check duplicate department code
$checkCode = mysqli_prepare(
    $conn,
    "SELECT id
     FROM departments
     WHERE department_code = ?
     AND id != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkCode,
    "si",
    $department_code,
    $id
);

mysqli_stmt_execute($checkCode);

mysqli_stmt_store_result($checkCode);

if (mysqli_stmt_num_rows($checkCode) > 0) {

    die("Another department already uses this code.");

}

mysqli_stmt_close($checkCode);


// Update department
$sql = "UPDATE departments
        SET department_name = ?,
            department_code = ?,
            description = ?,
            status = ?
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssi",
    $department_name,
    $department_code,
    $description,
    $status,
    $id
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: departments.php?updated=1");
    exit();

} else {

    echo "Failed to update department: " . mysqli_error($conn);

}

?>