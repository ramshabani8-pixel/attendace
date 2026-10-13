<?php

include("../includes/auth_check.php");
include("../config/database.php");

$user_id = $_SESSION['user_id'];

$sql = "
SELECT *
FROM notifications
WHERE user_id = ?
ORDER BY created_at DESC
";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $user_id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

include("../includes/header.php");
include("../includes/sidebar.php");
include("../includes/topbar.php");

?>

<div class="container-fluid">

<h3 class="mb-4">
Notifications
</h3>

<div class="card shadow-sm">

<div class="card-body">

<table class="table table-hover">

<thead>

<tr>

<th>Title</th>

<th>Message</th>

<th>Type</th>

<th>Date</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($result)){ ?>

<tr>

<td>

<?php echo htmlspecialchars($row['title']); ?>

</td>

<td>

<?php echo htmlspecialchars($row['message']); ?>

</td>

<td>

<?php echo $row['notification_type']; ?>

</td>

<td>

<?php echo date("d M Y H:i",strtotime($row['created_at'])); ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include("../includes/footer.php"); ?>