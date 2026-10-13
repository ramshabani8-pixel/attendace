<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../config/database.php");

// Only POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: shifts.php");
    exit();
}


// Get data
$id             = intval($_POST['id'] ?? 0);
$shift_name     = trim($_POST['shift_name'] ?? '');
$check_in_time  = $_POST['check_in_time'] ?? '';
$late_after     = $_POST['late_after'] ?? '';
$check_out_time = $_POST['check_out_time'] ?? '';
$status         = $_POST['status'] ?? 'Active';


// Validate ID
if ($id <= 0) {
    die("Invalid shift ID.");
}


// Validate fields
if (
    empty($shift_name) ||
    empty($check_in_time) ||
    empty($late_after) ||
    empty($check_out_time)
) {
    die("All shift fields are required.");
}


// Validate time logic

if ($late_after < $check_in_time) {
    die("Late time cannot be earlier than check-in time.");
}

if ($check_out_time <= $check_in_time) {
    die("Check-out time must be later than check-in time.");
}


// Check duplicate shift name
$checkShift = mysqli_prepare(
    $conn,
    "SELECT id
     FROM shifts
     WHERE shift_name = ?
     AND id != ?
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkShift,
    "si",
    $shift_name,
    $id
);

mysqli_stmt_execute($checkShift);

mysqli_stmt_store_result($checkShift);

if (mysqli_stmt_num_rows($checkShift) > 0) {

    die("Another shift with this name already exists.");

}

mysqli_stmt_close($checkShift);


// Update shift
$sql = "UPDATE shifts
        SET shift_name = ?,
            check_in_time = ?,
            late_after = ?,
            check_out_time = ?,
            status = ?
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "sssssi",
    $shift_name,
    $check_in_time,
    $late_after,
    $check_out_time,
    $status,
    $id
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: shifts.php?updated=1");
    exit();

} else {

    echo "Failed to update shift: " . mysqli_error($conn);

}

?>