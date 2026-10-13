<?php
include("../includes/auth_check.php");

requireRole('Admin');
include("../includes/header.php");
include("../config/database.php");

// Check ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: shifts.php");
    exit();
}

$id = intval($_GET['id']);

// Get shift
$sql = "SELECT * FROM shifts WHERE id = ? LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param($stmt, "i", $id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$shift = mysqli_fetch_assoc($result);

// Check if shift exists
if (!$shift) {
    header("Location: shifts.php");
    exit();
}

include("../includes/sidebar.php");
include("../includes/topbar.php");

?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="mb-1">
                <i class="bi bi-pencil-square"></i>
                Edit Shift
            </h3>

            <p class="text-muted mb-0">
                Update working shift information and attendance time rules.
            </p>

        </div>

        <a
            href="shifts.php"
            class="btn btn-secondary"
        >

            <i class="bi bi-arrow-left"></i>

            Back to Shifts

        </a>

    </div>


    <!-- Edit Shift Form -->
    <div class="card">

        <div class="card-header bg-warning">

            <h5 class="mb-0">
                Shift Information
            </h5>

        </div>

        <div class="card-body">

            <form
                action="update_shift.php"
                method="POST"
            >

                <!-- Hidden ID -->
                <input
                    type="hidden"
                    name="id"
                    value="<?php echo $shift['id']; ?>"
                >


                <div class="row">

                    <!-- Shift Name -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Shift Name
                        </label>

                        <input
                            type="text"
                            name="shift_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($shift['shift_name']); ?>"
                            required
                        >

                    </div>


                    <!-- Status -->
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select"
                        >

                            <option
                                value="Active"
                                <?php
                                echo ($shift['status'] === 'Active')
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Active
                            </option>

                            <option
                                value="Inactive"
                                <?php
                                echo ($shift['status'] === 'Inactive')
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                Inactive
                            </option>

                        </select>

                    </div>

                </div>


                <div class="row">

                    <!-- Check In -->
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Official Check-In Time
                        </label>

                        <input
                            type="time"
                            name="check_in_time"
                            class="form-control"
                            value="<?php echo $shift['check_in_time']; ?>"
                            required
                        >

                    </div>


                    <!-- Late After -->
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Late After
                        </label>

                        <input
                            type="time"
                            name="late_after"
                            class="form-control"
                            value="<?php echo $shift['late_after']; ?>"
                            required
                        >

                    </div>


                    <!-- Check Out -->
                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Official Check-Out Time
                        </label>

                        <input
                            type="time"
                            name="check_out_time"
                            class="form-control"
                            value="<?php echo $shift['check_out_time']; ?>"
                            required
                        >

                    </div>

                </div>


                <!-- Buttons -->

                <button
                    type="submit"
                    class="btn btn-success"
                >

                    <i class="bi bi-save"></i>

                    Update Shift

                </button>


                <a
                    href="shifts.php"
                    class="btn btn-secondary"
                >

                    Cancel

                </a>

            </form>

        </div>

    </div>

</div>

<?php include("../includes/footer.php"); ?>