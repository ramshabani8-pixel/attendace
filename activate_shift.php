<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../config/database.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shifts.php");
    exit();
}

$id = intval($_GET['id']);

$sql = "UPDATE shifts
        SET status = 'Active'
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    header("Location: shifts.php?activated=1");
    exit();

} else {

    echo "Failed to activate shift.";

}

?>