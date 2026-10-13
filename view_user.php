<?php
include("../includes/header.php");
include("../config/database.php");

if(!isset($_GET['id'])){
    header("Location: users.php");
    exit();
}

$id = intval($_GET['id']);

$sql = "SELECT users.*, departments.department_name
        FROM users
        LEFT JOIN departments
        ON users.department_id = departments.id
        WHERE users.id=?";

$stmt = mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

include("../includes/sidebar.php");
include("../includes/topbar.php");
?>

<div class="container-fluid">

<div class="card">

<div class="card-header bg-primary text-white">

<h4>User Profile</h4>

</div>

<div class="card-body">

<div class="row">

<div class="col-md-3 text-center">

<img
src="../assets/uploads/profiles/<?php echo $user['profile_photo'];?>"
class="img-fluid rounded-circle"
style="width:180px;height:180px;object-fit:cover;">

</div>

<div class="col-md-9">

<table class="table table-bordered">

<tr>
<th>User Code</th>
<td><?php echo $user['user_code']; ?></td>
</tr>

<tr>
<th>Full Name</th>
<td>
<?php
echo $user['first_name']." ".$user['middle_name']." ".$user['last_name'];
?>
</td>
</tr>

<tr>
<th>Email</th>
<td><?php echo $user['email']; ?></td>
</tr>

<tr>
<th>Phone</th>
<td><?php echo $user['phone']; ?></td>
</tr>

<tr>
<th>Department</th>
<td><?php echo $user['department_name']; ?></td>
</tr>

<tr>
<th>Position</th>
<td><?php echo $user['position']; ?></td>
</tr>

<tr>
<th>Role</th>
<td><?php echo $user['role']; ?></td>
</tr>

<tr>
<th>Status</th>
<td><?php echo $user['status']; ?></td>
</tr>

</table>

<a href="users.php" class="btn btn-secondary">
Back
</a>

</div>

</div>

</div>

</div>

</div>

<?php include("../includes/footer.php"); ?>