<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../includes/header.php");
include("../config/database.php");

// Get Departments
$departments = mysqli_query($conn, "SELECT * FROM departments ORDER BY department_name ASC");

include("../includes/sidebar.php");
include("../includes/topbar.php");
?>

<div class="container-fluid">

<div class="card">

<div class="card-header bg-primary text-white">

<h4><i class="bi bi-person-plus-fill"></i> Add New User</h4>

</div>

<div class="card-body">

<form action="save_user.php" method="POST" enctype="multipart/form-data">

<div class="row">

<div class="col-md-4 mb-3">

<label>First Name</label>

<input
type="text"
name="first_name"
class="form-control"
required>

</div>

<div class="col-md-4 mb-3">

<label>Middle Name</label>

<input
type="text"
name="middle_name"
class="form-control">

</div>

<div class="col-md-4 mb-3">

<label>Last Name</label>

<input
type="text"
name="last_name"
class="form-control"
required>

</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>Email</label>

<input
type="email"
name="email"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label>Phone</label>

<input
type="text"
name="phone"
class="form-control"
required>

</div>

</div>

<div class="row">

<div class="col-md-4 mb-3">

<label>Gender</label>

<select
name="gender"
class="form-select"
required>

<option value="">Select</option>

<option>Male</option>

<option>Female</option>

</select>

</div>


<div class="col-md-4 mb-3">

    <label>Department</label>

    <select
        name="department_id"
        class="form-select"
        required
    >

        <option value="">Select Department</option>

        <?php

        while ($dept = mysqli_fetch_assoc($departments)) {

        ?>

            <option value="<?php echo $dept['id']; ?>">

                <?php echo htmlspecialchars($dept['department_name']); ?>

            </option>

        <?php

        }

        ?>

    </select>

</div>


<div class="col-md-4 mb-3">

    <label>Shift</label>

    <select
        name="shift_id"
        class="form-select"
        required
    >

        <option value="">Select Shift</option>

        <?php

        $shifts = mysqli_query(
            $conn,
            "SELECT id, shift_name, check_in_time, late_after, check_out_time
             FROM shifts
             WHERE status = 'Active'
             ORDER BY shift_name ASC"
        );

        while ($shift = mysqli_fetch_assoc($shifts)) {

        ?>

            <option value="<?php echo $shift['id']; ?>">

                <?php
                echo htmlspecialchars($shift['shift_name']);

                echo " - ";

                echo date("h:i A", strtotime($shift['check_in_time']));

                echo " to ";

                echo date("h:i A", strtotime($shift['check_out_time']));
                ?>

            </option>

        <?php

        }

        ?>

    </select>

</div>

<div class="col-md-4 mb-3">

<label>Role</label>

<select
name="role"
class="form-select"
required>

<option>Intern</option>

<option>Supervisor</option>

<option>Admin</option>

</select>

</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>Position</label>

<input
type="text"
name="position"
class="form-control"
placeholder="IT Student">

</div>

<div class="col-md-6 mb-3">

<label>Profile Photo</label>

<input
type="file"
name="profile_photo"
class="form-control">

</div>

</div>

<div class="row">

<div class="col-md-6 mb-3">

<label>Password</label>

<input
type="password"
name="password"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label>Confirm Password</label>

<input
type="password"
name="confirm_password"
class="form-control"
required>

</div>

</div>

<div class="mb-3">

<label>Status</label>

<select
name="status"
class="form-select">

<option>Active</option>

<option>Inactive</option>

</select>

</div>

<button
type="submit"
class="btn btn-success">

<i class="bi bi-save"></i>

Save User

</button>

<a
href="users.php"
class="btn btn-secondary">

Cancel

</a>

</form>

</div>

</div>

</div>

<?php include("../includes/footer.php"); ?>