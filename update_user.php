<?php

include("../includes/auth_check.php");

requireRole('Admin');

include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: users.php");
    exit();
}


// Get submitted data

$id            = intval($_POST['id'] ?? 0);

$first_name    = trim($_POST['first_name'] ?? '');

$middle_name   = trim($_POST['middle_name'] ?? '');

$last_name     = trim($_POST['last_name'] ?? '');

$email         = trim($_POST['email'] ?? '');

$phone         = trim($_POST['phone'] ?? '');

$gender        = trim($_POST['gender'] ?? '');

$department_id = intval($_POST['department_id'] ?? 0);

$shift_id      = intval($_POST['shift_id'] ?? 0);

$role          = trim($_POST['role'] ?? '');

$position      = trim($_POST['position'] ?? '');

$status        = trim($_POST['status'] ?? 'Active');


// Basic validation

if ($id <= 0) {
    die("Invalid user ID.");
}

if (
    empty($first_name) ||
    empty($last_name) ||
    empty($email) ||
    empty($phone) ||
    empty($gender) ||
    $department_id <= 0 ||
    $shift_id <= 0
) {
    die("Please fill in all required fields.");
}


// Check duplicate email
// Ignore the current user's own email

$checkEmail = mysqli_prepare(
    $conn,
    "SELECT id
     FROM users
     WHERE email = ?
     AND id != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkEmail,
    "si",
    $email,
    $id
);

mysqli_stmt_execute($checkEmail);

mysqli_stmt_store_result($checkEmail);

if (mysqli_stmt_num_rows($checkEmail) > 0) {

    die("Another user already uses this email.");

}

mysqli_stmt_close($checkEmail);


// Check duplicate phone

$checkPhone = mysqli_prepare(
    $conn,
    "SELECT id
     FROM users
     WHERE phone = ?
     AND id != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkPhone,
    "si",
    $phone,
    $id
);

mysqli_stmt_execute($checkPhone);

mysqli_stmt_store_result($checkPhone);

if (mysqli_stmt_num_rows($checkPhone) > 0) {

    die("Another user already uses this phone number.");

}

mysqli_stmt_close($checkPhone);


// Update User

$sql = "UPDATE users
        SET first_name = ?,
            middle_name = ?,
            last_name = ?,
            email = ?,
            phone = ?,
            gender = ?,
            department_id = ?,
            shift_id = ?,
            role = ?,
            position = ?,
            status = ?
        WHERE id = ?";


$stmt = mysqli_prepare($conn, $sql);


mysqli_stmt_bind_param(
    $stmt,
    "ssssssiisssi",
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $phone,
    $gender,
    $department_id,
    $shift_id,
    $role,
    $position,
    $status,
    $id
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: users.php?updated=1");
    exit();

} else {

    echo "Failed to update user: " . mysqli_error($conn);

}

?>