<?php
include("../includes/auth_check.php");

requireRole('Admin');
session_start();
include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: add_user.php");
    exit();
}

// Get Form Data
$first_name   = trim($_POST['first_name']);
$middle_name  = trim($_POST['middle_name']);
$last_name    = trim($_POST['last_name']);
$email        = trim($_POST['email']);
$phone        = trim($_POST['phone']);
$gender       = trim($_POST['gender']);
$department = intval($_POST['department_id']);
$shift      = intval($_POST['shift_id']);
$role       = trim($_POST['role']);
$position     = trim($_POST['position']);
$password     = $_POST['password'];
$confirm      = $_POST['confirm_password'];
$status       = trim($_POST['status']);

// Password Match
if ($password !== $confirm) {
    die("Passwords do not match.");
}

// Check Email
$checkEmail = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
mysqli_stmt_bind_param($checkEmail, "s", $email);
mysqli_stmt_execute($checkEmail);
mysqli_stmt_store_result($checkEmail);

if (mysqli_stmt_num_rows($checkEmail) > 0) {
    die("Email already exists.");
}

// Check Phone
$checkPhone = mysqli_prepare($conn, "SELECT id FROM users WHERE phone=?");
mysqli_stmt_bind_param($checkPhone, "s", $phone);
mysqli_stmt_execute($checkPhone);
mysqli_stmt_store_result($checkPhone);

if (mysqli_stmt_num_rows($checkPhone) > 0) {
    die("Phone number already exists.");
}

// Generate User Code
switch ($role) {
    case "Admin":
        $prefix = "ADM";
        break;

    case "Supervisor":
        $prefix = "SUP";
        break;

    default:
        $prefix = "INT";
}

$query = mysqli_query($conn,
"SELECT COUNT(*) AS total FROM users WHERE role='$role'");

$total = mysqli_fetch_assoc($query)['total'] + 1;

$user_code = $prefix . date("Y") . str_pad($total, 3, "0", STR_PAD_LEFT);

// Password Hash
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Upload Image
$photo = "default.png";

if (!empty($_FILES['profile_photo']['name'])) {

    $photo = time() . "_" . $_FILES['profile_photo']['name'];

    move_uploaded_file(
        $_FILES['profile_photo']['tmp_name'],
        "../assets/uploads/profiles/" . $photo
    );
}

// Save User
$sql = "INSERT INTO users
(user_code,
first_name,
middle_name,
last_name,
email,
phone,
gender,
password,
role,
department_id,
shift_id,
position,
profile_photo,
status)

VALUES

(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "sssssssssiisss",
    $user_code,
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $phone,
    $gender,
    $passwordHash,
    $role,
    $department,
    $shift,
    $position,
    $photo,
    $status
);

if (mysqli_stmt_execute($stmt)) {

    header("Location: users.php");

} else {

    echo "Error: " . mysqli_error($conn);

}
?>