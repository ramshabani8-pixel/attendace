<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../config/database.php");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: departments.php");
    exit();
}

$id = intval($_GET['id']);

$sql = "UPDATE departments
        SET status = 'Active'
        WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    header("Location: departments.php?activated=1");
    exit();

} else {

    echo "Failed to activate department.";

}

?>