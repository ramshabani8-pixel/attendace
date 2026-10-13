<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../includes/header.php");
include("../config/database.php");

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit();
}

$id = intval($_GET['id']);

// Get User
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get Departments
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name ASC");

$shifts = mysqli_query(
    $conn,
    "SELECT id, shift_name, check_in_time, late_after, check_out_time
     FROM shifts
     WHERE status = 'Active'
     ORDER BY shift_name ASC"
);

include("../includes/sidebar.php");
include("../includes/topbar.php");
?>

<div class="container-fluid">

<div class="card">

<div class="card-header bg-warning text-dark">
<h4><i class="bi bi-pencil-square"></i> Edit User</h4>
</div>

<div class="card-body">

<form action="update_user.php" method="POST" enctype="multipart/form-data">

<input type="hidden" name="id" value="<?php echo $user['id']; ?>">

<div class="row">

<div class="col-md-4 mb-3">
<label>First Name</label>
<input type="text" name="first_name" class="form-control"
value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
</div>

<div class="col-md-4 mb-3">
<label>Middle Name</label>
<input type="text" name="middle_name" class="form-control"
value="<?php echo htmlspecialchars($user['middle_name']); ?>">
</div>

<div class="col-md-4 mb-3">
<label>Last Name</label>
<input type="text" name="last_name" class="form-control"
value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">
<label>Email</label>
<input type="email" name="email" class="form-control"
value="<?php echo htmlspecialchars($user['email']); ?>" required>
</div>

<div class="col-md-6 mb-3">
<label>Phone</label>
<input type="text" name="phone" class="form-control"
value="<?php echo htmlspecialchars($user['phone']); ?>" required>
</div>

</div>

<div class="row">

<div class="col-md-4 mb-3">
<label>Gender</label>

<select name="gender" class="form-select">

<option value="Male" <?php if($user['gender']=="Male") echo "selected"; ?>>Male</option>

<option value="Female" <?php if($user['gender']=="Female") echo "selected"; ?>>Female</option>

</select>

</div>

<div class="row">

    <!-- Department -->
    <div class="col-md-6 mb-3">

        <label class="form-label">
            Department
        </label>

        <select
            name="department_id"
            class="form-select"
            required
        >

            <option value="">
                Select Department
            </option>

            <?php while ($dept = mysqli_fetch_assoc($departments)): ?>

                <option
                    value="<?php echo $dept['id']; ?>"
                    <?php
                    echo ($dept['id'] == $user['department_id'])
                        ? 'selected'
                        : '';
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $dept['department_name']
                    );
                    ?>

                </option>

            <?php endwhile; ?>

        </select>

    </div>


    <!-- Shift -->
    <div class="col-md-6 mb-3">

        <label class="form-label">
            Shift
        </label>

        <select
            name="shift_id"
            class="form-select"
            required
        >

            <option value="">
                Select Shift
            </option>

            <?php while ($shift = mysqli_fetch_assoc($shifts)): ?>

                <option
                    value="<?php echo $shift['id']; ?>"
                    <?php
                    echo ($shift['id'] == $user['shift_id'])
                        ? 'selected'
                        : '';
                    ?>
                >

                    <?php
                    echo htmlspecialchars(
                        $shift['shift_name']
                    );

                    echo " - ";

                    echo date(
                        "h:i A",
                        strtotime($shift['check_in_time'])
                    );

                    echo " to ";

                    echo date(
                        "h:i A",
                        strtotime($shift['check_out_time'])
                    );
                    ?>

                </option>

            <?php endwhile; ?>

        </select>

    </div>

</div>

<div class="col-md-4 mb-3">

<label>Role</label>

<select name="role" class="form-select">

<option value="Admin" <?php if($user['role']=="Admin") echo "selected"; ?>>Admin</option>

<option value="Supervisor" <?php if($user['role']=="Supervisor") echo "selected"; ?>>Supervisor</option>

<option value="Intern" <?php if($user['role']=="Intern") echo "selected"; ?>>Intern</option>

</select>

</div>

</div>

<div class="mb-3">

<label>Position</label>

<input type="text"
name="position"
class="form-control"
value="<?php echo htmlspecialchars($user['position']); ?>">

</div>

<div class="mb-3">

<label>Status</label>

<select name="status" class="form-select">

<option value="Active" <?php if($user['status']=="Active") echo "selected"; ?>>Active</option>

<option value="Inactive" <?php if($user['status']=="Inactive") echo "selected"; ?>>Inactive</option>

</select>

</div>

<button class="btn btn-success">
<i class="bi bi-save"></i> Update User
</button>

<a href="users.php" class="btn btn-secondary">
Cancel
</a>

</form>

</div>

</div>

</div>

<?php include("../includes/footer.php"); ?>