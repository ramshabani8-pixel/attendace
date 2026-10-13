<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../config/database.php");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: add_shift.php");

    exit();

}


// Get form data

$shift_name     = trim($_POST['shift_name'] ?? '');
$check_in_time  = $_POST['check_in_time'] ?? '';
$late_after     = $_POST['late_after'] ?? '';
$check_out_time = $_POST['check_out_time'] ?? '';
$status         = $_POST['status'] ?? 'Active';


// Validate required fields

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
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $checkShift,
    "s",
    $shift_name
);

mysqli_stmt_execute($checkShift);

mysqli_stmt_store_result($checkShift);


if (mysqli_stmt_num_rows($checkShift) > 0) {

    die("A shift with this name already exists.");

}


mysqli_stmt_close($checkShift);


// Insert shift

$sql = "INSERT INTO shifts
        (
            shift_name,
            check_in_time,
            late_after,
            check_out_time,
            status
        )
        VALUES
        (?, ?, ?, ?, ?)";


$stmt = mysqli_prepare($conn, $sql);


mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $shift_name,
    $check_in_time,
    $late_after,
    $check_out_time,
    $status
);


if (mysqli_stmt_execute($stmt)) {

    header("Location: shifts.php?success=1");

    exit();

} else {

    echo "Failed to save shift: " . mysqli_error($conn);

}

?>